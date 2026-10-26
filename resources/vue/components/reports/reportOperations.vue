<template>
	<div class="content-box" >
		<div class="element-wrapper print-ready">

      <div class="element-actions">
          <form class="form-inline justify-content-sm-end">

                <div class="form-group pt-1">
                    <label v-translate>Mes</label> 
                    <el-date-picker
                            v-model="month"
                            type="month"
                            format="MM/yyyy"	
                            value="yyyy-MM"	
                            placeholder="Selecciona un Mes">
                    </el-date-picker>
                </div>
                <button onClick="window.print()" class="btn btn-success ml-3"> <i class="icon-feather-printer"></i> </button>
          </form>
      </div>
            
      <h6 class="element-header">        
          <span class="default-title"> <i class="icon-feather-file-text mr-1"></i> <translate>Reporte Tesorería</translate></span>
      </h6>

      <div class="element-box print-ready" id="print-wrapper">

        <table class="report print-ready">

          <!-- REPORT HEADER -->
          <thead>
            <tr>
              <td colspan="3">
                <h4 v-translate>Reporte                 
                </h4>
                <h2 v-translate>Tesorería</h2>
              </td>
              <td class="text-right" style="width:15%">
                <img :src="appDomain + auth.company_logo"/>
                <br>
                {{curDate}}
                <br>
                <small class="pr-1"><translate>Reporte desde</translate> <b>{{from}}</b> <translate>hasta</translate> <b>{{to}}</b></small>
              </td>
            </tr>
          </thead>

          <!-- REPORT BODY -->
          <tbody>

            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">

                    <legend class="mt-2 mb-2"><translate>SALDOS</translate></legend>
                    
                    <div class="row">
                        <div class="form-check form-filter-large col-6" v-for="(item , Accountkey) in accounts" :key="Accountkey" >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>{{item.name}}</span>
                                    <img style="width: 100px" class="pull-right p-1" :src="appDomain +'/'+ item.img" onerror="javascript:this.src='../images/bank.png'" />
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{item.sum_range.split('.')[0]}}<small><small>.{{item.sum.split('.')[1]}}</small></small></span>
                                    </label>
                                </div>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                                <br>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{item.sum.split('.')[0]}}<small><small>.{{item.sum.split('.')[1]}}</small></small></span>
                                    </label>
                                </div>
                                <div class="trending">
                                    <translate>Total hasta el</translate> {{curDate}}
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-check form-filter-large col-6"  >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>FEE</span>                                
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumFee)}}<small></small></span>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                                <br>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumFeeTotal)}}<small></small></span>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Total hasta el</translate> {{curDate}}
                                </div>
                            </a>
                        </div>
                        <div class="form-check form-filter-large col-6"  >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>Seguimiento y Monitoreo</span>                                
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumMonitoring)}}<small></small></span>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                                <br>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumMonitoringTotal)}}<small></small></span>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Total hasta el</translate> {{curDate}}
                                </div>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">

                <legend class="mt-2 mb-1"><translate>Operaciones Realizadas</translate></legend>
                    
                    <!-- TABLE OPERATIONs -->
                    <table  class="table zoom-07  mt-0" v-if="operations && operations.length">
                        <thead>
                            <tr>
                            <th v-translate class="text-left">Fecha</th>
                            <th v-translate class="text-left">Referencia</th>
                            <th v-translate class="text-left">Tipo</th>
                            <th v-translate class="text-left">Persona</th>
                            <th v-translate class="text-left">Donador</th>
                            <th v-translate class="text-left">Categoría</th>										
                            <th v-translate class="text-left">Sub Categoría</th>										
                            <th v-translate class="text-left">Forma de Pago</th>
                            <th v-translate class="text-center">% de Fee</th>
                            <th v-translate class="text-center">FEE</th>
                            <th v-translate class="text-center">% de Operación</th>
                            <th v-translate class="text-center">Costo de Operación</th>
                            <th v-translate class="text-left">Ingreso</th>
                            <th v-translate class="text-left">Egreso</th>										
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(op, operationIndex ) in operations" :key="operationIndex">
                                <td class="text-left small">{{op.date}}</td>											
                                <td class="text-left">{{op.reference||'-'}}</td>
                                <td class="text-left"> <span class="badge" :class="op.type == 'income' ? 'badge-success' : 'badge-danger'" v-safe-html="op.type == 'income' ? 'Ingreso' : 'Egreso'"></span></td>
                                <td class="text-left">{{op.client||'-'}}</td>
                                <td class="text-left">{{op.donor||'-'}}</td>
                                <td class="smaller text-left"><span :class="'badge badge-warning'">{{op.category}}</span></td>											
                                <td class="smaller text-left"><span :class="'badge badge-warning'">{{op.subcategory}}</span></td>											
                                <td class="text-left">{{op.mode}}</td>
                                <td class="text-center">												
                                    <span class="bolder" v-safe-html="op.type=='outcome' ? 'N/A' : (((op.fee==null ) ? 0  : (op.fee )) + '%') "></span>
                                </td>											
                                <td class="text-center">
                                    <span class="bolder" v-safe-html="op.type=='outcome' ? 'N/A' : ( (op.fee==null ) ? 0  : formatPrice(op.amount *  (op.fee/100) ))"></span>
                                </td>											
                                <td class="text-center">												
                                    <span class="bolder" v-safe-html="op.type=='outcome' ? 'N/A' : (((op.monitoring==null ) ? 0  : (op.monitoring )) + '%') "></span>
                                </td>											
                                <td class="text-center">
                                    <span class="bolder" v-safe-html="op.type=='outcome' ? 'N/A' : ( (op.monitoring==null ) ? 0  : formatPrice(op.amount *  (op.monitoring/100) ))"></span>
                                </td>											
                                <td class="text-left text-success" v-safe-html="op.type=='income' ? ('$'+ formatPrice(op.amount)) : '-'"></td>
                                <td class="text-left text-danger" v-safe-html="op.type=='outcome' ? ('-$'+ formatPrice(op.amount)) : '-'"></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9"></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>	

                </td>
            </tr>

            <!-- >> REPORT PAGE -->
          

          </tbody>

        </table>

      </div>

		</div>
	</div>
  
