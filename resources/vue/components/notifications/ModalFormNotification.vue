<template>
    <div data-backdrop="static" data-keyboard="false" class="onboarding-modal modal fade animated" id="formNotificationModal" role="dialog" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-centered" role="document">
            <div class="modal-content text-center">
                <button aria-label="Close"  @click="closeForm()" class="close" data-dismiss="modal" type="button">
                    <span class="close-label" v-translate>Cerrar</span>
                    <span class="os-icon os-icon-close"></span>
                </button>
                <div class="onboarding-side-by-side">
                    <div class="onboarding-media">
                        <img alt="" :src="appDomain + '/images/bigicon6.png'" width="200px">
                    </div>
                    <div v-if="!successMesaage" class="onboarding-content with-gradient">
                        <h4 class="onboarding-title" >Nueva Notificación</h4>
                        <div class="onboarding-text" >Esta sección te permitira crear una notificación y enviarla a los usuarios que pertenezacan a una compañia, a un grupo ó a un usuario en especifico.</div>
                        <form @submit.prevent="formSubmit">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="" style="font-size: 16px;" v-translate>Titulo</label>
                                        <input name="title" v-model="form.title" :class="{'input': true, 'is-invalid': errors.has('title') }"  v-validate="'required|max:40'" class="form-control" placeholder="..." type="text" value="">
                                        <div class="invalid-feedback" v-show="errors.has('title')" >{{ errors.first('title') }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for=""  style="font-size: 16px;" v-translate>Tipo de la Notificación </label>
                                        <select name="type" v-model="form.type" @change="triggerAddress" :class="{'input': true, 'is-invalid': errors.has('type') }"  data-vv-as=" " v-validate="'required'" class="form-control">
                                            <option value="" v-translate>...</option>
                                            <option value="auth" v-translate>Autorización</option>
                                            <option value="text" v-translate>Informativo</option>
                                            <option value="action" v-translate>Acción</option>
                                        </select>
                                        <div class="invalid-feedback" v-show="errors.has('type')" >{{ errors.first('type') }}</div>
                                    </div>
                                </div>
                            </div>                                                      
                           
                                                                             

                            <div class="row">
                                <div class="col-sm-12 col-centered">
                                    <div class="form-group" >
                                        <label><translate>Destinatario</translate></label>
                                        <br>
                                        <!-- <div class="form-check d-inline-block mt-1 mr-3" v-if="form.type!='auth'">
                                            <label class="form-check-label"> 
                                                <input v-model="form.addressee" data-vv-as=" " v-validate="'required'" checked="checked" class="form-check-input" name="addressee" value="company" type="radio"><translate>Compañia</translate>
                                            </label>
                                        </div> -->
                                        <div class="form-check d-inline-block mt-1 mr-3" v-if="form.type!='auth'">
                                            <label class="form-check-label"> 
                                                <input v-model="form.addressee" data-vv-as=" " v-validate="'required'" checked="" class="form-check-input" name="addressee" value="group" type="radio"><translate>Grupo</translate>                                                
                                            </label>
                                        </div>
                                        <div class="form-check d-inline-block mt-1 mr-3">
                                            <label class="form-check-label">
                                                <input  v-model="form.addressee" data-vv-as=" " v-validate="'required'" checked="" class="form-check-input" name="addressee" value="user" type="radio"><translate>Usuario</translate>
                                            </label>
                                        </div>
                                        <div class="invalid-feedback" v-show="errors.has('addressee')" >{{ errors.first('addressee') }}</div>
                                    </div>
                                </div>
                            </div>                            

                            <div class="row" >
                                <div class="col-sm-12 " >
                                    <div class="form-group" >
                                        <template v-if="form.addressee=='company'">
                                            <label class="mb-0" v-translate>Destinatario</label><br>
                                            <select   v-model="form.channel_id" name="channel_id" class="form-control " :class="{'input': true, 'is-invalid': errors.has('channel_id') }"  data-vv-as=" " v-validate="'required'">
                                                <option v-for="(item, index) in companies" :key="index" :value="item.id" >
                                                    {{item.slug+' - '+item.name}}
                                                </option>		 
                                            </select>
                                        </template>
                                        <template v-if="form.addressee=='group'">
                                            <label class="mb-0" v-translate>Destinatario</label><br>
                                            <select  v-model="form.channel_id" name="channel_id" class="form-control " :class="{'input': true, 'is-invalid': errors.has('channel_id') }"  data-vv-as=" " v-validate="'required'">
                                                <option v-for="(item, index) in groups" :key="index" :value="item.value" >
                                                    {{item.label}}
                                                </option>		 
                                            </select>
                                        </template>
                                        <template v-if="form.addressee=='user'">
                                            <label class="mb-0" v-translate>Destinatario</label><br>
                                            <select  v-model="form.channel_id" name="channel_id" class="form-control " :class="{'input': true, 'is-invalid': errors.has('channel_id') }"  data-vv-as=" " v-validate="'required'">
                                                <option v-for="(option, indexOption ) in users" :value="option.value" :key="indexOption">
                                                    {{ option.label }}
                                                </option>	 
                                            </select>
                                        </template>
                                        <div class="invalid-feedback" v-show="errors.has('channel_id')" >{{ errors.first('channel_id') }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-12 col-centered">
                                    <div class="form-group" >
                                        <label for=""  style="font-size: 16px;" v-translate>Mensaje</label>
                                        <textarea name="message" v-model="form.message" :class="{'input': true, 'is-invalid': errors.has('message') }"  data-vv-as=" " v-validate="'required'" class="form-control" ></textarea>
                                        <div class="invalid-feedback" v-show="errors.has('message')" >{{ errors.first('message') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" v-if="form.type=='action'">
                                <div class="col-sm-12 col-centered">
                                    <div class="form-group">
                                        <label for=""  style="font-size: 16px;" v-translate>Url</label>
                                        <input type="url" name="url" v-model="form.url" :class="{'input': true, 'is-invalid': errors.has('url') }"  v-validate="'required|url'" class="form-control" />
                                        <div class="invalid-feedback" v-show="errors.has('url')" >{{ errors.first('url') }}</div>
                                    </div>
                                </div>
                            </div>
                            <!-- BUTTONS FORM -->
                            <div class="form-buttons-w">
                                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                    <span v-show="loading"><translate>Enviando</translate>...</span>
                                    <span v-show="!loading"><translate>Enviar</translate></span>
                                </button>
                                <button class=" btn btn-light" data-dismiss="modal" type="button"  @click="closeForm()"  v-translate> Cancelar</button>
                            </div>
                            <!-- END BUTTONS FORM -->
                        </form>
                    </div>
                    <div v-else>
                        <div v-if=" form.type!='recurrent'" class="onboarding-content with-gradient">
                            <h4 class="onboarding-title">Notificación enviada</h4>
                            <div class="onboarding-text">Se ha realizado el envio de la Notificación que acabas de crear, en breve los usuarios de la compañia seleccionada, grupo o usuario seleccionado la estaran recibiendo.</div>
                            <img :src="appDomain + '/images/success.png'" style="max-width: 31%;" alt="">
                        </div>
                        <div v-else class="onboarding-content with-gradient">
                            <h4 class="onboarding-title">Notificación Registrada</h4>
                            <div class="onboarding-text">Se ha realizado el registro de Notificación y sera enviada de acuerdo a la frecuencia programada, en breve los usuarios de la compañia seleccionada, grupo o usuario seleccionado la estaran recibiendo.</div>
                            <img :src="appDomain + '/images/bigicon2.png'" style="max-width: 31%;" alt="">
                        </div>
                    </div>
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
                groups:{},
                users:{},
                form:{
                    title:'',
                    type:'',
                    message:'',
                    channel_id:null,
                    frequency: 1,    
                    project_id:null,                
                    addressee:'user',
                    url:null
                }
			}
        },
        props:['companies'],
        computed: {
            route: function(){
                return this.$route
            }
        },
        watch:{
            route: async function(newValue, oldValue){
                if( newValue.name == 'ProjectForm' ){
                    this.form.type = 'recurrent'
                    this.form.project_id = Number(newValue.params.id)||null
                }else{
                    this.form.type = ''
                    this.form.project_id = null
                }
            }
        },
        methods:{
            data:async function(){
                var vm = this;
                await axios.post(api.dataNotifications)
                .then(function (response) {
                    if(response.data.success){
                        vm.groups = response.data.groups
                        vm.users = response.data.users
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    console.log(error);
                })
            },
            triggerAddress(){
                if(this.form.type=='auth'){
                    this.form.addressee='user'
                }
            },
            closeForm(){
                let vm = this
                var errors = vm.errors
                vm.successMesaage = false
                vm.form.title=''
                vm.form.url=null
                vm.form.message=''
                vm.form.channel_id=null
                vm.form.addressee='user'
                if(vm.$route.name != 'ProjectForm'){
                    vm.form.type=''
                    vm.form.project_id=null
                }
                setTimeout(function () {
                    errors.clear()
                }, 1)
            },
            formSubmit:async function() {
                let vm = this
                var errors = vm.errors;
                this.$validator.validateAll().then((result) => {
                    if (result){
                        vm.loading = true
                        axios.post(api.sendNotification, vm.form )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);
                                vm.form.title=''
                                vm.form.url=null
                                vm.form.message=''
                                vm.form.channel_id=null
                                vm.form.project_id=null
                                vm.form.addressee='user'
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
            vm.data()
        }
    }
</script>