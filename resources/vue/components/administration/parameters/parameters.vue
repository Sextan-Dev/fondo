<template>
<div class="content-box" v-show="!loading">
    <div class="element-wrapper">
        <h6 class="element-header">
            <default-header></default-header>
        </h6>

        <div class="alert alert-warning borderless">
            <h5 class="alert-heading" v-translate>Parámetros del programa</h5>
            <p v-translate>
                Aquí puedes modificar los parámetros generales del programa. Algunos parámetros pueden afectar el funcionanimiento del programa. Es importante tener un buen conocimiento de las consecuencias antes guardar una modificación.
            </p>
        </div>
        
        <div class="element-box p-3">
            <form @submit.prevent="formSubmit">
                <h5 class="form-header"><i class="os-icon os-icon-wallet-loaded m-r-10 mr-2"></i> <translate>Parametros Programas</translate></h5>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <legend v-translate><span v-translate>Pilares</span></legend>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <el-input class="add-input" placeholder="..." v-model="new_pilar"></el-input>
                                </div>
                                <div class="col-md-3 pl-0">
                                    <button type="submit" @click.prevent="addPilar()" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus mr-2"></i><span>Añadir</span>
                                    </button>
                                </div>
                            </div>

                            <table class="table mt-4 table-padded zoom-07">
                                <thead>
                                    <tr>
                                        <th>
                                            Nombre
                                        </th                                   
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, key) in pilars">
                                        <td>
                                            <el-input placeholder="..." v-model="pilars[key].name"></el-input>
                                        </td>
                                        <td>

                                        </td>
                                        <td>
                                            <a href="#" class="bolder mr-3 tip" title="Guardar Cambios" @click.prevent="update(item.id, pilars[key].name)">
                                                <i class="text-primary  fa fa-floppy-o"></i>
                                            </a>
                                            <a href="#" class="bolder mr-3 tip" title="Eliminar"  @click.prevent="remove(item.id)">
                                                <i class="text-danger  fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </form>
        </div>


        <div class="element-box p-3">
            <form @submit.prevent="formSubmit">
                <h5 class="form-header"><i class="os-icon os-icon-wallet-loaded m-r-10 mr-2"></i> <translate>Parametros Contables</translate></h5>
                <hr>
                <!-- PAYMENT METHODS -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <legend v-translate><span v-translate>Metodos de Pago</span></legend>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <el-input class="add-input" placeholder="..." v-model="new_paymenth_method"></el-input>
                                </div>
                                <div class="col-md-3 pl-0">
                                    <button type="submit" @click.prevent="addPayment()" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus mr-2"></i><span>Añadir</span>
                                    </button>
                                </div>
                            </div>

                            <table class="table mt-4 table-padded zoom-07">
                                <thead>
                                    <tr>
                                        <th>
                                            Nombre
                                        </th>                                        
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, key) in payment_methods">
                                        <td>
                                            <el-input placeholder="..." v-model="payment_methods[key].name"></el-input>
                                        </td>
                                        <td>
                                            <a href="#" class="bolder mr-3 tip" title="Guardar Cambios" @click.prevent="update(item.id, payment_methods[key].name)">
                                                <i class="text-primary  fa fa-floppy-o"></i>
                                            </a>
                                            <a href="#" class="bolder mr-3 tip" title="Eliminar"  @click.prevent="remove(item.id)">
                                                <i class="text-danger  fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <legend v-translate><span v-translate>Porcentajes de Fee</span></legend>
                             <div class="row">
                                <div class="col-md-4">
                                    <el-input class="add-input" placeholder="..." v-model="new_fee"></el-input>
                                </div>
                                <div class="col-md-3 pl-0">
                                    <button type="submit" @click.prevent="addFee()" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus mr-2"></i><span>Añadir</span>
                                    </button>
                                </div>
                            </div>
                            <table class="table table-padded zoom-07">
                                <thead>
                                    <tr>
                                        <th>
                                            Porcentaje
                                        </th>                                        
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, key) in fees">
                                        <td>
                                            <el-input placeholder="..." v-model="fees[key].name"></el-input>
                                        </td>
                                        <td>
                                            <a href="#" class="bolder mr-3 tip" title="Guardar Cambios" @click.prevent="update(item.id, fees[key].name)">
                                                <i class="text-primary  fa fa-floppy-o"></i>
                                            </a>
                                            <a href="#" class="bolder mr-3 tip" title="Eliminar"  @click.prevent="remove(item.id)">
                                                <i class="text-danger  fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>            
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <legend v-translate><span v-translate>Porcentajes de Costo Operacional</span></legend>
                            <div class="row">
                                <div class="col-md-4">
                                    <el-input class="add-input" placeholder="..." v-model="new_monitoring"></el-input>
                                </div>
                                <div class="col-md-3 pl-0">
                                    <button type="submit" @click.prevent="addMonitoring()" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus mr-2"></i><span>Añadir</span>
                                    </button>
                                </div>
                            </div>
                            <table class="table table-padded zoom-07">
                                <thead>
                                    <tr>
                                        <th>
                                            Porcentaje
                                        </th>                                        
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, key) in monitoring">
                                        <td>
                                            <el-input placeholder="..." v-model="monitoring[key].name"></el-input>
                                        </td>
                                        <td>
                                            <a href="#" class="bolder mr-3 tip" title="Guardar Cambios" @click.prevent="update(item.id, monitoring[key].name)">
                                                <i class="text-primary  fa fa-floppy-o"></i>
                                            </a>
                                            <a href="#" class="bolder mr-3 tip" title="Eliminar"  @click.prevent="remove(item.id)">
                                                <i class="text-danger  fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>               
                        </div>
                    </div>
                </div>
                <!-- PAYMENT CONCEPTS -->        
                <!-- SUBMIT BUTTONS -->            
            </form>
        </div>



    </div>
