<template>
    <div class="row task-form">
        <div class="col-md-12">
            <form id="formContacts" @submit.prevent="formSubmit" novalidate="true">

                <h5 class="form-header"><i class="fa fa-bar-chart m-r-10 mr-2"></i> <translate>Indicadores</translate></h5>
                <hr>

                <!-- TABLE DYNAMIC CONTACTS -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group input-sm">
                            
                            <tableDynamic :dataTable="indicators"></tableDynamic>      
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
                indicators:{
                    name:'indicators',
                    rowsDelete :[],
                    columns:{
                        category:'Indicador',
                        target:'Real',
                        meta:'Meta'
                    },
                    defineTypeElement: { 
                                            'target':{type:'number'}, 
                                            'meta':{type:'number'}, 
                                            'category' :  { 
                                                type: 'select' , data:{} ,taggable:false
                                            },
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
                await axios.post(api.dataTask, {project_id:vm.form.project_id})
                .then(async function (response) {
                    if(response.data.success){                        
                        vm.indicators.defineTypeElement.category.data = response.data.indicators
                        
                    }else{
                        toastr.error(response.data.msg)
                        
                    }
                    vm.loading = false
                })
                .catch(function (error) {
                    vm.loading = false
                    toastr.error(error);
                    console.log(error);
                })
            },
            // GET REALTION BEETWEN CLIENTS AND TASK
            pivot: async function(){                           
                let vm = this               
                axios.post(api.selectTaskPivot, {id: this.$route.params.id ,type:'Indicador' })
                .then(async function (response) {
                    if(response.data.success){

                        
                        vm.indicators.rows = response.data.data
                      
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
                        
                        axios.post(api.updateIndicators, {form : this.form, indicators : this.indicators.rows})
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
                            vm.loading=false;        
                            toastr.error(error);
                            console.log(error);
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
