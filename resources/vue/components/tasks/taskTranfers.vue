<template>
    <div class="row task-form">
        <div class="col-md-12">
            <form id="formOperation"  @submit.prevent="formSubmit" novalidate="true">
                <h5 class="form-header"><i class="icon-wallet m-r-10 mr-2"></i> <translate>Transferencia de Donativos</translate></h5>
                <hr>
                <div class="row">
                    <div class="mt-3 col-4 ">
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title" v-translate>Ingreso Totales</div>
                                <div class="balance-value">
                                    <span  class="text-success">${{formatPrice(sumIncome)}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 col-4 ">
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title" v-translate>Egresos Totales</div>
                                <div class="balance-value">
                                    <span  class="text-danger">${{formatPrice(sumOutcome)}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 col-4 ">
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title" v-translate>Saldo Disponible</div>
                                <div class="balance-value">
                                    <span v-if="(sumIncome-sumOutcome) >=0" class="text-success">${{formatPrice(sumIncome-sumOutcome)}}</span>
                                    <span v-else class="text-danger">${{formatPrice(sumIncome-sumOutcome)}}</span>
                                   
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6  mt-5">
                        <div class="form-group">
                            <label>
                                <span v-translate>Actividad</span>
                            </label><br>
                            <span class="help" v-translate>Selecciona la actividad a la que se tranferira el importe</span>
                            <br>
                            <div class="form-group">
                                <v-select   placeholder="..." id="task_id" name="task_id" data-vv-name="task_id"
                                        :class="{'input': true, 'is-invalid': errors.has('task_id') }"
                                        v-model="operation.task_id"
                                        data-vv-as=" "    
                                        v-validate="'required'"
                                        :options="optionsTask">
                                </v-select>
                                
                                <div class="invalid-feedback" v-show="errors.has('task_id')" >{{ errors.first('task_id') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>
                                <span v-translate>Ingresos</span>
                            </label><br>
                            <span class="help" v-translate>Selecciona el Ingreso de donde se tomaran los recursos</span>
                            <br>
                            <div class="form-group">
                                <v-select   placeholder="..." id="operation_id" name="operation_id" 
                                        :class="{'input': true, 'is-invalid': errors.has('operation_id') }"
                                        v-model="operation.operation_id"
                                        data-vv-as=" "    
                                        v-validate="'required'"
                                        :options="operations">
                                </v-select>                                
                                <div class="invalid-feedback" v-show="errors.has('operation_id')" >{{ errors.first('operation_id') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" v-if="operation.operation_id">
                    <div class="col-md-8">
                        <table class="table table-lightborder table-bordered table-v-compact mb-0">
                            <tbody>
                                <tr>
                                    <td><strong class="text-success">$ {{formatPrice(operation.operation_id.income)}}</strong><div class="balance-label smaller lighter text-nowrap">Donación recibida</div></td>
                                    <td><strong class="text-danger">$ {{formatPrice(operation.operation_id.outcomes)}}</strong><div class="balance-label smaller lighter text-nowrap">Egresos Pagados de esta donación</div></td>
                                    <td><strong :class="(incomeBalance>0)? 'text-success':  'text-danger'">$ {{formatPrice(incomeBalance)}}</strong><div class="balance-label smaller lighter text-nowrap">Saldo disponible</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label >
                                <span v-translate>Cuenta de Retiro</span>
                            </label>
                            <span class="help" v-translate>Seleccione la cuenta de retiro</span>
                            <br> 
                            <v-select   placeholder="..." name="account_out_id"
                                        v-validate="'required'" 
                                        data-vv-as=" "    
                                        :options="optionsAccounts" 
                                        :class="{'input': true, 'is-invalid': errors.has('account_out_id') }" 
                                        v-model="operation.account_out_id">
                            </v-select>
                            <div class="invalid-feedback" v-show="errors.has('account_out_id')" >{{ errors.first('account_out_id') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label >
                                <span v-translate>Cuenta Destino</span>
                            </label>
                            <span class="help" v-translate>Seleccione la cuenta destino</span>
                            <br> 
                            <v-select   placeholder="..." name="account_in_id"
                                        v-validate="'required'" 
                                        data-vv-as=" "    
                                        :options="optionsAccounts" 
                                        :class="{'input': true, 'is-invalid': errors.has('account_in_id') }" 
                                        v-model="operation.account_in_id">
                            </v-select>
                            <div class="invalid-feedback" v-show="errors.has('account_in_id')" >{{ errors.first('account_in_id') }}</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span v-translate>Concepto</span>
                            </label>
                            <br> 
                            <v-select placeholder="..." name="category"     
                                v-model="operation.category_id"
                                data-vv-as=" "    
                                :options="optionsCategories"
                                v-validate="'required'" 
                                :class="{'input': true, 'is-invalid': errors.has('category') }">
                            </v-select>
                          
                            <div class="invalid-feedback" v-show="errors.has('category')" >{{ errors.first('category') }}</div>
                        </div>
                    </div>
                     <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span v-translate>Cliente/Proveedor</span>
                            </label>
                            <span class="help" v-translate>ej. Carmen Felix</span>
                            <br> 
                           <v-select placeholder="..." name="client_id"
                                    v-model="operation.client_id"  
                                    :options="optionsClients"  
                                    data-vv-as=" "    
                                    v-validate="'required'" 
                                    :class="{'input': true, 'is-invalid': errors.has('client_id') }"> 
                            </v-select>
                            <div class="invalid-feedback" v-show="errors.has('client_id')" >{{ errors.first('client_id') }}</div>
                        </div>
                    </div>
                </div> -->
                <div class="row">
                    <div class="col-6 ">
                        <div class="form-group">
                            <label for="">Importe</label><br>
                            <span class="help">Indica el importe a tranferir.</span>
                            <input type="text" class="form-control" name="amount" v-model="operation.amount"   :class="{'input': true, 'is-invalid': errors.has('amount') }" v-validate="'required|decimal'" >
                            <div class="invalid-feedback" v-show="errors.has('amount')" >{{ errors.first('amount') }}</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="">
                                <span v-translate>Comentarios</span>
                                <span class="help" v-translate>Este campo es opcional</span>
                            </label><br>
                            <textarea name="comment" class="form-control" v-model="operation.comment" rows="3"></textarea>
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
    import {saveAs, isNumber, toNumber} from "../../core/helpers.js"; 
	export default {
        data(){
            return {
                loading : true,
                appDomain: appDomain,
                sumDebit: 0,
                sumCredit: 0,
                sumIncome: 0,
                sumOutcome: 0,
                sumPrice: 0,
                sumCost: 0,
                sumMargen: 0,
                optionsTask:[],
                operations:[],
                optionsClients:[],
                optionsCategories:[],
                optionsAccounts:[],
                incomeBalance:0,
                operation:{                    
                    incomes:[],
                    outcomes:[],
                    amount:0,
                    task_id: null,
                    account_out_id:null,
                    operation_id:null,
                    account_in_id:null,
                    client_id:null,
                    category_id:null,
                    comment:''
                }
            }
        }, 
        props:[ 'form'],

        computed:{
            operation_id : function(){
                return this.operation.operation_id
            }
        },

        watch: {
            operation_id : function (){
                let vm = this
                if(!_.isNull(this.operation.operation_id)){
                    let operation = this.operation.operation_id
                    vm.incomeBalance = operation.income - operation.outcomes
                }
            }
        },

        methods:{
                        
            data:async function(){
                let vm = this
                await axios.post(api.dataTask,{project_id:vm.form.project_id, task_id:vm.form.id})
                .then(async function (response) {
                    if(response.data.success){   
                        vm.optionsTask= []  
                        _.each(response.data.task, function(item, key){
                            if(Number(item.value)!= vm.form.id){
                                vm.optionsTask.push(item)
                            }
                        })   
                        vm.operations = response.data.operations
                        // vm.optionsClients = response.data.contacts    
                        // vm.optionsCategories = response.data.category     
                    }else{
                        toastr.error(response.data.msg);
                    }        
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })    
            },

            // GET ALL OPERATIONS
            selectOperations:async function(){
                let vm = this
                axios.post(api.selectTaskOperations,{id:vm.$route.params.id})
                .then(async function (response) {
                    if(response.data.success){               
                        vm.operation.incomes =    response.data.incomes                  
                        vm.operation.outcomes =    response.data.outcomes   
                        vm.sumIncome = vm.operation.incomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev)}, 0);
                        vm.sumOutcome = vm.operation.outcomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev)}, 0);                      
                    }else{
                        toastr.error(response.data.msg);
                    }        
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })    
            },

            clearForm(){
                let vm = this
                vm.operation.amount = 0
                vm.operation.task_id =  null
                vm.operation.account_out_id = null
                vm.operation.account_in_id = null
                vm.operation.client_id = null
                vm.operation.category_id = null
                vm.operation.operation_id = null
                vm.operation.comment = ''
                vm.$validator.reset();
                
            },
           
            // Form submit for Update
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading=true;
                        axios.post(api.transferTaskOperations, {task_id : vm.form.id, operation: vm.operation})
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);     
                                vm.selectOperations();     
                                vm.clearForm();    
                                await vm.data()   
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
            await vm.data()
            await vm.selectOperations()
            vm.loading = false
            $('[data-toggle="tooltip"]').tooltip()
           
        },
        
    }
</script>
