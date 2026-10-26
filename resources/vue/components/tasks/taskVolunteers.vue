<template>
    <div class="row task-form">
        <div class="col-md-12">
            <form id="formContacts" @submit.prevent="formSubmit" novalidate="true">

                <h5 class="form-header"><i class="fa fa-handshake-o m-r-10 mr-2"></i> <translate>Voluntarios</translate></h5>
                <hr>

                <!-- TABLE DYNAMIC CONTACTS -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group input-sm">
                            
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
    import {api} from "../../config/jwt-routes";
    // Import component tableDynamic
    import tableDynamic from '../../helpers/tableDynamic'
	export default {
        data(){
            return {
                loading : true,
                contacts:{
                    name:'volunteers',
                    rowsDelete :[],
                    columns:{
                        contact:'Nombre',
                        email:'Correo Electronico',
                        age:'Edad',
                        gender:'Genero',
                        hours:'Horas',
                        //points:'Puntos',
                        done:'Realizado',
                        comment:'Comentario'
                    },
                    required:[ 'name' ],
                   
                    defineTypeElement: { 
                                            'age':{type:'number',validate: ''}, 
                                            'hours':{type:'number',validate: ''}, 
                                            'points':{type:'number',validate: ''}, 
                                            'done':{type:'checkbox',validate: ''}, 
                                            'contact' :  { 
                                                type: 'select' , data:{} ,validate: ''
                                            },                        
                                            'gender':{
                                                type:'select', validate: '',
                                                data:  [{ 'value':'m', 'label':'M' },{ 'value':'f', 'label':'F' }], 
                                                taggable:false 
                                            }
                                        },
                    rows:[]
                },
                
            }
        }, 
        props:[ 'form','auth','appDomain','siteName'],
        components:{
            'tableDynamic' : tableDynamic
        },
        methods:{
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
                axios.post(api.selectTaskPivot, {id: this.$route.params.id ,type:'Voluntario' })
                .then(async function (response) {
                    if(response.data.success){

                        if(!Boolean(Number(vm.auth.company.rewards) ) ){
                            //delete vm.contacts.columns.points
                            //delete vm.contacts.columns.points
                            response.data.data.forEach(function(value, key){
                                vm.contacts.rows.push(_.omit( value , 'points')  )
                            })
                        }else{
                            vm.contacts.rows = response.data.data
                        }
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
                        
                        axios.post(api.updateVolunteers, {form : this.form, contacts : this.contacts.rows})
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
            
        }
    }

</script>
