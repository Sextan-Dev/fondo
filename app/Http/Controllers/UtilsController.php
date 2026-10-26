<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Contact;
use App\Content;
use App\Operation;
use App\Project;

class UtilsController extends Controller {

    /*** DELTE FILES ***/
    public function deleteFiles(Request $request)
    {
        try {
            $path = publicUploadPath((string) $request->path);
            if (!$path) {
                return ['success' => false, 'msg' => _('Archivo no permitido.')];
            }
            unlink($path);

            return ['success' => true , 'msg' =>  _("Archivo eliminado correctamente ")];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function cleanContacts()
    {
        $contacts = Contact::select('id', 'name')->get()->toArray();

        $used = 0;

        foreach ($contacts as $key => $contact) {

            $contents = Content::where([
                'contact_id' => $contact['id']
            ])->count();

            if($contents > 0){
                continue;
            }else{

                $operations_1 = Operation::where([
                    'contact_id' => $contact['id']
                ])->count();
                $operations_2 = Operation::where([
                    'donor_id' => $contact['id']
                ])->count();
    
                if($operations_1 > 0 || $operations_1>0){
                    continue;
                }else{                    
                    $oproject_1 = Project::where([
                        'donors' => $contact['id']
                    ])->count();
                    
                    if($oproject_1 > 0 ){
                        continue;
                    }else{
                        $notUsed[] = $contact;
                    }                    
                }
            }
        }

        return $notUsed;
    }
}
