<template>
    <div data-backdrop="static" data-keyboard="false" class="onboarding-modal modal fade animated" id="showNotificationModal" role="dialog" tabindex="-1" style="display:none;">
        <div class="modal-dialog modal-centered" role="document">
            <div class="modal-content text-center">
                <button aria-label="Close" @click="closeForm()" class="close" data-dismiss="modal" type="button">
                    <span class="os-icon os-icon-close"></span>
                </button>               
                <div  v-if="!successMesaage"  class="onboarding-content with-gradient">
                    <h4 class="onboarding-title" >
                        <i v-if="notification.type=='deploy'" class="fa fa-exclamation-circle mr-2"></i>
                        <i v-else class="icon-bubbles mr-2"></i> {{notification.title}}&nbsp;
                    </h4>
                    <div class="onboarding-text" v-safe-html="notification.message"></div>
                    
                    <div class="onboarding-text row">
                        <div class="col-sm-12 col-centered">
                           <template v-if="notification.type=='action'">
                               <div class="onboarding-text">Este mensaje tiene un vinculo incluido, da click <a target="_blank" :href="notification.url">aqui</a> para abrir el enlance</div>
                           </template>
                           <template v-if="notification.type=='route'">
                               <div class="onboarding-text">
                                     <button @click.prevent="openRoute( notification.route )" class="btn pl-3 pr-3 btn-primary">
                                        <i class="icon-feather-arrow-right-circle mr-2"></i> {{notification.route.linkMask}}
                                    </button>
                               </div>
                                   
                           </template>
                           <template v-if="notification.type=='auth' && notification.read!=1">
                                <form @submit.prevent="formSubmit">
                                    <div class="form-group text-center" >
                                        <label><translate>Autorizas a</translate>&nbsp;{{notification.sender.name}}? </label>
                                        <br>
                                        <div class="form-check d-inline-block mt-1 mr-3">
                                            <label class="form-check-label"> 
                                                <input v-model="form.auth" data-vv-as=" " v-validate="'required'" checked="checked" class="form-check-input" name="auth" value="1" type="radio"><translate>Si Autorizo</translate>
                                            </label>
                                        </div>
                                        <div class="form-check d-inline-block mt-1 mr-3">
                                            <label class="form-check-label"> 
                                                <input v-model="form.auth" data-vv-as=" " v-validate="'required'" checked="" class="form-check-input" name="auth" value="0" type="radio"><translate>No Autorizo</translate>                                                
                                            </label>
                                        </div>
                                        <div class="invalid-feedback" v-show="errors.has('auth')" >{{ errors.first('auth') }}</div>

                                    </div>
                                    <!-- BUTTONS FORM -->
                                    <div class="form-buttons-w text-center">
                                        <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                            <span v-show="loading"><translate>Respondiendo</translate>...</span>
                                            <span v-show="!loading"><translate>Responder</translate></span>
                                        </button>
                                        <button class=" btn btn-light" data-dismiss="modal" type="button"  @click="closeForm()"  v-translate> Responder despues</button>
                                    </div>
                                </form>
                           </template>
                           <template v-if="notification.type=='auth' && notification.read==1">
                               <p v-if="notification.auth"><span class="status-pill succes mr-2"></span><translate>Autorizada</translate></p>
                               <p v-else><span  class="status-pill red mr-2"></span><translate>No Autorizada</translate></p>
                           </template>
                        </div>
                        
                    </div><br>
                    <div class="onboarding-text"><em><span class="help"> <translate>Enviado por </translate>{{notification.sender.name}}</span></em></div>
                </div>
                <div v-else class="onboarding-content with-gradient">
                    <h4 class="onboarding-title" v-translate>Respuesta enviada</h4>
                    <img :src="appDomain + '/images/success.png'" style="max-width: 31%;" alt="">
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import {siteName,appDomain} from '../../config/app';
    import {mapState} from 'vuex';
    import jwtToken from '../../helpers/jwt-token';
    import {EventBus} from '../../core/eventBus.js';
    import {api} from "../../config/jwt-routes";
    export default {
        data() {
			return {
                loading:false,
                successMesaage:false,
                siteName: siteName,
                appDomain: appDomain, 
                form:{
                    auth:1,
                    id:null
                }           
			}
        },
        props:['notification'],
        methods:{
            closeForm(){
                let vm = this
                var errors = vm.errors
                vm.successMesaage = false
                setTimeout(function () {
                    errors.clear()
                }, 1)
            },
            openRoute:async function(params){
                //console.log('...')
                await this.$router.push({name:params.name,params: {id: params.id} })                                
            },
            formSubmit:async function() {
                let vm = this
                var errors = vm.errors;
                this.$validator.validateAll().then((result) => {
                    if (result){
                        vm.loading = true
                        vm.form.id = vm.notification.id
                        axios.post(api.responseAuthNotification, vm.form )
                        .then(async function (response) {
                            if(response.data.success){
                                vm.$parent.$emit('readNotification', vm.form.id)
                                setTimeout(function () {
                                    errors.clear()
                                }, 1)
                                vm.successMesaage = true
                            }else{
                                toastr.error(response.data.msg);
                            }
                            vm.loading=false                      
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            vm.loading=false 
                            console.log(error);
                        })
                    }
                })
            }
        },
        mounted: async function(){
            let vm = this
        }
    }

</script>