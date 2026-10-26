<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Project;
use App\User;
use App\Contact;
use App\Operation;
use Illuminate\Support\Facades\DB;
use DataTables;
use Validator;
use Carbon\Carbon;
use PhpParser\Node\Expr\AssignOp\Concat;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\OperationHistory;

use App\Imports\Operations\Incomes;
use App\Imports\Operations\Outcomes;
use App\Imports\Operations\Transfers;
use App\Imports\Operations\Projects;

class LayoutController extends Controller
{
    public function uploadMasiveProjects(Request $request){
        try{
            if($request->has('projects')) {
                $file = \Request::file('projects');
                $filename = 'projects_' . date('YmdHis') . '.xlsx';
                \Storage::disk('local')->put('tmp/' . $filename,  \File::get($file));

                if(file_exists(storage_path('app/tmp') . '/' . $filename)){
                    
                    $result = Excel::import(new Projects(), $request->file('projects') );

                    return [
                        'success' => true,
                        'result' => $result,
                        'msg' => 'Importation réussie'
                    ];
                }else{
                    return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
                }
            }else{
                return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
            }
        }catch(\Exception $e ){
            return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: ").$e->getMessage(). ' - ' .$e->getFile() .$e->getLine()];
        }
    }

    public function uploadMasiveIncomes(Request $request){
        try{
            if($request->has('incomes')) {
                $file = \Request::file('incomes');

                $filename = 'incomes_' . date('YmdHis') . '.xlsx';
                \Storage::disk('local')->put('tmp/' . $filename,  \File::get($file));

                if(file_exists(storage_path('app/tmp') . '/' . $filename)){
                    
                    $result = Excel::import(new Incomes(), $request->file('incomes') );

                    $dataHistory = [
                        'user_id' => authUser('id'),
                        'status' => 1,
                        'original_name' => $file->getClientOriginalName(),
                        'import_key' => $filename
                    ];
                    $storeOperationH = new OperationHistory;
                    $storeOperationH->fill($dataHistory);
                    $storeOperationH->save();
    
                    return [
                        'success' => true,
                        'result' => $result,
                        'msg' => 'Importation réussie'
                    ];
                }else{
                    return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
                }
            }else{
                return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
            }
        }catch(\Exception $e ){
            return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: ").$e->getMessage()];
        }
    }

    public function uploadMasiveTransfers(Request $request){
        try{
            if($request->has('transfers')) {
                $file = \Request::file('transfers');
                $filename = 'transfers_' . date('YmdHis') . '.xlsx';
                \Storage::disk('local')->put('tmp/' . $filename,  \File::get($file));

                if(file_exists(storage_path('app/tmp') . '/' . $filename)){
                    
                    $result = Excel::import(new Transfers(), $request->file('transfers') );

                    return [
                        'success' => true,
                        'result' => $result,
                        'msg' => 'Importation réussie'
                    ];
                }else{
                    return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
                }
            }else{
                return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
            }
        }catch(\Exception $e ){
            return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: ").$e->getMessage()];
        }
    }

    public function uploadMasiveOutcomes(Request $request){
        try{
            if($request->has('outcomes')) {
                $file = \Request::file('outcomes');
                $filename = 'outcomes_' . date('YmdHis') . '.xlsx';
                \Storage::disk('local')->put('tmp/' . $filename,  \File::get($file));

                if(file_exists(storage_path('app/tmp') . '/' . $filename)){
                    
                    $result = Excel::import(new Outcomes(), $request->file('outcomes') );

                    return [
                        'success' => true,
                        'result' => $result,
                        'msg' => 'Importation réussie'
                    ];
                }else{
                    return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
                }
            }else{
                return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: file not found or not upload")];
            }
        }catch(\Exception $e ){
            return ['success' => false , 'msg' =>  _("Oups... Un Error ha ocurrido: ").$e->getMessage()];
        }
    }

