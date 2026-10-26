<template>
    <div class="row task-form">
        <div class="col-md-12">
            <form id="formOperation"  @submit.prevent="formSubmit" novalidate="true">
                <h5 class="form-header"><i class="icon-wallet m-r-10 mr-2"></i> <translate>Estado de Cuenta</translate></h5>
                <hr>
                <div class="row">
                    <div class="col-4">
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title" v-translate>Balance Ingresos</div>
                                <div class="balance-value">
                                    <span v-if="(sumIncome-sumCredit)>=0" class="text-success">${{formatPrice(sumIncome-sumCredit)}}</span>
                                    <span v-else class="text-danger">${{formatPrice(sumIncome-sumCredit)}}</span>
                                    <span class="trending smaller">
                                        PRESUPUESTADO: ${{formatPrice(sumCredit)}}<br>
                                        CONTABILIZADO: ${{formatPrice(sumIncome)}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="element-balances tip">
                            <div class="balance element-box">
                                <div class="balance-title" v-translate>Balance Egresos</div>
                                <div class="balance-value">
                                    <span v-if="(sumDebit-sumOutcome)>=0" class="text-success">${{formatPrice(sumDebit-sumOutcome)}}</span>
                                    <span v-else class="text-danger">${{formatPrice(sumDebit-sumOutcome)}}</span>
                                    <span class="trending smaller">
                                        PRESUPUESTADO: ${{formatPrice(sumDebit)}}<br>
                                        CONTABILIZADO: ${{formatPrice(sumOutcome)}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 ">
                        <div class="element-balances">
                            <div class="balance element-box">
                                <div class="balance-title" v-translate>Saldo Disponible</div>
                                <div class="balance-value">
                                    <span v-if="(sumIncome-sumOutcome) >=0" class="text-success">${{formatPrice(sumIncome-sumOutcome)}}</span>
                                    <span v-else class="text-danger">${{formatPrice(sumIncome-sumOutcome)}}</span>
                                    <span class="trending smaller">
                                        INGRESOS TOTALES: ${{formatPrice(sumIncome)}}<br>
                                        EGRESOS TOTALES: ${{formatPrice(sumOutcome)}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- TABLE DEBIT & INCOME -->
                <div class="row">
                    <div :class="(operation.incomes.length>0) ? 'col-xl-8' :'col-xl-12' ">
                        <div class="form-group ">
                            <legend><span v-translate>Ingresos Presupuestados</span></legend>
                            <tableDynamic :dataTable="operation.creditTable"></tableDynamic>      
                        </div>
                    </div>
                    <div class="col-md-4" v-if="operation.incomes.length>0">
                        <div class="form-group ">
                            <legend><span v-translate>Ingresos Contabilizados</span></legend>
                            <table  class="table table-striped table-sm" >
                                <thead>
                                    <tr>
                                        <th v-translate>Fecha</th>
                                        <th v-translate>Cliente</th>
                                        <th v-translate>Monto</th>
                                        <th v-translate>Comentario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in operation.incomes" :key="index">
                                        <td>{{row.date}}</td>
                                        <td>{{row.name}}</td>
                                        <td class="bolder text-success">${{formatPrice(row.amount)}}</td>
                                        <td class="smaller">{{row.comment}}</td>                                    
                                    </tr>
                                </tbody>
                            </table>   
                        </div>
                    </div>
                </div>
                <!-- TABLES DEBIT & OUTCOMES -->
                <div class="row">
                    <div :class="(operation.outcomes.length>0) ? 'col-xl-8' :'col-xl-12' ">
                        <div class="form-group mb-0">
                            <legend><span v-translate>Egresos Presupuestados</span></legend>
                            <tableDynamic v-bind="{dataTable:operation.debitTable}"></tableDynamic>      
                        </div>
                        <div class="form-group">
                            <div class="element-balances mt-0 pt-0 task-form">
                                <div class="balance smaller">
                                    <div class="balance-title" data-msgid="Ingreso presupuestado" >Precio</div>
                                    <div class="balance-value">
                                        <span class="text-success">${{formatPrice(sumPrice)}}</span>
                                    </div>
                                </div>
                                <div class="balance">
                                    <div class="balance-title" >Costo</div>
                                    <div class="balance-value">
                                        <span class="text-danger">${{formatPrice(sumCost)}}</span>
                                    </div>
                                </div>
                                <div class="balance bg-light ">
                                    <div class="balance-title">
                                        <span>Margen Total</span>
                                    </div>
                                    <div class="balance-value">
                                        <span>${{formatPrice(sumMargen)}}</span>
                                        <span v-if="sumMargen>0" class="trending trending-up-basic"><i class="os-icon os-icon-arrow-2-up"></i></span>
                                        <span v-else class="trending trending-down-basic"><i class="os-icon os-icon-arrow-2-down"></i></span>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" v-if="operation.outcomes.length>0">
                        <div class="form-group ">
                            <legend><span v-translate>Egresos Contabilizados</span></legend>
                            <table  class="table table-striped table-sm" >
                                <thead>
                                    <tr>
                                        <th v-translate>Fecha</th>
                                        <th v-translate>Cliente</th>
                                        <th v-translate>Monto</th>
                                        <th v-translate>Comentario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr  v-for="(row, index) in operation.outcomes" :key="index">
                                        <td>{{row.date}}</td>
                                        <td>{{row.name}}</td>
                                        <td class="bolder text-danger">${{formatPrice(row.amount)}}</td>
                                        <td class="smaller">{{row.comment}}</td>                                    
                                    </tr>
                                </tbody>
                            </table>   
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
                operation:{
                    id:null,
                    creditTable:{
                        name:'creditTable',
                        rowsDelete :[],
                        inventory: [],
                        columns:{
                            in_date:'Fecha',
                            client:'Cliente',
                            in_comment:'Descripción',
                            in_amount:'Monto',
                        },
                        required:[ 'date', 'in_amount' ],
                        defineTypeElement: { 
                            'in_date' :  { type: 'date',validate: '', class:"d-none"}, 
                            'client' :  { type: 'select' , data:[], validate: '' }, 
                            'in_amount' :  { type: 'number-edit' ,validate: 'decimal' }  ,
                            
                        },
                        rows:[]
                    },
                    debitTable:{
                        name:'debitTable',
                        inventory: [],
                        rowsDelete :[],
                        task:true,
                        columns:{
                            out_date:'Fecha',
                            provider:'Proveedor',
                            out_qt:'Cantidad',
                            out_article:'Articulo',
                            out_comment:'Descripción',
                            out_cost:'Costo',
                            out_amount:'Precio Venta'
                        },
                        required:[ 'date', 'out_amount' ],
                        defineTypeElement: { 
                            'provider' :  { type: 'select' , data:[] ,validate: ''},
                            'out_date' :  { type: 'date',validate: '', class:"d-none"}, 
                            'out_qt' : {type:'number-edit',validate: 'required|numeric', default:1},
                            'out_cost' : {type:'number-edit',validate: 'decimal'},
                            'out_amount' : {type:'number-edit',validate: 'decimal'},
                            'out_article':{type:'plain' }
                        },
                        rows:[]
                    },
                    incomes:[],
                    outcomes:[],
                }
            }
        }, 
        props:[ 'form'],
        components:{
            'tableDynamic' : tableDynamic
        },
        methods:{
           
            // GET CONTACTS LIST
            data:async function(){
                let vm = this
                await axios.post(api.selectDataTaskOperations)
                .then(async function (response) {
                    if(response.data.success){
                        vm.operation.creditTable.defineTypeElement.client.data = response.data.donor      
                        vm.operation.debitTable.defineTypeElement.provider.data = response.data.contacts                                             
                        vm.operation.debitTable.inventory= response.data.inventories                       
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
                        vm.operation.creditTable.rows =    response.data.credits                  
                        vm.operation.debitTable.rows =    response.data.debits                  
                        vm.operation.incomes =    response.data.incomes                  
                        vm.operation.outcomes =    response.data.outcomes                  
                    }else{
                        toastr.error(response.data.msg);
                    }        
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })    
            },
            updateDate(data){
                var vm = this
                vm.operation[data.table].rows[data.index][data.name]= data.value
            },
            // Form submit for Update
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading=true;
                        vm.operation.id = vm.$route.params.id 
                        axios.post(api.updateTaskOperations, vm.operation)
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
            await vm.$on('updateDate', function(value){
                vm.updateDate(value)
            });
            await vm.data()
            await vm.selectOperations()
            vm.loading = false
            $('[data-toggle="tooltip"]').tooltip()
           
        },
        updated: function() {
            let vm = this;
            vm.sumCredit = vm.operation.creditTable.rows.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.in_amount)) ?0:cur.in_amount) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumDebit = vm.operation.debitTable.rows.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.out_cost)) ?0:cur.out_cost) + (!isNumber(prev) ? 0 : prev)}, 0);
            vm.sumIncome = vm.operation.incomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev)}, 0);
            vm.sumOutcome = vm.operation.outcomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev)}, 0);

            vm.sumPrice =vm.operation.debitTable.rows.reduce((prev,cur) =>  { return ( (parseFloat(toNumber(cur.out_amount) ) * parseFloat(toNumber(cur.out_qt) ) )  + toNumber(prev) )}, 0);
            vm.sumCost=vm.operation.debitTable.rows.reduce((prev,cur) =>  { return ( (parseFloat(toNumber(cur.out_cost) ) * parseFloat(toNumber(cur.out_qt) ) )  + toNumber(prev) )}, 0);
            vm.sumMargen= vm.sumPrice - vm.sumCost;
        }
    }
</script>
