<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Log;
use Mail;
class DeploymentAlert extends Command
{
    use \App\Http\Traits\NotificationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alertDeployment:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $notification = [
            'title' => _('Actualización del sistema disponible'),
            'type' => 'deploy',
            'message' => "Existe una actualización disponible para el sistema, para poder cargar esta actualización vuelve a cargar esta pagina haciendo click en el icono &nbsp;<i class='fa fa-repeat mr-1'></i> de tu navegador o dando click en el siguiente link <a href='/' class='btn'>Recargar Pagina</a>",
            'channel_id' => '',
            'addressee' => 'all',
            'url' => ''
        ];

        $this->sendPushNotificaction($notification);
    }
}
