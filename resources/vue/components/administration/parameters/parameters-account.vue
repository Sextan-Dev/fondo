<template>
    <div class="element-box p-3">
        <form @submit.prevent="formSubmit">
            <h5 class="form-header"><i class="os-icon os-icon-wallet-loaded m-r-10 mr-2"></i> <translate>Parametros Contables</translate></h5>
            <hr>
            <!-- PAYMENT METHODS -->
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <legend v-translate><span v-translate>Metodos de Pago</span></legend>
                        <tableDynamic :dataTable="form.modes"></tableDynamic>                       
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <legend v-translate><span v-translate>Porcentajes de Fee</span></legend>
                        <tableDynamic :dataTable="form.fee"></tableDynamic>                       
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <legend v-translate><span v-translate>Porcentajes de Costo Operacional</span></legend>
                        <tableDynamic :dataTable="form.monitoring"></tableDynamic>                       
                    </div>
                </div>
            </div>
            <!-- PAYMENT CONCEPTS -->        
            <!-- SUBMIT BUTTONS -->
            <div class="form-buttons-w">
                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                    <span v-show="loading"><translate>Guardando</translate>...</span>
                    <span v-show="!loading"><translate>Guardar</translate></span>
                </button>
            </div>
        </form>
    </div>
</template>
<script>
import {api} from "../../../config/jwt-routes"
import {EventBus} from "../../../core/eventBus"
// Import component tableDynamic
import tableDynamic from '../../../helpers/tableDynamic'
export default {
    props:['appDomain','siteName', 'auth'],
    data(){
        return {
            loading : false,
            form:{
                monitoring:{
                    name:'monitoring',
                    type:'monitoring',
                    category:'account',
                    columns:{
                        name:'Porcentaje',
                        slug:'Clave'
                    },
                    required:[ 'Porcentaje', 'slug'],
                    rowsDelete:[],
                    rows:[]
                },
                fee:{
                    name:'fee',
                    type:'fee',
                    category:'account',
                    columns:{
                        name:'Porcentaje',
                        slug:'Clave'
                    },
                    required:[ 'name', 'slug'],
                    rowsDelete:[],
                    rows:[]
                },
                modes:{
                    name:'modes',
                    type:'pay_method',
                    category:'account',
                    columns:{
                        name:'Metodo',
                        slug:'Clave'
                    },
                    required:[ 'name', 'slug'],
                    rowsDelete:[],
                    rows:[]
                },
                categories:{
                    name:'categories',
                    type:'pay_concept',
                    category:'account',
                    columns:{
                        name:'Concepto',
                        slug:'Clave'
                    },
                    required:[ 'name', 'slug'],
                    rowsDelete:[],
                    rows:[]
                }
            }
        }
    },
    components:{
        'tableDynamic' : tableDynamic
    },
    methods:{
        data:async function(){
            let vm = this
            await axios.post(api.showParameters )
            .then(async function (response) {
                if(response.data.success){
                    if (response.data.categories != undefined ) {
                        let rows = _.where(response.data.categories , { category:'account' }) 
                        vm.form.modes.rows = []
                        vm.form.categories.rows = []
                        rows.forEach(function(value, key){
                            if(value.type == 'pay_method'){
                                vm.form.modes.rows.push(_.pick( value ,'id', 'name', 'slug'))
                            }else if(value.type == 'monitoring'){
                                
                                vm.form.monitoring.rows.push(_.pick( value ,'id', 'name', 'slug'))
                            
                            }else if(value.type == 'fee'){
                                
                                vm.form.fee.rows.push(_.pick( value ,'id', 'name', 'slug'))
                            
                            }else{
                                
                                vm.form.categories.rows.push(_.pick( value ,'id', 'name', 'slug'))
                            }
                        })
                    }
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
        formSubmit:async function() {
            let vm = this
            this.$validator.validateAll().then((result) => {
                if (result){
                    vm.loading = true
                    axios.put(api.updateParametersCategories, vm.form )
                    .then(async function (response) {
                        if(response.data.success){
                            toastr.success(response.data.msg);
                            await vm.data()
                        }else{
                            toastr.error(response.data.msg);
                        }
                        vm.loading = false    
                    })
                    .catch(function (error) {
                        vm.loading = false
                        toastr.error(error);
                        console.log(error);
                    })
                }
            })
        }
    },
    mounted: async function(){
        let vm = this
        EventBus.$on("categories", function(categories) {
            if(categories != undefined )
            {
                let rows = _.where(categories , { category:'account' }) 
                rows.forEach(function(value, key){
                    if(value.type == 'pay_method'){
                        vm.form.modes.rows.push(_.pick( value ,'id', 'name', 'slug'))
                    }else if(value.type == 'monitoring'){
                        
                        vm.form.monitoring.rows.push(_.pick( value ,'id', 'name', 'slug'))
                    
                    }else if(value.type == 'fee'){
                        
                        vm.form.fee.rows.push(_.pick( value ,'id', 'name', 'slug'))
                    
                    }else{
                        
                        vm.form.categories.rows.push(_.pick( value ,'id', 'name', 'slug'))
                    }
                })
            } 
        })
    }
}
</script>