</div>
</template>

<script>
import {
    api
} from "../../../config/jwt-routes";


export default {
    props: ['appDomain', 'siteName', 'auth'],
    data() {
        return {          
            loading: true,

            pilars:[],
            programs:[],
            payment_methods:[],
            fees:[],
            monitoring:[],

            new_pilar:null,
            new_paymenth_method:null,
            new_fee:null,
            new_monitoring:null,
            
            errorMonitoring:false,
            errorFee:false,
            errorPayment:false,
        }
    },
   
    methods: {
        data: async function () {     
            let vm = this       
            axios.post(api.listingParameters)
            .then(function (response) {
                if (response.data.success) {
                 
                    vm.pilars = _.where(  response.data.categories, {type:'pilar', category: "project"})
                    vm.payment_methods = _.where(  response.data.categories, {type:'pay_method', category: "account"})
                    vm.monitoring = _.where(  response.data.categories, {type:'monitoring', category: "account"})
                    vm.fees = _.where(  response.data.categories, {type:'fee', category: "account"})
                } else {
                    toastr.error(response.data.msg);
                }
            })
            .catch(function (error) {
                toastr.error(response.data.msg);
            })
        },
        update(id, value){
            let vm = this
            if(value && value != ''){
                axios.post(api.updateParameters, {value:value, id: id})
                .then(function (response) {
                    if (response.data.success) {                    
                        //vm.data()                      
                        toastr.success(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(response.data.msg);
                })
            }
        },
        
        addFee(){
            let vm = this
            vm.errorFee = false
            if(vm.new_fee){
                axios.post(api.storeParameters, {value:vm.new_fee, type: 'fee', category:'account'})
                .then(function (response) {
                    if (response.data.success) {                    
                        vm.data()
                        vm.new_fee = null
                        vm.errorFee = false
                        toastr.success(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(response.data.msg);
                })
            }else{
                vm.errorFee = true
            }
        },
        addMonitoring(){
            let vm = this
            vm.errorMonitoring = false
            if(vm.new_monitoring){
                axios.post(api.storeParameters, {value:vm.new_monitoring, type: 'monitoring', category:'account'})
                .then(function (response) {
                    if (response.data.success) {                    
                        vm.data()
                        vm.new_monitoring = null
                        vm.errorMonitoring = false
                        toastr.success(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(response.data.msg);
                })
            }else{
                vm.errorMonitoring = true
            }
        },
        addPayment(){
            let vm = this
            vm.errorPayment = false
            if(vm.new_paymenth_method){
                axios.post(api.storeParameters, {value:vm.new_paymenth_method, type: 'pay_method', category:'account'})
                .then(function (response) {
                    if (response.data.success) {                    
                        vm.data()
                        vm.new_paymenth_method = null
                        toastr.success(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(response.data.msg);
                })
            }else{
                vm.errorPayment = true
            }
        },
        addPilar(){
            let vm = this
            vm.errorPilar = false
            if(vm.new_pilar){
                axios.post(api.storeParameters, {value:vm.new_pilar, type: 'pilar', category:'project'})
                .then(function (response) {
                    if (response.data.success) {                    
                        vm.data()
                        vm.new_pilar = null
                        toastr.success(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(response.data.msg);
                })
            }else{
                vm.errorPilar = true
            }
        },

        remove(id){
            swal({
                title: "<translate>Seguro ?</translate>",
                html: "<translate>Estas a punto de eliminar este registro, deseas continuar?</translate>",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: '<translate>Confirmar</translate>',
                cancelButtonText: '<translate>Cancelar</translate>',
            }).then((confirm) => {
                if (confirm.value) {
                    var vm = this;
                    axios.post(api.deleteParameter, {id:id})
                    .then(async function (response) {
                        if(response.data.success){
                            vm.data()                
                            swal({ html : response.data.msg, type: "success", timer: 1500 });
                        }else{
                            swal.close();
                            toastr.error(response.data.msg);
                        }                             
                    })
                    .catch(function (error) {
                        toastr.error(response.data.msg);
                        console.log(error);
                    })
                } else {
                    swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                }
            });
        },

    },
    mounted: async function () {

        var vm = this;
        vm.loading = false;
        await vm.data()
    }
}
</script>
<style>
    .add-input input{
        max-height: 27px!important;
    }
</style>