</template>

<script>

  import {api} from "../../config/jwt-routes";

	export default {
        props:['auth','appDomain','siteName'],
        data(){
            return {
                loading : true,    
                curDate: moment().format('DD/MM/YYYY'),             
                month: moment().format('YYYY-MM'),
                accounts: [],
                categories: [],
                operations:[],
                chart: [],
                sumFee:0,
                sumMonitoring:0,
                sumMonitoringTotal:0,
                sumFeeTotal:0
            }
        },  
        methods:{
             // Get data for Form & Set all selects
            data: async function(){
                var vm = this;
                await axios.post(api.dataOperation)
                .then(function (response) {
                    if(response.data.success){                        
                        vm.optionsMode = response.data.mode                        
                        vm.optionsFee = response.data.fee
                        vm.optionsMonitoring = response.data.monitoring
                        vm.optionsProjects = response.data.projects                        
                        vm.optionsContactsAvailables = response.data.contacts
                        vm.optionsContacts = _.where(response.data.contacts, {category:'Donador'})
                        vm.optionsDonors = _.where(response.data.contacts, {category:'Donador'})
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
            },
            //Project Report
            reportOperations:async function(){
                var vm = this;     
                await axios.post(api.reportOperations, {month: moment(vm.month).endOf('month').format('YYYY-MM-DD') })
                .then(function (response) {
                    if(response.data.success){
                        vm.operations = response.data.operations
                    }else{
                        toastr.error(response.data.msg);
                    }

                       
                      
                })
                .catch(function (error) {
                    toastr.error(error);
                })
            }
        },
        mounted: async function() {

            var vm = this;

            //PROJECT REPORT
            await vm.data(); 
            await vm.reportOperations()

            vm.loading = false;

        }
    }

</script>

