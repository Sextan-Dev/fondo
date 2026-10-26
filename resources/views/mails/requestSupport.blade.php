@extends('mails.layout')
@section('message')
<!-- Body content -->
    <tr>
        <td class="content-cell" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 35px;">
            <h1 style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #2F3133; font-size: 19px; font-weight: bold; margin-top: 0; text-align: left;">{{_("Atención")}},</h1>
            <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">{!!_('El usuario '). "<b>" . $data['user']  . "</b> (". $data['email']  .") " . _( 'ha realizado una petición de soporte, favor de verificar.' )!!}</p>                                                       
        </td>
    </tr>
    <tr>
        <td class="content-cell" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 35px;">
            <table style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; margin: 0 auto; padding: 0; text-align: center;  -premailer-cellpadding: 0; -premailer-cellspacing: 0; width:100%">
                <thead>
                    <th style="text-align: left;">{{_("Sección")}}</th>
                    <th style="text-align: left;">{{_("Tipo")}}</th>
                    <th style="text-align: left;">{{_("Descripción")}}</th>
                </thead>
                <tbody>
                    <tr>
                        <td style="color: #A5A5A5; font-size:12px; margin-top: 10px; margin-bottom: 5px;">
                            {{ $data['section'] }}
                        </td> 
                        <td style="color: #A5A5A5; font-size: 12px; margin-top: 10px;margin-bottom: 5px;">
                            {{ $data['type'] }}         
                        </td>
                        <td style="color: #A5A5A5; font-size: 12px; margin-top: 10px;margin-bottom: 5px;">
                            {{ $data['description'] }}         
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
@endsection