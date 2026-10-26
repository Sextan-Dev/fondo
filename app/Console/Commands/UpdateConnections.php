<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Log;
use Mail;
class UpdateConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateConnections:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to all users connected and request forced to refresh';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        event(new \App\Events\DeploymentNotification( (object) [
            'type' => 'update',
            'message' => 'New deployment, please update this page'
        ]));
    }
}
