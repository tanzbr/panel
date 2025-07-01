<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $owner = null;
        $count = 0;

        foreach(User::all() as $user) {
            foreach($user->servers as $server) {
                if ($owner !== $server->owner_id) {
                    $owner = $server->owner_id;
                    $count = 0;
                }
                $count = $count + 1;

                $server->update([
                    'position' => $count,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
