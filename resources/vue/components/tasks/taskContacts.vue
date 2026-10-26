<template>
    <div class="row task-form">
        <div class="col-md-12">
            <form id="formContacts" @submit.prevent="formSubmit" novalidate="true">

                <h5 class="form-header"><i class="icon-feather-users m-r-10 mr-2"></i> <translate>Beneficiarios</translate></h5>
                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <legend><span v-translate>Beneficiarios Directos <i class="fa icon-feather-info" data-toggle="popover" title="Nivel 1" data-content="Quien recibe un apoyo directo"></i> </span></legend>
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title"><translate>Beneficiarios Directos</translate></div>
                                <div class="balance-value">
                                    {{form.benef_1||0}}
                                    <span v-if="form.target_benef_1>0">
                                        <span class="trending text-success" v-if="form.benef_1/form.target_benef_1>0.5"> {{Math.round(form.benef_1*100/form.target_benef_1)}}% <translate>Meta</translate> </span>
                                        <span class="trending text-danger" v-else> {{Math.round(form.benef_1*100/form.target_benef_1)}}% <translate>Meta</translate> </span>
                                    </span>
                                </div>
                                <div v-if="form.target_benef_1>0">
                                    <div class="os-progress-bar danger" :class="{'success':(form.benef_1/form.target_benef_1>0.5)}">
                                        <div class="bar-level-1" style="width: 100%">
                                            <div class="bar-level-3" :style="'width:'+Math.round(form.benef_1*100/form.target_benef_1)+'%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 pr-0">
                                <label class="smaller" v-translate>Real</label><br>
                                <input type="number" name="benef_1" v-model="form.benef_1" class="form-control md-6" placeholder="..." value="" >
                            </div>
                            <div class="col-1 p-0"><h1 class="pt-3 text-center font-weight-light">/</h1></div>
                            <div class="col-5 pl-0">
                                <label class="smaller" v-translate>Meta</label><br>
                                <input type="number" name="target_benef_1" v-model="form.target_benef_1" class="form-control md-6" placeholder="..." value="" >
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <legend><span> <translate>Beneficiarios Indirectos</translate> <i class="fa icon-feather-info" data-toggle="popover" title="Nivel 2" data-content="Quien recibe un apoyo preventivo o beneficia indirectamente del apoyo"></i> </span></legend>
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title"><translate>Beneficiarios Indirectos</translate></div>
                                <div class="balance-value">
                                    {{form.benef_2||0}}
                                    <span v-if="form.target_benef_2>0">
                                        <span class="trending text-success" v-if="form.benef_2/form.target_benef_2>0.5"> {{Math.round(form.benef_2*100/form.target_benef_2)}}% <translate>Meta</translate> </span>
                                        <span class="trending text-danger" v-else> {{Math.round(form.benef_2*100/form.target_benef_2)}}% <translate>Meta</translate> </span>
                                    </span>
                                </div>
                                <div v-if="form.target_benef_2>0">
                                    <div class="os-progress-bar danger" :class="{'success':(form.benef_2/form.target_benef_2>0.5)}">
                                        <div class="bar-level-1" style="width: 100%">
                                            <div class="bar-level-3" :style="'width:'+Math.round(form.benef_2*100/form.target_benef_2)+'%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 pr-0">
                                <label class="smaller" v-translate>Real</label><br>
                                <input type="number" name="benef_2" v-model="form.benef_2" class="form-control md-6" placeholder="..." value="" >
                            </div>
                            <div class="col-1 p-0"><h1 class="pt-3 text-center font-weight-light">/</h1></div>
                            <div class="col-5 pl-0">
                                <label class="smaller" v-translate>Meta</label><br>
                                <input type="number" name="target_benef_2" v-model="form.target_benef_2" class="form-control md-6" placeholder="..." value="" >
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-md-4">
                        <legend><span> <translate>Beneficiarios Nivel 3</translate> <i class="fa icon-feather-info" data-toggle="popover" title="Nivel 3" data-content="Quien recibe información de campañas o de sensibilización"></i> </span></legend>
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title"><translate>Beneficiarios Nivel 3</translate></div>
                                <div class="balance-value">
                                    {{form.benef_3||0}}
                                    <span v-if="form.target_benef_3>0">
                                        <span class="trending text-success" v-if="form.benef_3/form.target_benef_3>0.5"> {{Math.round(form.benef_3*100/form.target_benef_3)}}% <translate>Meta</translate> </span>
                                        <span class="trending text-danger" v-else> {{Math.round(form.benef_3*100/form.target_benef_3)}}% <translate>Meta</translate> </span>
                                    </span>
                                </div>
                                <div v-if="form.target_benef_3>0">
                                    <div class="os-progress-bar danger" :class="{'success':(form.benef_3/form.target_benef_3>0.5)}">
                                        <div class="bar-level-1" style="width: 100%">
                                            <div class="bar-level-3" :style="'width:'+Math.round(form.benef_3*100/form.target_benef_3)+'%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 pr-0">
                                <label class="smaller" v-translate>Real</label><br>
                                <input type="number" name="benef_3" v-model="form.benef_3" class="form-control md-6" placeholder="..." value="" >
                            </div>
                            <div class="col-1 p-0"><h1 class="pt-3 text-center font-weight-light">/</h1></div>
                            <div class="col-5 pl-0">
                                <label class="smaller" v-translate>Meta</label><br>
                                <input type="number" name="target_benef_3" v-model="form.target_benef_3" class="form-control md-6" placeholder="..." value="" >
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- TABLE DYNAMIC CONTACTS -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group input-sm">
                            <legend><span v-translate>Registro de Beneficiarios</span></legend>
                            <tableDynamic :dataTable="contacts"></tableDynamic>      
                        </div>
                    </div>
                </div>
                <div class="form-buttons-w">
                    <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                        <span v-show="loading"><translate>Guardando</translate>...</span>
                        <span v-show="!loading"><translate>Guardar</translate></span>
                    </button>
                    <button class=" btn btn-light" type="button" href="" @click.prevent="$router.push({name:'TaskList'})" v-translate> Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
	import {appDomain} from '../../config/app';
    import {api} from "../../config/jwt-routes";
    // Import component tableDynamic
    import tableDynamic from '../../helpers/tableDynamic'
	export default {
        data(){
            return {
                loading : true,
                appDomain: appDomain,
                contacts:{
                    name:'contacts',
                    rowsDelete :[],
                    columns:{
                        contact:'Nombre',
                        email:'Correo Electronico',
                        age:'Edad',
                        gender:'Genero',
                        comment:'Comentario'
                    },
                    required:[ 'name' ,'email'],
                    defineTypeElement: { 
                                            'age':{type:'number'}, 
                                            'contact' :  { 
                                                type: 'select' , data:{}, action:'actionParent'
                                            },                        
                                            'gender':{
                                                type:'select', 
                                                data:  [{ 'value':'m', 'label':'M' },{ 'value':'f', 'label':'F' }],
                                                taggable:false
                                            }
                                        },
                    rows:[]
                },
                
            }
        }, 
        props:[ 'form'],
        components:{
            'tableDynamic' : tableDynamic
        },
        methods:{
            actionParent:function(){
                console.log('aqui')
            },
            // GET CONTACTS LIST
            data:async function(){
                let vm = this
                await axios.post(api.select2Contact)
                .then(async function (response) {
                    if(response.data.success){
                        vm.contacts.defineTypeElement.contact.data = response.data.data                  
                    }else{
                        toastr.error(response.data.msg);
                    }        
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })    
            },
            // GET REALTION BEETWEN CLIENTS AND TASK
            pivot: async function(){                           
                let vm = this               
                axios.post(api.selectTaskPivot, {id: this.$route.params.id , type:'Beneficiario'})
                .then(async function (response) {
                    if(response.data.success){
                        vm.contacts.rows = response.data.data                  
                    }else{
                        toastr.error(response.data.msg);
                    }        
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })    
            },
            
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading=true;
                        axios.post(api.updateTaskContacts, {form : this.form, contacts : this.contacts.rows})
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);
                                vm.loading=false;        
                            }else{
                                toastr.error(response.data.msg);
                                vm.loading=false;        
                            }        
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                            vm.loading=false;        
                        })                            
                    }                    
                })
            }
        },
        mounted: async function() {
            let vm = this
            // SELECT2 OBTAIN DATA OF TASK_FORM
            await vm.data()
            
            // IF EXIST ID TASK
            if(this.$route.params.id || this.form.id!=''){
                await vm.pivot()
            }
            this.loading = false
            
        },
        created:function(){
            let vm = this
           
        }
        
    }

</script>
