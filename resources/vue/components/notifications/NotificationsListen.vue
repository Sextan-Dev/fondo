<template>
    <div data-backdrop="static" data-keyboard="false" class="onboarding-modal modal fade animated" id="comingDeployment" role="dialog" tabindex="-1" style="display:none;">
        <div class="modal-dialog modal-centered" role="document">
            <div class="modal-content text-center">
                <button aria-label="Close"  class="close" data-dismiss="modal" type="button">
                    <span class="os-icon os-icon-close"></span>
                </button>    
                <div class="onboarding-media">
                    <img alt="" :src="appDomain + '/images/bigicon5.png'" width="200px">
                </div>           
                <div   class="onboarding-content with-gradient">
                    <h4 class="onboarding-title">&nbsp;
                        Actualización disponible
                    </h4>                   
                    
                    <div class="onboarding-text row">
                        <div class="col-sm-12 col-centered">
                           
                           <span id="alert-message"></span>
                           
                        </div>                        
                    </div><br>
                    <div class="onboarding-text"><em><span class="help"> <translate>Enviado por el equipo de Soporte</translate></span></em></div>
                </div>                
            </div>
        </div>
    </div>
</template>
<script>
    import jwtToken from '../../helpers/jwt-token';
    import {EventBus} from '../../core/eventBus.js';
    import {api} from "../../config/jwt-routes";
	export default {
        props:['auth','appDomain','siteName'],
        methods: {
            // CALL METHOD PARENT FOR DIPLAY NOTIFICATION IN FOOTER
            displayNoty:async function(notification){
                let vm = this                
                vm.$parent.$emit('showPreviewNotification', notification)
                $("#soundNotification").trigger("play");
            }
        },
        mounted: async function(){
            if (!window.Echo) return;
            let vm = this
            // LISTEN ACTION NOTIFICATIONS
            Echo.channel('actionNotification.' + vm.auth.id)
            .listen('.newAction', (message) => {
                toastr.success(message.message);
            });

            // LISTEN NOTIFICATION FOR ALL USERS COMPANY
            Echo.channel('companyNotification.' + vm.auth.company_id)
            .listen('.notification', (message) => {
                vm.displayNoty(message)
            });

            // LISTEN NOTIFICATION FOR ALL USERS GROUP
            Echo.channel('groupNotification.' + vm.auth.group.id)
            .listen('.notification', (message) => {
                vm.displayNoty(message)         
            });

            // LISTEN NOTIFICATION FOR SPECIFIC USER
            Echo.channel('userNotification.' + vm.auth.id)
            .listen('.notification', (message) => {
               // console.log(message)
                vm.displayNoty(message)                
            });
            
            // LISTEN NOTIFICATION NEW DEPLOYMENT
            Echo.channel('deployment')
            .listen('.newDeployment', (notification) => {
                console.log(notification)
                if(notification.type == 'update'){
                    window.location.href = '/'
                }else{
                    if(notification.type == 'alert'){
                        $('#alert-message').text(notification.message)
                        $('#comingDeployment').modal()
                    }
                }
            });

            
        }
    }
</script>
