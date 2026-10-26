<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Email;
class NotificationRequestSuppport extends Mailable
{
    use Queueable, SerializesModels;

    public array $dataMessage;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($dataMessage)
    {
        $this->dataMessage = $dataMessage;
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
        $subject = _('Nueva solicitud de soporte');
        
        $notification['type'] = 'requestSupport' ;
        $notification['subject'] = $subject ;
        $notification['message'] = '' ;
        $notification['date'] = date('Y-m-d') ;
        $notification['email_from'] = $address ;
        $notification['email_to'] = '' ;
        $notification['email_cc'] = '' ;
        $notification['email_bcc'] = '' ;
        $notification['comment'] = '' ;
        $notification['company_id'] = idCompany() ;


        $email = new Email;
        $email->fill( $notification );
        $email->save();

        return $this->view('mails.requestSupport', ['data'=> $this->dataMessage])
                    ->from($address, $name)
                    ->replyTo($this->dataMessage['email'])
                    ->to("support@sextansystem.com")
                    ->cc("jorge@sextansystem.com")
                    ->subject($subject);
    }
}
