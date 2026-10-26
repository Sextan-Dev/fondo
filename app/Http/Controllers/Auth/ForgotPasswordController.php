<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // Sending is inside the try: a failing mail transport or template used to escape it as a bare 500
        try{
            $response = $this->broker()->sendResetLink(
                $request->only('email')
            );
        }catch(\Exception $e ){
            report($e);
            return ['success' => false , 'msg' => _("No pudimos enviar el correo, intenta de nuevo más tarde.")];
        }

        switch ($response) {
            case \Password::RESET_LINK_SENT:
                return ['success'=>true , 'msg' => _("Correo enviado de forma correcta, revisa tu correo, no olvides revisar tu carpeta de spam.")];
            case \Password::INVALID_USER:
                return ['success'=>false , 'msg' => _("No encontramos ningún usuario con ese correo.")];
            case \Password::RESET_THROTTLED:
                return ['success'=>false , 'msg' => _("Ya te enviamos un correo hace un momento, espera unos minutos antes de pedir otro.")];
            default:
                return ['success'=>false , 'msg' => _("Oups... Un error ha ocurrido: ").trans($response)];
        }
    }

}
