<?php
namespace App\Http\Traits;
use App\User;
use App\Notification;

trait NotificationTrait {
    
    public function sendPushNotificaction($notificationConfig)
    {
        try{
            $notification['title'] = $notificationConfig['title'];
            $notification['message'] = $notificationConfig['message'];
            $notification['frequency'] = (isset($notificationConfig['frequency']))? $notificationConfig['frequency']: null ;
            $notification['date'] = (isset($notificationConfig['date']))? $notificationConfig['date']: null ;
            $notification['project_id'] = (isset($notificationConfig['project_id']))? $notificationConfig['project_id']: null;
            $notification['sender'] = authUser('name');
            $notification['sender_user_id'] = authUser('id');
            $notification['content_id'] = null;
            if(array_key_exists('url', $notificationConfig)  ){
                $notification['url'] = $notificationConfig['url'];
            }            
            switch ($notificationConfig['type']) {
                case 'auth':                    
                    $notification['type'] =_('Solicitud de autorización');
                    $notification['content_id'] = array_key_exists('content_id', $notificationConfig)?  $notificationConfig['content_id']: null;
                    break;
                case 'text':                    
                    $notification['type'] = _('Notificación');
                    break;
                case 'action':                    
                    $notification['type'] = _('Notificación');
                    break;
                case 'route':                    
                    $notification['type'] = _('Notificación');
                    $notification['route'] = $notificationConfig['route'];                    
                    break;
                case 'recurrent':                    
                    $notification['type'] = _('Notificación');                
                    break;
                case 'deploy':                    
                    $notification['type'] = _('Notificación');   
                    $notification['sender'] = 'Support'; 
                    $notification['sender_user_id'] = 1;           
                    break;
                default:
                    return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : Type notification not defined")];
                    break;
            }

            switch ($notificationConfig['addressee']) {
                case 'company':
                    $users = User::where(['company_id' => $notificationConfig['channel_id'], 'active' => 1])->get();
                    break;
                case 'group':
                    $users = User::where(['group_id' => $notificationConfig['channel_id'], 'active' => 1])->get();
                    break;
                case 'user':
                    $users = User::where(['id' => $notificationConfig['channel_id']])->get();
                    break;
                case 'all':
                    $users = User::where(['active' => 1])->get();
                    break;
                default:
                    return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : Addressee notification not defined")];
                    break;
            }

            try{         
                //debugger($notificationConfig);      
                foreach ($users as $key => $user) {
                    $dataNotifications = $notification;                    
                    $dataNotifications['type'] = $notificationConfig['type'];
                    if($dataNotifications['type'] == 'route'){
                        $dataNotifications['url'] = json_encode($notificationConfig['route']);
                    }

                    $dataNotifications['user_id'] = $user->id;
                    unset($dataNotifications['channel']);
                    $store = new Notification;
                    $store->fill( $dataNotifications );
                    $store->save();
                    $notification['id'] = $store->id; 
                    $notification['channel'] = 'userNotification.' . $user->id;
                    event(new \App\Events\CustomNotification( (object) $notification ));
                }        
                
            }catch(\Exceptio $e){
                return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
            }
           
            return ['success' =>true , 'msg'=>_('La notificación ha sido enviada exitosamente.')];
           
           
        }catch(\Exceptio $e){
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
}