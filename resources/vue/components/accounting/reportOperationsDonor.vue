<template>
	<div class="content-box operations">
		<div class="element-wrapper">            
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">        
                <span class="default-title"> <i class="icon-feather-file-text mr-1"></i> <translate>Reporte de Donador</translate></span>
            </h6>
                           
            <!-- DATEPICKER RANGE FILTER -->
            <div class="element-filters mb-4" v-if="ready">
                <h6 class="element-header mb-3" v-translate>Filtros</h6>
                <div class="row">
                    <div class="col-md-12">
                        <form class="form-inline">
                            <div class="row" style="width: 100%">                               
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>
                                            <span>Periodo:</span>
                                        </label><br>
                                        <el-date-picker
                                            @change="operations()"
                                            v-model="month"
                                            value-format="yyyy-MM"
                                            type="month"
                                            placeholder="Pick a month">
                                        </el-date-picker>                                                              
                                    </div>
                                </div>

                                <div class="col-md-4" >
                                    <div class="form-group">
                                        <label>
                                            <span>Clasificación de Ingresos:</span>
                                        </label>                                                                                        
                                        <el-select name="category" v-model="category" filterable placeholder="..." >
                                            <el-option :label="'Todo'" :value="'todo'"></el-option>
                                            <el-option v-for="(item, key) in optionsCategories" :key="key" :label="key" :value="key">
                                            </el-option>
                                        </el-select>                                            
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <span>Sub Clasificación de Ingresos:</span>
                                        </label>                                                                                        
                                        <el-select name="subcategory" v-model="subcategory" filterable placeholder="...">                                                
                                            <el-option v-for="item in optionsSubCategories" :key="item" :label="item" :value="item">
                                            </el-option>
                                        </el-select>                                                                                                     
                                    </div>
                                </div>      
                            </div>
                        </form>
                    </div>
                </div>
            </div>
       
            <!-- DataTable -->
            <div class="element-box">
               

                <div class="row">
                    <div class="col-md-2">
                        <img :src="appDomain + auth.company_logo" style="width:100%;" /><br>
                    </div>
                    <div class="col-md-10">
                        <span>Donador</span>
                        <h5 v-if="donor" v-safe-html="donor.name"></h5>
                    </div>
                </div>
                <hr>

                 <div class="row balances-report">

                    <div class="col-md-4">
                        <a href="#" class="element-box el-tablo">
                            <div class="label">Ingresos</div>
                            <div class="value" style="font-size: 30px !important;" v-safe-html="'$' + formatPrice(sumIncomesNoFee)">$0.00</div>
                            <div class="trending trending-up-basic">
                                <i class="os-icon os-icon-arrow-2-up"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="element-box el-tablo">
                            <div class="label">Egresos</div>
                            <div class="value text-danger" style="font-size: 30px !important;" v-safe-html="'$' + formatPrice(sumOutcomes)">$0.00</div>
                            <div class="trending trending-down-basic">
                                <i class="os-icon os-icon-arrow-2-down"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="element-box el-tablo">
                            <div class="label">Costo Operativo</div>
                            <div class="value text-danger" style="font-size: 30px !important;" v-safe-html="'$' + formatPrice(sumMonitoring)">$0.00</div>
                            <div class="trending trending-down-basic">
                                <i class="os-icon os-icon-arrow-2-down"></i>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <a href="#" class="element-box el-tablo">
                            <div class="label">Traspasos Recibidos</div>
                            <div class="value" style="font-size: 30px !important;" v-safe-html="'$' + formatPrice(sumTransferReceipt)">$0.00</div>
                            <div class="trending trending-up-basic">
                                <i class="os-icon os-icon-arrow-2-up"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="element-box el-tablo">
                            <div class="label">Traspasos Realizados</div>
                            <div class="value text-danger" style="font-size: 30px !important;" v-safe-html="'$' + formatPrice(sumTransferSend)">$0.00</div>
                            <div class="trending trending-down-basic">
                                <i class="os-icon os-icon-arrow-2-down"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="element-box el-tablo">
                            <div class="label">Liberaciones y Remanentes</div>
                            <div class="value text-danger" style="font-size: 30px !important;" v-safe-html="'$' + formatPrice(sumRemnants)">$0.00</div>
                            <div class="trending trending-down-basic">
                                <i class="os-icon os-icon-arrow-2-down"></i>
                            </div>
                        </a>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12 ">
                        <div class="element-wrapper pb-0 mt-4">
                            <h6 class="element-header">
                                <i class="fa fa-money m-r-10 mr-2"></i>Ingresos
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-10">
                        <table class="table table-lightborder">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoria</th>
                                    <th>Sub Categoria</th>
                                    <th>Importe</th>
                                    <th>Fee Retenido</th>
                                    <th>Importe sin Fee</th>
                                    <th>Comentarios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(income, key) in incomes">
                                    <td><span class="smaller" v-safe-html="income.date"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="income.category"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="income.subcategory"></span></td>
                                    <td><span class="text-success" v-safe-html="'$' + formatPrice(income.income)"></span></td>
                                    <td><span class="text-success" v-safe-html="'$' + formatPrice(income.fee)"></span></td>
                                    <td><span class="text-success" v-safe-html="'$' + formatPrice(income.incomesNoFee)"></span></td>
                                    <td><span class="" v-safe-html="income.comment"></span></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"></td>
                                    <td><span class="badge badge-primary" v-safe-html="'$' + formatPrice(sumIncomes)"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="'$' + formatPrice(sumIncomesFee)"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="'$' + formatPrice(sumIncomesNoFee)"></span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>    

                <div class="row">
                    <div class="col-md-8">
                        <div class="element-wrapper pb-0 mt-4">
                            <h6 class="element-header">
                                <i class="fa fa-tags m-r-10 mr-2"></i>Egresos
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-lightborder ">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoria</th>
                                    <th>Sub Categoria</th>
                                    <th>Receptor</th>                                    
                                    <th>Importe</th>
                                    <th>Comentarios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(outcome, key) in outcomes">
                                    <td><span class="smaller" v-safe-html="outcome.date"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="outcome.category"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="outcome.subcategory"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="outcome.receptor"></span></td>
                                    <td><span class="text-danger" v-safe-html="'$' + formatPrice(outcome.amount)"></span></td>
                                    <td><span class="" v-safe-html="outcome.comment"></span></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4"></td>
                                    <td><span class="badge badge-danger" v-safe-html="'$' + formatPrice(sumOutcomes)"></span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>  
              
                <div class="row">
                    <div class="col-md-12 ">
                        <div class="element-wrapper pb-0 mt-4">
                            <h6 class="element-header">
                                <i class="fa fa-exchange m-r-10 mr-2"></i>Traspasos Recibidos
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-10">
                        <table class="table table-lightborder ">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoria</th>
                                    <th>Sub Categoria</th>
                                    <th>Donador</th>
                                    <th>Proyecto</th>
                                    <th>Categoria Origen</th>
                                    <th>Sub Categoria Origen</th>
                                    <th>Importe</th>
                                    <th>Comentarios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transfer, key) in transfersReceipt">
                                    <td><span class="smaller" v-safe-html="transfer.date"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="transfer.category"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="transfer.subcategory"></span></td>
                                    <td><span class="smaller" v-safe-html="transfer.donor"></span></td>
                                    <td><span class="smaller" v-safe-html="transfer.project"></span></td>
                                    <td><span class="badge badge-warning" v-safe-html="transfer.categoryOrigin"></span></td>
                                    <td><span class="badge badge-warning" v-safe-html="transfer.subcategoryOrigin"></span></td>
                                    <td><span class="text-success" v-safe-html="'$' + formatPrice(transfer.amount)"></span></td>
                                    <td><span class="" v-safe-html="transfer.comment"></span></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6"></td>
                                    <td><span class="badge badge-success" v-safe-html="'$' + formatPrice(sumTransferReceipt)"></span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>  
                   
                <div class="row">
                    <div class="col-md-12 ">
                        <div class="element-wrapper pb-0 mt-4">
                            <h6 class="element-header">
                                <i class="fa fa-exchange m-r-10 mr-2"></i>Traspasos Enviados
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-10">
                        <table class="table table-lightborder ">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoria</th>
                                    <th>Sub Categoria</th>
                                    <th>Receptor</th>
                                    <th>Proyecto</th>
                                    <th>Categoria Destino</th>
                                    <th>Sub Categoria Destino</th>
                                    <th>Importe</th>
                                    <th>Comentarios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transfer, key) in transfersSend">
                                    <td><span class="smaller" v-safe-html="transfer.date"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="transfer.category"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="transfer.subcategory"></span></td>
                                    <td><span class="smaller" v-safe-html="transfer.receptor"></span></td>
                                    <td><span class="smaller" v-safe-html="transfer.project"></span></td>
                                    <td><span class="badge badge-warning" v-safe-html="transfer.categoryDestiny"></span></td>
                                    <td><span class="badge badge-warning" v-safe-html="transfer.subcategoryDestiny"></span></td>
                                    <td><span class="text-danger" v-safe-html="'$' + formatPrice(transfer.amount)"></span></td>
                                    <td><span class="" v-safe-html="transfer.comment"></span></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6"></td>
                                    <td><span class="badge badge-danger" v-safe-html="'$' + formatPrice(sumTransferSend)"></span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>       


                <div class="row">
                    <div class="col-md-12 ">
                        <div class="element-wrapper pb-0 mt-4">
                            <h6 class="element-header">
                                <i class="fa fa-inbox m-r-10 mr-2"></i>Liberaciones y Remanentes
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-lightborder ">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoria</th>
                                    <th>Sub Categoria</th>                                                                     
                                    <th>Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(remnant, key) in remnants">
                                    <td><span class="smaller" v-safe-html="remnant.date"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="remnant.category"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="remnant.subcategory"></span></td>
                                    <td><span class="text-danger" v-safe-html="'$' + formatPrice(remnant.amount)"></span></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"></td>
                                    <td><span class="badge badge-danger" v-safe-html="'$' + formatPrice(sumRemnants)"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>  


                <div class="row">
                    <div class="col-md-8 ">
                        <div class="element-wrapper pb-0 mt-4">
                            <h6 class="element-header">
                                <i class="fa fa-lock m-r-10 mr-2"></i>Costo Operativo
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-lightborder ">
                            <thead>
                                <tr>
                                    <th>Proyecto</th>
                                    <th>Incio</th>
                                    <th>Termino</th>
                                    <th>Categoria</th>
                                    <th>Sub Categoria</th>                                                                     
                                    <th>Costo Operativo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(monitoring, key) in monitorings" v-if="monitoring.category == 'Costo Operativo'">
                                    <td><span class="smaller" v-safe-html="monitoring.project"></span></td>
                                    <td><span class="smaller" v-safe-html="monitoring.dateFrom||'-'"></span></td>
                                    <td><span class="smaller" v-safe-html="monitoring.dateTo||'-'"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="monitoring.category"></span></td>
                                    <td><span class="badge badge-primary" v-safe-html="monitoring.subcategory"></span></td>
                                    <td><span class="text-danger" v-safe-html="'$' + formatPrice(monitoring.amount)"></span></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5"></td>
                                    <td><span class="badge badge-danger" v-safe-html="'$' + formatPrice(sumMonitoring)"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>        
        
        
            </div>
		</div>
	</div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {isNumber} from "../../core/helpers.js"; 
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                ready:false,
                month:"2021-03" ,//moment().format('YYYY-MM'),
                datatable: null,
                category:'todo',
                subcategory:'',
                loading : true,
                donor:[],
                monitorings:[],
                monitoringsOriginal:[],
                incomes:[],
                incomesOriginal:[],
                remnants:[],
                remnantsOriginal:[],
                outcomes:[],
                outcomesOriginal:[],
                transfersReceipt:[],
                transfersReceiptOriginal:[],
                transfersSend:[],
                transfersSendOriginal:[],
                sumMonitoring:0,
                sumIncomes:0,
                sumOutcomes:0,
                sumRemnants:0,
                sumIncomesFee:0,
                sumIncomesNoFee:0,
                sumTransferReceipt:0,
                sumTransferSend:0,
                optionsCategories:{
                    "AVN": [
                        "AVN"
                    ],
                    "Corporativo": [
                        "Matching",
                        "Nacional",
                        "Internacional",
                        "Voluntariado",
                        "Plataformas"
                    ],
                    "Reciclaje": [
                        "Plastico",
                        "Unicel",
                        "Carton",
                        "Metales",
                        "Otros"
                    ],
                    "Especie": [
                        "Articulos de limpieza e higiene personal",
                        "Papeleria",
                        "Alimentos",
                        "Electrodomesticos",
                        "Electronica",
                        "Juguetes",
                        "Medicamentos",
                        "Mobiliarios y equipo de oficina",
                        "Otros"
                    ],
                    "Emergencias": [
                        "Nacionales",
                        "Internacional",
                        "Individuales",
                        "Plataforma"
                    ],
                    "Donativos individuales": [
                        "Plataforma",
                        "Nacionales",
                        "Internacionales",

                    ],                    
                    "No identificados": [
                        "Nacional",
                        "Internacional"
                    ],
                    "Remanentes": [
                        "Remanentes de proyectos",
                        "Extraordinarios"
                    ],
                    "Liberaciones": [
                        "Liberaciones CDMX",
                        "Feed"
                    ]
                },
                optionsSubCategories:[]
            }
        },
        watch:{

            'category': function(){
                this.subcategory = ''
                if(this.category=='todo'){
                    this.optionsSubCategories = []
                    this.incomes = JSON.parse(JSON.stringify(this.incomesOriginal))
                    this.outcomes = JSON.parse(JSON.stringify(this.outcomesOriginal))
                    this.monitorings = JSON.parse(JSON.stringify(this.monitoringsOriginal))
                    this.remnants = JSON.parse(JSON.stringify(this.remnantsOriginal))
                    this.transfersReceipt = JSON.parse(JSON.stringify(this.transfersReceiptOriginal))
                    this.transfersSend = JSON.parse(JSON.stringify(this.transfersSendOriginal))
                }else{
                    this.incomes = _.where(this.incomesOriginal,{category:this.category})
                    this.outcomes = _.where(this.outcomesOriginal,{category:this.category})
                    this.monitorings = _.where(this.monitoringsOriginal,{category:this.category})
                    this.remnants = _.where(this.remnantsOriginal,{category:this.category})
                    this.transfersReceipt = _.where(this.transfersReceiptOriginal,{category:this.category})
                    this.transfersSend = _.where(this.transfersSendOriginal,{category:this.category})
                    this.optionsSubCategories = this.optionsCategories[this.category]
                }
            },

            'subcategory': function(){                 
                let filter = {category:this.category, subcategory:this.subcategory}
                this.incomes = _.where(this.incomesOriginal,filter)
                this.outcomes = _.where(this.outcomesOriginal,filter)
                this.monitorings = _.where(this.monitoringsOriginal,filter)
                this.remnants = _.where(this.remnantsOriginal,filter)
                this.transfersReceipt = _.where(this.transfersReceiptOriginal,filter)
                this.transfersSend = _.where(this.transfersSendOriginal,filter)
            },
        },

        updated: function() {
            let vm = this;
            vm.sumIncomes = vm.incomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.income)) ?0:cur.income) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumIncomesFee = vm.incomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.fee)) ?0:cur.fee) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumIncomesNoFee = vm.incomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.incomesNoFee)) ?0:cur.incomesNoFee) + (!isNumber(prev) ? 0 : prev) }, 0);
            
            vm.sumTransferReceipt = vm.transfersReceipt.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumTransferSend = vm.transfersSend.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumOutcomes = vm.outcomes.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumRemnants = vm.remnants.reduce((prev,cur) =>  { return parseFloat((!isNumber(cur.amount)) ?0:cur.amount) + (!isNumber(prev) ? 0 : prev) }, 0);
            vm.sumMonitoring = vm.monitorings.reduce((prev,cur) =>  { 
                return parseFloat((!isNumber(cur.amount) || cur.category!='Costo Operativo' ) ? 0:cur.amount) + (!isNumber(prev) ? 0 : prev) 
            }, 0);
        },    
       
        methods: {                                            
            async operations(){
                let vm = this
                await axios.post(api.donorGetOperations, {
                    donor: vm.$route.params.id,
                    month: vm.month
                })
                .then(function (response) {
                    if(response.data.success){            
                        vm.donor = response.data.donor            
                        
                        vm.monitorings = response.data.monitorings
                        vm.monitoringsOriginal = response.data.monitorings
                       
                        vm.incomes = response.data.incomes
                        vm.incomesOriginal = response.data.incomes

                        vm.outcomes = response.data.outcomes
                        vm.outcomesOriginal = response.data.outcomes
                        
                        vm.remnants = response.data.remnants
                        vm.remnantsOriginal = response.data.remnants

                        vm.transfersReceipt = response.data.transfersReceipt
                        vm.transfersReceiptOriginal = response.data.transfersReceipt

                        vm.transfersSend = response.data.transfersSend
                        vm.transfersSendOriginal = response.data.transfersSend
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })    
            }

        },
        mounted: async function(){
            var vm = this;                                   
            vm.operations()
            vm.loading = false;
            vm.ready = true
        }
    }
</script>
<style>
    .el-select {
        width: 100%;
    }
    .operations .form-filter-large .value .form-check-label span {
        font-size: 16px;
        font-weight: 700;
    }
 
    .operations .form-filter-large .label {
        font-size: 12px;
    }
    .operations .el-tablo .value {
        font-size: 16px;
    }
    .operations div.dataTables_wrapper div.dataTables_processing{
        position: relative!important;
    }
  
</style>

<style>

.balances-report .el-tablo {
	    
	-webkit-transform: translateY(-5px) scale(1.02);
	transform: translateY(-5px) scale(1.02);
	-webkit-box-shadow: 0px 5px 12px rgb(126 142 177 / 20%);
	box-shadow: 0px 5px 12px rgb(126 142 177 / 20%);
}
.balances-report .el-tablo .value{
	color: #047bf8;
}
.balances-report .el-tablo .trending{
	margin-left: 0;
}
</style>
