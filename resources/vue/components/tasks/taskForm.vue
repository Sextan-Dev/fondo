<template>
    <div class="app-email-w b-t  b-t" :class="{ 'compact-side-menu' : isCompat }">
        <div class="app-email-i">
            <div class="ae-side-menu">
                <div class="aem-head"><a @click.prevent="isCompat = !isCompat" class="ae-side-menu-toggler" href="#"><i class="os-icon os-icon-hamburger-menu-2"></i></a></div>
                <ul class="ae-main-menu">
                    <li class="active">
                        <a href="#"  @click.prevent="changeComponent('general', $event)" >
                            <i class="os-icon os-icon-phone-21"></i>
                            <span v-translate>General</span>
                        </a>
                    </li>  
                    <!-- <li>
                        <a href="#" @click.prevent="changeComponent('volunteers',$event)" >
                            <i class="fa fa-handshake-o"></i>
                            <span v-translate>Voluntarios</span>
                        </a>
                    </li>                                    -->
                    <li>
                        <a href="#" @click.prevent="changeComponent('documents',$event)">
                            <i class="os-icon os-icon-ui-44"></i>
                            <span v-translate>Documentos</span>
                        </a>
                    </li>          
                    <!-- <li>
                        <a href="#" @click.prevent="changeComponent('operations',$event)">
                            <i class="icon-wallet"></i>
                            <span style="font-size: 13px;" v-translate>Edo. de Cuenta</span>
                        </a>
                    </li> -->
                    <!-- <li>
                        <a href="#" @click.prevent="changeComponent('tranfers',$event)">
                            <i class="icon-feather-repeat"></i>
                            <span style="font-size: 12px;" v-translate>Transferencias</span>
                        </a>
                    </li> -->
                    <!-- <li>
                        <a href="#" @click.prevent="changeComponent('orderCollect',$event)">
                            <i class="fa fa-file-text-o"></i>
                            <span style="font-size: 13px;" v-translate>Orden de Cobro</span>
                        </a>
                    </li> -->
                    <!-- <li>
                        <a href="#" @click.prevent="changeComponent('orderPay',$event)">
                            <i class="fa fa-file-text-o"></i>
                            <span style="font-size: 13px;" v-translate>Orden de Pago</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="changeComponent('indicators',$event)" >
                            <i class="fa fa-bar-chart"></i>
                            <span v-translate>Indicadores</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="changeComponent('contacts',$event)" >
                            <i class="icon-feather-users"></i>
                            <span v-translate>Beneficiarios</span>
                        </a>
                    </li> -->
                  
                    <!-- <li>
                        <a href="#" @click.prevent="changeComponent('documents',$event)">
                            <i class="os-icon os-icon-ui-44"></i>
                            <span v-translate>Documentos</span>
                        </a>
                    </li>            -->
                </ul>
                <div class="ae-labels m-0" v-if="this.$route.params.id">
                    <a href="#" class="ae-label ae-label-red pl-0">
                        <span class="label-value help" v-translate>Creación:</span>
                        <span class="bold label-value help" >&nbsp;&nbsp;&nbsp;{{form.created_at}}</span>
                    </a>
                    <a href="#" class="ae-label ae-label-red pl-0"  >
                        <span class="label-value help" v-translate>Modificada:</span>
                        <span class="bold label-value help">{{form.updated_at}}</span>
                    </a>
                </div>
            </div>
            
            <div class="ae-content">
                <div class="ae-top-menu">
                    <div v-if="form.name" class="value-pair">
                        <div class="label" v-translate>Actividad:</div>
                        <div class="value bolder">{{form.name}}</div>
                    </div>
                    <div v-if="form.status" class="value-pair d-none d-sm-inline">
                        <div class="label" v-translate>Estatus:</div>
                        <div class="value badge " :class="status[form.status].class || 'badge-warning'">{{status[form.status].name}}</div>
                    </div>
                    <div v-if="projectName" class="value-pair d-none d-lg-inline">
                        <div class="label" v-translate>Proyecto:</div>
                        <router-link :to="{name:'ProjectForm',params:{id:form.project_id}}">
                            <div class="value badge badge-primary">{{projectName}}</div>
                        </router-link>
                    </div>
                    <div v-if="respName" class="value-pair d-none d-xxl-inline">
                        <div class="label" v-translate>Responsable:</div>
                        <div class="value bolder">{{respName}}</div>
                    </div>
                    <div v-if="form.updated_at" class="value-pair d-none">
                        <div class="label" v-translate>Modificado el :</div>
                        <div class="value">{{form.updated_at}}</div>
                    </div>
                </div>
                <div class="aec-full-message-w" v-show="show == 'general'" >
                    <taskGeneral :form="form" :configPlugins="configPlugins"></taskGeneral>
                </div>
                <div class="aec-full-message-w " v-if="show == 'orderPay'"  >
                    <taskOrderPay  :task="form" :appDomain="appDomain" :auth="auth"></taskOrderPay>
                </div>
                <div class="aec-full-message-w " v-if="show == 'orderCollect'"  >
                    <taskOrderCollect  :appDomain="appDomain" :task="form" :auth="auth"></taskOrderCollect>
                </div>
                <div class="aec-full-message-w " v-if="show == 'operations'"  >
                    <taskOperations  :form="form"></taskOperations>
                </div>
                <div class="aec-full-message-w " v-if="show == 'indicators'"  >
                    <taskIndicators :siteName="siteName" :appDomain="appDomain" :auth="auth"   :form="form"></taskIndicators>
                </div>
                <div class="aec-full-message-w " v-if="show == 'tranfers'"  >
                    <taskTranfers :siteName="siteName" :appDomain="appDomain" :auth="auth"   :form="form"></taskTranfers>
                </div>
                <div class="aec-full-message-w " v-if="show == 'contacts'"  >
                    <taskContacts  :form="form"></taskContacts>
                </div>
                
                <div class="aec-full-message-w " v-if="show == 'volunteers'"  >
                    <taskVolunteers :siteName="siteName" :appDomain="appDomain" :auth="auth"   :form="form"></taskVolunteers>
                </div>
                <div class="aec-full-message-w " v-show="show == 'documents'" >
                    <taskDocuments :form="form" ></taskDocuments>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import taskGeneral from "./taskGeneral";
    import taskOperations from "./taskOperations";
    import taskTranfers from "./taskTranfers";
    import taskOrderPay from "./taskOrderPay";
    import taskOrderCollect from "./taskOrderCollect";
    import taskContacts from "./taskContacts";
    import taskVolunteers from "./taskVolunteers";
    import taskIndicators from "./taskIndicators";
    import taskDocuments from "./taskDocuments";
	export default {
        props:[ 'auth','appDomain','siteName','configPlugins'],
        data(){
            return {
                task_id: null,
                projectName: null,
                respName: null,
                show:'general',
                isCompat:false,
                contactsList:{},
                created_at:  moment().format('YYYY-MM-DD HH:mm:ss'),
                form : {
                    id:'',
                    project_id:null,
                    name:null,
                    org_beneficiary:null,
                    slug:null,
                    date_from:moment().format('YYYY-MM-DD'),
                    date_to:moment().format('YYYY-MM-DD'),
                    user_id:null,
                    type:null,
                    category:null,
                    tags:null,
                    description:'',
                    description_html:'',
                    comments:[],
                    milestone:moment().format('YYYY-MM-DD'),
                    reminders:[],
                    files:null,
                    proof:null
                },
                status:{
					'pending' :{name : 'Pendiente', class: 'badge-warning'},
					'approved':{name : 'Aprobada',  class: 'badge-info'},
					'working':{name : 'En curso',  class: 'badge-primary'},
					'done':{name : 'Finalizada',  class: 'badge-success'},
					'canceled': {name : 'Cancelada', class: 'badge-red'}
				}     
            }
        },
        methods:{
             // Show User data for Edit
            showItem:async function( id ){
                var vm = this;                 
                if(id){
                    await axios.post(api.showTask, {id: id })
                    .then(function (response) {
                        if(response.data.success){
                            let data = response.data.data
                            data.zone_impact_1 = _.isNull(data.zone_impact_1) ? [] : data.zone_impact_1
                            data.zone_impact_2 = _.isNull(data.zone_impact_2) ? [] : data.zone_impact_2
                            data.zone_impact_3 = _.isNull(data.zone_impact_3) ? [] : data.zone_impact_3
                            data.description_html = (_.isNull(data.description_html)) ? '' : data.description_html
                            vm.form = data
                            vm.form.files = JSON.parse(vm.form.files)                        
                            vm.form.proof = JSON.parse(vm.form.proof)
                            if(vm.$route.params.action=='copy'){
                                vm.form.comments=[]
                                vm.form.reminders=[]
                            } 
                            vm.form.milestone = moment().format('YYYY-MM-DD')                     
                        }else{
                            
                        }
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                    })
                }
            },
            updateForm(form) {
                let vm = this
                vm.$route.params.id = form.id
                vm.form= form
                vm.form.zone_impact_1 = _.isNull(vm.form.zone_impact_1) ? [] : vm.form.zone_impact_1
                vm.form.zone_impact_2 = _.isNull(vm.form.zone_impact_2) ? [] : vm.form.zone_impact_2
                vm.form.zone_impact_3 = _.isNull(vm.form.zone_impact_3) ? [] : vm.form.zone_impact_3
                vm.form.files = JSON.parse(form.files)                        
                vm.form.proof = JSON.parse(form.proof)  
            },
            updateId(id) {
                let vm = this
                vm.$route.params.id = id
                vm.$route.params.action = null
                vm.form.id = id
            },
            updateReminder(index) {
                let vm = this
                vm.form.reminders.splice(index,1);
            },
            updateDocuments(documents) {
                let vm = this
                vm.form.files = JSON.parse(documents.files)                        
                vm.form.proof = JSON.parse(documents.proof)
                
            },
            changeComponent: function(component, event){
                if( component == this.show ){
                    return
                }
                if( this.$route.params.id == null || this.$route.params.action=='copy'){
                    toastr.error("Es necesario completar la información general para poder completar las demas secciones.");
                }else{
                    this.show = component
                    $('.ae-main-menu li').removeClass('active')               
                    $(event.target).parents('li:first').addClass('active')
                }
            }
        },
        components:{
            taskGeneral : taskGeneral,
            taskOperations : taskOperations,
            taskContacts : taskContacts,
            taskOrderPay : taskOrderPay,
            taskVolunteers : taskVolunteers,
            taskIndicators : taskIndicators,
            taskDocuments : taskDocuments,
            taskOrderCollect : taskOrderCollect,
            taskTranfers:taskTranfers
        },
        mounted: async function() {
            var vm=this;
            
            await vm.$on('updateForm', function(value){
                vm.updateForm(value)
            });

            await vm.$on('updateId', function(value){
                vm.updateId(value)
            });

            await vm.$on('updateDocuments', function(value){
                vm.updateDocuments(value)
            });
            
            await vm.$on('updateReminder', function(value){
                vm.updateReminder(value)
            });

            //vm.$route.params.id = 22

            if(vm.$route.params.id){
                await vm.showItem(vm.$route.params.id);
            } else if (vm.$route.params.project) {
                vm.form.project_id = vm.$route.params.project;
            }
                      

            //SET HEADER
            setTimeout(function () {
                vm.projectName = $('select[name="project_id"] option:selected').text();
                vm.respName = $('select[name="user_id"] option:selected').text();
            },200);
            await vm.$on('updateHeader', function(){
                vm.projectName = $('select[name="project_id"] option:selected').text();
                vm.respName = $('select[name="user_id"] option:selected').text();
            });

            $('.date').daterangepicker(
            {   singleDatePicker: true,
                autoUpdateInput: true,
                autoApply: true,
                minDate: moment().format('YYYY-MM-DD'),
                locale: {
                    format: 'YYYY-MM-DD'
                },
            });
            $('.date').on('apply.daterangepicker','hide.daterangepicker', function(ev, picker) {
                vm.form.milestone = picker.startDate.format('YYYY-MM-DD')
            });
            $('.date').on('hide.daterangepicker', function(ev, picker) {
                vm.form.milestone = picker.startDate.format('YYYY-MM-DD')
            });

            vm.loading = false;
        }
    }
</script>

<style>
.breadcrumb{
    display:none;
}
</style>