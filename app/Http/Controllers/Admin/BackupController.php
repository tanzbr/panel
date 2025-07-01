<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Prologue\Alerts\Facades\Alert;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;

class BackupController extends Controller
{
    /**
     * @var SettingsRepository
     */
    protected $settingsRepository;

    /**
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('admin.backup', [
            'run_at' => $this->settingsRepository->get('backup::auto::run', '02:00'),
            'days' => $this->settingsRepository->get('backup::auto::days', 0),
            'weeks' => $this->settingsRepository->get('backup::auto::weeks', 0),
            'months' => $this->settingsRepository->get('backup::auto::months', 0),
            'name' => $this->settingsRepository->get('backup::auto::name', 'Automatic Backup [DATE]'),
            'excluded_nodes' => json_decode($this->settingsRepository->get('backup::auto::excluded', '{}'), true),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function save(Request $request)
    {
        $this->validate($request, [
            'run_at' => ['required', 'date_format:H:i'],
            'days' => ['required', 'integer', 'min:0'],
            'weeks' => ['required', 'integer', 'min:0'],
            'months' => ['required', 'integer', 'min:0'],
            'backup_name' => ['required', 'string', 'min:1', 'max:40'],
            'excluded_nodes' => ['array'],
            'excluded_nodes.*' => ['integer', 'exists:nodes,id'],
        ]);

        $this->settingsRepository->set('backup::auto::run', $request->input('run_at', '02:00'));
        $this->settingsRepository->set('backup::auto::days', (int) $request->input('days', 0));
        $this->settingsRepository->set('backup::auto::weeks', (int) $request->input('weeks', 0));
        $this->settingsRepository->set('backup::auto::months', (int) $request->input('months', 0));
        $this->settingsRepository->set('backup::auto::name', trim(strip_tags($request->input('backup_name', 'Automatic Backup [DATE]'))));
        $this->settingsRepository->set('backup::auto::excluded', json_encode($request->input('excluded_nodes', [])));

        Alert::success('You\'ve successfully saved automatic backup settings.')->flash();

        return back();
    }
}
