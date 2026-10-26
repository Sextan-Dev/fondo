@extends('mails.layout')
@section('message')
<!-- Body content -->
<tr>
    <td class="content-cell" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 35px;">
        <h1 style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #2F3133; font-size: 19px; font-weight: bold; margin-top: 0; text-align: left;">{{_("Hola ") . $data['user']}},</h1>
        <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">{{_('Hemos recibido tu solicitud de soporte por parte tuya, procederemos a analizarla y en breve te brindaremos el status de la misma.') }}</p>                                                       
    </td>
</tr>
@endsection