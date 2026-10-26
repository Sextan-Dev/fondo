<?php

namespace App\Http\Controllers;
use App\User;
use App\UserGroup;
use App\Action;
use App\Company;
use App\Notification;
use App\Content;
use App\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mail;
use DataTables;

class NotificationController extends Controller {
    
    use \App\Http\Traits\NotificationTrait;

    public function data(Request $request)
    {
        try{
            $users = User::selectRaw( ' CONCAT(c.slug, " - ",  users.name) as label, users.id as value' )
            ->leftJoin('companies as c', 'c.id', '=', 'users.company_id' )
            ->where(['users.active' => 1])->get();
            $groups = UserGroup::select( 'name as label', 'id as value' )->where(['active' => 1, ['name' , '!=' , 'root'] ])->get();
            $success = true;
            return compact('success', 'users', 'groups');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function send(Request $request)
    {
        return $this->sendPushNotificaction($request->all());
        return ['success' =>true , 'msg'=>_('La notificación ha sido registrada exitosamente.')];
    }

    public function show(Request $request)
    {
        try{
            $notification = Notification::where(['id'=>$request->id])->with('sender')->first();
            if($notification ){

                if($notification->type != 'auth'){
                    $notification->fill(['read' => 1])->save();
                }
                switch ($notification->type) {
                    case 'auth':                    
                        $notification->type_description =_('Solicitud de autorización');
                        break;
                    case 'text':                    
                        $notification->type_description = _('Notificación');
                        break;
                    case 'action':                    
                        $notification->type_description = _('Petición');
                        break;
                    case 'route':                    
                        $notification->type_description = _('Notificación');
                        break;
                    case 'deploy':                    
                        $notification->type_description = _('Notificación');
                        break;
                    default:
                        return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : Type notification not defined")];
                        break;
                }
    
                return ['success' =>true , 'notification' => $notification];
            }

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function responseAuth(Request $request)
    {
        try{
            $notification = Notification::where(['id'=>$request->id])->with('sender')->firstOrFail();
            $notification->fill([
                'read' => 1,
                'auth' => ($request->auth) ? 1 : 0
            ])->save();
            
            if($request->auth){
                $status = 'approved';
                $auth = _('Tu Solicitud ').'<strong>'.$notification->message.'</strong>'._(' ha sido autorizada');
            }else{
                $auth = _('Tu Solicitud ').'<strong>'.$notification->message.'</strong>'._(' no ha sido autorizada');
                $status = 'rejected';
            }

            if( $notification->content_id ){
                $content = Content::find($notification->content_id);
                if($content->type == 'order_pay'){
                    $content->status = $status;
                }else{
                    if($request->auth){
                        $content->active = 1;
                    }
                }
                $content->save();                
            }
            $noty = new Notification;
            $noty->fill( [
                'title' => _('Respuesta a solicitud de autorización'),
                'message' => $auth,
                'type' => 'text',
                'user_id' => $notification->sender_user_id,
                'sender_user_id' => authUser('id')
            ] );
            $noty->save();
            
            $notification = [];
            $notification['id'] = $noty->id; 
            $notification['title'] = $noty->title; 
            $notification['message'] = $noty->message; 
            $notification['type'] = 'Notificación'; 
            $notification['channel'] = 'userNotification.' . $noty->user_id;
            $notification['sender'] = authUser('name');

            event(new \App\Events\CustomNotification( (object) $notification ));

            return ['success' =>true , 'msg'=>_('La notificación ha sido enviada exitosamente.')];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function requestAuthDocument(Request $request)
    {
        try{

            $content_id = $request->id;
            $msg = '';
            $content = Content::selectRaw('contents.id,contents.type, contents.name,contents.task_id , SUM(o.cost * o.qt) as cost')
            ->leftJoin('operations as o' , 'o.content_id','=', 'contents.id')
            ->where([
                'contents.id' => $content_id                
            ])
            ->groupBy('contents.id')->first();
            
            
            if($content->type =='order'){
                $budget = DB::table('operations AS p')
                ->select(DB::raw('p.task_id, SUM(p.cost * p.qt) as cost'))
                ->where([
                    'p.task_id' => $content->task_id,
                    'p.type' => 'debit'
                ])
                ->groupBy('p.task_id')->first();
                $msg = "La orden de compra <strong>". $content->name. "</strong> excede los montos cotizados previamente en los requerimientos, se necesita aprovación para activar esta orden.<br>Monto cotizado: $".$budget->cost."<br>Monto de la Orden de compra: $".$content->cost;
                $title = _('Solicitud de autorizacón de Orden de compra');
            }else{
                if($content->type =='order_pay'){
                    $title = _('Solicitud de autorizacón de Orden de Pago');
                    $msg = "La orden de PAgo <strong>". $content->name. "</strong> excede los montos cotizados previamente en la Planeación, se necesita aprovación para activar esta orden.";
                }
            }
            //DATA PUSH NOTIFICATION
            $notification = [
                'title' => $title,
                'type' => 'auth',
                'content_id' => $request->id,
                'message' => $msg,
                'channel_id' => UserGroup::where(['slug' => 'fin'])->first()->id,
                'addressee' => 'group',
                'url' => ''
            ];

            // TRIGGER PUSH NOTIFICATION
            $this->sendPushNotificaction($notification);
            return ['success' =>true , 'msg'=>_('La notificación ha sido enviada exitosamente.')];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function unread(Request $request)
    {
        try{
            $notifications = Notification::where(['read' => 0])
            ->where(function($query)
            {
                $query->where('user_id', '=', authUser('id'));
            })->with('sender')->orderBy('id', 'desc')->get();

            
            return ['success' => true , 'notifications' => $notifications];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => 'Oups... Ha ocurrido un problema: '. $e->getMessage()];
        }
    }

    public function select(Request $request)
    {
        try{
            $limit = 10;
            $offset = $request->offset;
            $notifications = Notification::select(
                                DB::raw( "
                                            notifications.id,
                                            notifications.title,
                                            notifications.message,
                                            notifications.type,
                                            notifications.read,
                                            notifications.auth,
                                            DATE_FORMAT(notifications.created_at,'%Y') as breakpoint,
                                            DATE_FORMAT(notifications.created_at,'%d/%m/%y %k:%i') as date,
                                            users.name as user
                            "))
                            ->leftJoin('users', 'users.id', '=', 'notifications.sender_user_id')
                            ->where(['notifications.user_id' => authUser('id')])
                            ->orderBy('notifications.id', 'desc')->limit($limit)->offset($offset)->get();

            $timeline = [];
            $dataNotifications = [];
            $text = '';
            foreach( $notifications as $notification ){
                $importance = 1;
                $text = '';
                switch ($notification->type) {
                    case 'auth':
                        $icon = 'fa fa-gavel';
                        $color = 'red';
                        if($notification->auth){
                            $text .= '<br><span class="status-pill success mr-2"></span>'.('Autorizada').'<br>';
                        }else{
                            $text .= '<br><span class="status-pill red mr-2"></span>'.('No Autorizada').'<br>';
                        }
                        if( !$notification->read ){
                            $text = '<br><a noty-id="'.$notification->id.'" onclick="return false" class=" open-notification badge badge-danger-inverted" href="#">'._('Pendiente por Autorizar').'</a><br>';
                        }
                        break;
                    case 'text':
                        $color = 'indigo l-900';
                        $icon = 'fa fa-comment';
                        break;
                    case 'action':
                        $color = 'pink';
                        $icon = 'fa fa-share-alt';
                        break;
                    case 'route':
                        $color = 'pink';
                        $icon = 'fa fa-share-alt';
                        break;
                    case 'deploy':
                        $color = 'green';
                        $icon = 'fa fa-exclamation';
                        break;
                }
                $readStatus = (!$notification->read) ?  '<span class="status-pill blue mr-3 tip" title="No Leído"></span>' : '';
                $dataNotifications[$notification->breakpoint]['breakpoint'] = ( $notification->breakpoint == date('Y')) ? 'Este año' :  $notification->breakpoint;
                $dataNotifications[$notification->breakpoint]['history'][] = [
                    'id' => $notification->id,
                    'date' => $notification->date,
                    'icon' => $icon,
                    'story' => '<div class="content-notification open-notification" noty-id="'.$notification->id.'">'.$readStatus.'<mark class="badge"><strong>' . ucfirst($notification->title) .'</strong></mark><br> '. $notification->message.'<br>'.$text.'<span class="help"><em>'._('Enviado por '). ucfirst($notification->user) . '</em></span></div>',
                    'title' => $notification->title,
                    'color' => $color,
                    'importance' => $importance,
                ];
            }

            foreach( $dataNotifications as $notification){
                $timeline[] = $notification;
            }

            return ['success' => true , 'timeline' => $timeline];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => 'Oups... Ha ocurrido un problema: '. $e->getMessage()];
        }
    }

    public function selectRecurrent(Request $request)
    {
        try{
            $notifications = Notification::select(
                DB::raw( "
                            notifications.id,
                            notifications.title,
                            notifications.message,
                            notifications.created_at,
                            users.name as user
            "))
            ->leftJoin('users', 'users.id', '=', 'notifications.sender_user_id')
            ->leftJoin('projects', 'projects.id', '=', 'notifications.project_id')
            ->where([ 'notifications.project_id' => $request->project_id, 'notifications.active' => 1 ])
            ->orderBy('notifications.id', 'desc');
            
            return Datatables::of($notifications)->make(true);
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    /*** DELETE ***/
    public function delete(Request $request)
    {
        try{
            //SET UNACTIVE
            $updateItem = Notification::where(['id'=>$request->id])->firstOrFail();
            $updateItem->active = 0;
            $updateItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'ELIMINATION','name'=>'Notification deleted : '.$updateItem->name,'tablename'=>'notifications','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
}
