<?php

namespace App\Http\Controllers;

use App\Content;
use App\User;
use App\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mail;
use App\Mail\NotificationReponseSuppport;
use App\Mail\NotificationRequestSuppport;

class SupportController extends Controller {

    /*** SEND MAIL SUPPORT ***/
    public function requestSupport(Request $request)
    {
        try {
            $user = User::find(authUser('id'));
            $type['improvement'] = 'Task';
            $type['error'] = 'Bug';
            $type['ask'] = 'Story';
            $type['other'] = 'Story';
            $priority['improvement'] = 'Task';
            $priority['error'] = 'Bug';
            $priority['ask'] = 'Story';
            $priority['other'] = 'Story';

            //JIRA INTEGRATION
            // $curl = curl_init();
            // curl_setopt_array($curl, array(
            //     CURLOPT_URL => config('jira.url')."/rest/api/2/issue/",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 30,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "POST",
            //     CURLOPT_POSTFIELDS => '{
            //                                 "fields": {
            //                                     "project":
            //                                     { 
            //                                         "key": "NS"
            //                                     },
            //                                     "summary": "'.$request->section.'",
            //                                     "description": "'.$request->description.' Created by user - '.$user->name.'",
            //                                     "issuetype": {
            //                                         "name": "'.$type[$request->type].'"
            //                                     },
            //                                     "assignee": { "name": "'.config('jira.assignee').'" }                            
            //                                 }
            //                             }',
            //     CURLOPT_HTTPHEADER => array(
            //         "authorization: Basic ".config('jira.auth'),
            //         "cache-control: no-cache",
            //         "content-type: application/json"
            //     )
            // ));
            // $response = curl_exec($curl);
            // $err = curl_error($curl);
            // curl_close($curl);

               
            Mail::send(new NotificationRequestSuppport([
                'user'=> $user->name,
                'type'=> $request->type,
                'section'=> $request->section,
                'description'=> $request->description, 
                'email'=> $user->email,
                'name'=> $user->name
            ]));

            if (Mail::failures()) {
                return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
            }
            Mail::to($user->email)->send(new NotificationReponseSuppport(['user'=> $user->name ]) );
            return ['success' => true, 'msg' => _("Tu petición de soporte se ha enviado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

}
