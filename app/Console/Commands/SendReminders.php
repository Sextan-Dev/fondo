<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Mail\NotificationNewTaskReminder;
use App\Content;
use Log;
use Mail;
class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendReminders:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search and send all pending reminders';

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
        // UPCOMING REMINDERS 
        $upcomingReminders = Content::whereRaw('
            active= 1 
            AND type = "reminder"
            AND DATE_FORMAT( date , "%Y-%m-%d") = DATE_FORMAT( NOW(), "%Y-%m-%d") '
        )->with(['task.user', 'task.project'])->get();
        

        foreach($upcomingReminders as $key => $reminder){
            Mail::to($reminder->user->email, $reminder->user->name)
            ->send(new NotificationNewTaskReminder([
                'user'=> $reminder->user->name,
                'start'=>_("mañana"),
                'project'=>$reminder->task->project->name ,
                'task'=>$reminder->task->name, 
                'reminder' => $reminder->value 
            ]));
        }

        //TODAY REMINDERS 
        $todayReminders = Content::whereRaw('
            active=1 
            AND  type = "reminder" 
            AND DATE_FORMAT( date , "%Y-%m-%d") = DATE_FORMAT( NOW(), "%Y-%m-%d")'
        )->with(['task.user', 'task.project'])->get();
        

        foreach($todayReminders as $key => $reminder){           
            Mail::to($reminder->user->email, $reminder->user->name)
            ->send(new NotificationNewTaskReminder([
                'user'=> $reminder->user->name,
                'start'=>_("hoy"),
                'project'=>$reminder->task->project->name ,
                'task'=>$reminder->task->name, 
                'reminder' => $reminder->value 
            ]));           
        }
    }
}