    public function fixProjects(){
        $projects = [
        ['id' => 1, 'names' =>	"Agencia de las Naciones Unidas para los Refugiados"],
        ['id' => 2, 'names' =>	"IK Comunidad Infantil S de RL de CV,Grupo de Trabajo para el Desarrollo de la Niñez AC,Asociacion para Leer Escuchar Escribir  Recrear AC"],
        ['id' => 3, 'names' =>	"IK Comunidad Infantil S de RL de CV,Grupo de Trabajo para el Desarrollo de la Niñez AC,K & Koncept SA de CV"],
        ['id' => 4, 'names' =>	"Fundacion Mexicana para la Planeacion Familiar AC,Solucionesde  GPC INC S de RL de CV "],
        ['id' => 5, 'names' =>	"Asociacion Potosina en Pro del Deficiente Mental  AC"],
        ['id' => 6, 'names' =>	"Juntos una experiencia compartida AC"],
        ['id' => 7, 'names' =>	"Musica para la Vida AC"],
        ['id' => 8, 'names' =>	"Nuestros Niños de San Luis AC"],
        ['id' => 9, 'names' =>	"Laboratorio de Imaginacion y Accion Social AC"],
        ['id' => 10, 'names' =>	"Patronato Nacional de Alfabetizacion y Extension Educativa AC,Instituto de Pensamiento y Cultura en America Latina AC"],
        ['id' => 11, 'names' =>	"Fundacion Robotica para la Educacion AC"],
        ['id' => 12, 'names' =>	"Proline Builders SA de CV,Arco Iris de Jesus ABP"],
        ['id' => 13, 'names' =>	"Ada Elena Popoca Vazquez"],
        ['id' => 14, 'names' =>	"Alimentos de México para Compartir AC"],
        ['id' => 15, 'names' =>	"Daunis Gente Excepcional IAP,Red Pedagogica Contemporanea,Ana M. Medrano Moya,Juan Carlos Hernández Chacon,Alejandro León Maldonado"],
        ['id' => 16, 'names' =>	"Escuela Hogar del Perpetuo Socorro Toluca IAP"],
        ['id' => 17, 'names' =>	"Centro de la Amistad del Cerro del Judio IAP"],
        ['id' => 18, 'names' =>	"Vamos a Dar AC"],
        ['id' => 19, 'names' =>	"Escalera Foundation Mexico AC"],
        ['id' => 20, 'names' =>	"Fundacion Homeless Mexico AC,Angeles Perez Castañeda"],
        ['id' => 21, 'names' =>	"Asociacion Mexicana para Naciones Unidas de Jovenes AMNU AC"],
        ['id' => 22, 'names' =>	"Fundacion Robotica para la Educacion AC"],
        ['id' => 23, 'names' =>	"Fundacion IMSS Ac,Comite de Ayuda a Desastres y Emergencias Nacionales AC,Fundacion Hogares IAP,Desarrollo Dorado De Milagros"],
        ['id' => 24, 'names' =>	"Fundation Forge"],
        ['id' => 25, 'names' =>	"IK Comunidad infantil,Utopia Acciones para Inspirar"],
        ['id' => 26, 'names' =>	"Fundacion Alma IAP"],
        ['id' => 27, 'names' =>	"Early Institute AC"],
        ['id' => 28, 'names' =>	"Fundacion Mexicana para la Planificacion Familiar AC,Respondiste AC,Desarrollo Rural Sustentable LUUM AC"],
        ['id' => 29, 'names' =>	"Escuelas Sustentables AC"],
        ['id' => 30, 'names' =>	"Co plataforma de Investigacion Generativa AC"],
        ['id' => 31, 'names' =>	"Co plataforma de Investigacion Generativa AC"],
        ['id' => 32, 'names' =>	"Atentamente Consultores AC"],
        ['id' => 33, 'names' =>	"Fondo para la Paz IAP,UtopIa Acciones Reales AC"],
        ['id' => 34, 'names' =>	"Escuelas Sustentables AC"],
        ['id' => 35, 'names' =>	"Escuelas Sustentables AC"],
        ['id' => 36, 'names' =>	"Purple Flower AC"],
        ['id' => 37, 'names' =>	"Casa de la Sal AC"],
        ['id' => 38, 'names' =>	"Escuelas Sustentables AC"],
        ['id' => 39, 'names' =>	"Alto Comisionado de Naciones Unidas para Refugiados,Alimentos de Mexico a Compartir AC,Caritas Mexicana AC,DEI Comunidad AC,Medical Mission Network Mexico AC,Vision Mundial de México AC"],
        ['id' => 40, 'names' =>	"Instituto Internacional de Recursos Renovables AC"],
        ['id' => 41, 'names' =>	"Fondo Unido IAP"],
        ['id' => 42, 'names' =>	"Por confirmar"],
        ['id' => 43, 'names' =>	"Fundación UNAM"],
        ['id' => 44, 'names' =>	"Utopia Acciones Reales AC,Nueva Vida AC,Magiam Natura SC,Hunab Proyecto de Vida "],
        ['id' => 45, 'names' =>	"Proline Builders  SA de CV"],
        ['id' => 46, 'names' =>	"Construyendo Esperanzas AC"]
        ];

        foreach($projects as $project){
            
            $allies = [];
            $projectAllies = explode(',',$project['names']);

            foreach($projectAllies as $allie){

                $contact =  Contact::select('id')->where([
                    'name' => trim($allie),
                    'category' => 'Aliado',
                    'active' => 1
                ])->first();
                if($contact){
                    $contact_id = $contact->id;      
                }else{
                    $contact = new Contact();
                    $contact->fill([
                        'name' => trim($allie),
                        'category' => 'Aliado',
                        'company_id' => idCompany(),
                        'active' => 1
                    ]);
    
                    $contact->save();
                    $contact_id = $contact->id;
                } 

              
                $allies[] = $contact_id;
            }

            $update = Project::where(['id' => $project['id'] ])->first();

            $update['allies'] = implode(',',$allies);
            $update->save();
        }
    }
   
}