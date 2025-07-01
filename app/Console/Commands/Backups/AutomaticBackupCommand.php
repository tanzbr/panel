<?php

namespace Pterodactyl\Console\Commands\Backups;

use Carbon\Carbon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Console\Command;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;

class AutomaticBackupCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'p:backups:auto';

    /**
     * @var string
     */
    protected $description = 'Manage auto backups.';

    /**
     * @var InitiateBackupService
     */
    protected $initiateBackupService;

    /**
     * @var SettingsRepository
     */
    protected $settingsRepository;

    /**
     * @var DeleteBackupService
     */
    protected $deleteBackupService;

    /**
     * @param InitiateBackupService $initiateBackupService
     * @param SettingsRepository $settingsRepository
     * @param DeleteBackupService $deleteBackupService
     */
    public function __construct(InitiateBackupService $initiateBackupService, SettingsRepository $settingsRepository, DeleteBackupService $deleteBackupService)
    {
        parent::__construct();

        $this->initiateBackupService = $initiateBackupService;
        $this->settingsRepository = $settingsRepository;
        $this->deleteBackupService = $deleteBackupService;
    }

    /**
     *
     */
    public function handle()
    {
        $excludedNodes = json_decode($this->settingsRepository->get('backup::auto::excluded', '{}'), true);

        foreach (Server::query()->whereNull('status')->whereNotIn('node_id', $excludedNodes)->get() as $server) {
            // Create backup every day
            $server->backup_limit += 1;
            $server->automatic_backup = true;
            $backupName = str_replace('[DATE]', Carbon::now()->format('Y-m-d'), $this->settingsRepository->get('backup::auto::name', 'Automatic Backup [DATE]'));

            try {
                $this->initiateBackupService->handle($server, $backupName);
            } catch (TooManyBackupsException|\Throwable $e) {
                $this->line(sprintf('Failed to start the backup creation for %s', $server->uuidShort));
            }

            // Delete old automatic backups
            $daysToStore = $this->settingsRepository->get('backup::auto::days', 0);
            $weeksToStore = $this->settingsRepository->get('backup::auto::weeks', 0);
            $monthsToStore = $this->settingsRepository->get('backup::auto::months', 0);
            $backups = $server->backups()->where('is_automatic', '=', 1)->orderBy('created_at', 'DESC')->get();

            foreach ($backups as $key => $backup) {
                $daysInDiff = Carbon::parse(date('Y-m-d', strtotime($backup->created_at)))->diffInDays(Carbon::now());

                // Check every day store
                if ($daysInDiff < $daysToStore) {
                    continue;
                }

                // Check week store
                if ($daysInDiff < $daysToStore + ($weeksToStore * 7)) {
                    if (self::hasEarlierBackupForWeek($backups, $key)) {
                        $this->deleteBackup($backup);
                    }

                    continue;
                }

                // Check month store
                if ($daysInDiff < Carbon::now()->subDays($daysToStore + ($weeksToStore * 7))->subMonths($monthsToStore)->diffInDays(Carbon::now())) {
                    if (self::hasEarlierBackupForMonth($backups, $key)) {
                        $this->deleteBackup($backup);
                    }

                    continue;
                }

                // Delete in every other case
                $this->deleteBackup($backup);
            }
        }
    }

    /**
     * @param Backup $backup
     * @return void
     */
    private function deleteBackup(Backup $backup)
    {
        try {
            $this->deleteBackupService->handle($backup);
        } catch (\Throwable $e) {
            $this->line(sprintf('Failed to delete backup #%s', $backup->id));
        }
    }

    /**
     * @param $backups
     * @param $key
     * @return bool
     */
    private static function hasEarlierBackupForWeek($backups, $key)
    {
        if (date('N', strtotime($backups[$key]->created_at)) == 1) {
            return false;
        }

        if (!isset($backups[$key + 1]->created_at)) {
            return false;
        }

        if (
            date('N', strtotime($backups[$key + 1]->created_at)) < date('N', strtotime($backups[$key]->created_at)) &&
            date('Y-W', strtotime($backups[$key + 1]->created_at)) == date('Y-W', strtotime($backups[$key]->created_at))
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $backups
     * @param $key
     * @return bool
     */
    private static function hasEarlierBackupForMonth($backups, $key)
    {
        if (date('j', strtotime($backups[$key]->created_at)) == 1) {
            return false;
        }

        if (!isset($backups[$key + 1]->created_at)) {
            return false;
        }

        if (
            date('j', strtotime($backups[$key + 1]->created_at)) < date('j', strtotime($backups[$key]->created_at)) &&
            date('Y-n', strtotime($backups[$key + 1]->created_at)) == date('Y-n', strtotime($backups[$key]->created_at))
        ) {
            return true;
        }

        return false;
    }
}
