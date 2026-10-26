<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Email;
class NotificationNewTaskReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = config('mail.from')['address'];
        $name = config('mail.from')['name'];
        $subject = _('Nuevo Recordatorio');
        
        
        $notification['type'] = 'reminder' ;
        $notification['subject'] = $subject ;
        $notification['message'] = '' ;
        $notification['date'] = date('Y-m-d') ;
        $notification['email_from'] = $address ;
        $notification['email_to'] = '' ;
        $notification['email_cc'] = '' ;
        $notification['email_bcc'] = '' ;
        $notification['comment'] = '' ;

        $email = new Email;
        $email->fill( $notification );
        $email->save();

        return $this->view('mails.newTaskReminder', ['data'=> $this->attributes])
                    ->from($address, $name)
                    ->subject($subject);
    }
}
