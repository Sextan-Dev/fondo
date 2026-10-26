<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Email;
class NotificationDonorUpdateProject extends Mailable
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
        $subject = _('Actualización de Proyecto');
        
        
        $notification['type'] = 'donor_notification' ;
        $notification['subject'] = $subject ;
        $notification['message'] = 'update project' ;
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

        return $this->view('mails.donorUpdateProject', ['data'=> $this->dataMessage])
                    ->from($address, $name)
                    ->subject($subject);
    }
}
