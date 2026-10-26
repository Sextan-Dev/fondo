@extends('mails.layout')
@section('message')
<tr>
    <td style="padding:20px">
        <h1 style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #2F3133; font-size: 19px; font-weight: bold; margin-top: 0; text-align: left;">
                {{_("Hola ") . $data['name']}},
        </h1>
        <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
            {{_('Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón para elegir una nueva.') }}
        </p>
    </td>
</tr>
<tr>
    <td class="content-cell" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 0 35px 35px;">
        <!-- BUTTON -->
        <a href="{{ $data['link'] }}" class="button button-blue" target="_blank" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; border-radius: 3px; box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16); color: #FFF; display: inline-block; text-decoration: none; -webkit-text-size-adjust: none; background-color: #3097D1; border-top: 10px solid #3097D1; border-right: 18px solid #3097D1; border-bottom: 10px solid #3097D1; border-left: 18px solid #3097D1;">
            {{_("Restablecer contraseña")}}
        </a>
    </td>
</tr>
<tr>
    <td style="padding:0 20px">
        <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
            {{ sprintf(_('Este enlace caduca en %d minutos. Si no pediste este cambio, ignora este correo: tu contraseña seguirá siendo la misma.'), $data['expire']) }}
        </p>
        <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #AEAEAE; font-size: 12px; line-height: 1.5em; margin-top: 0; text-align: left; word-break: break-all;">
            {{_('Si el botón no funciona, copia y pega esta dirección en tu navegador:') }}<br>
            <a href="{{ $data['link'] }}" style="color: #3097D1;">{{ $data['link'] }}</a>
        </p>
    </td>
</tr>

@endsection
