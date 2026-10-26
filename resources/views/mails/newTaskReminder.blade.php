@extends('mails.layout')
@section('message')
<!-- Body content -->
<tr>
    <td class="content-cell" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 35px;">
        <h1 style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #2F3133; font-size: 19px; font-weight: bold; margin-top: 0; text-align: left;">{{_("Hola ") . $data['user']}},</h1>
        <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">{!!_("Te recordamos que ") .$data['start']._(" comienza la etapa ").'<b style="font-style: italic;">'.$data['reminder'].'</b>'._(", perteneciente a la tarea ") !!} <b style="font-style: italic;">{{ $data['task'] }} [{{ $data['project'] }}]</b> {{ _(', para conocer el detalle accede al portal para revisarla.')}}</p>
    </td>
</tr>
<tr>
    <td class="content-cell" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 35px;">
        <a href="{{url('/')}}" class="button button-blue" target="_blank" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; border-radius: 3px; box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16); color: #FFF; display: inline-block; text-decoration: none; -webkit-text-size-adjust: none; background-color: #3097D1; border-top: 10px solid #3097D1; border-right: 18px solid #3097D1; border-bottom: 10px solid #3097D1; border-left: 18px solid #3097D1;">Ir al Portal</a>
    </td>
</tr>
@endsection
