<template>
	<div class="content-box" >
		<div class="element-wrapper print-ready">

      <div class="element-actions p-3">
          <button onClick="window.print()" class="btn btn-success"> <i class="icon-feather-printer"></i> </button>
      </div>
            
      <div class="element-actions p-3">
          <select v-model="versionComplete" class="form-control form-control-sm rounded">
                <option :value="true" v-translate>
									Versión Completa
								</option>
                <option :value="false" v-translate>
									Versión Cliente
								</option>
          </select>
      </div>
            
      <h6 class="element-header">
          <span v-if="report=='project'" class="default-title"> <i class="icon-feather-file-text mr-1"></i> <translate>Cotización del Proyecto</translate></span>
          <button v-if="report=='project'" @click.prevent="$router.push({name:'TaskList', params:{project:project.id}})" class="btn btn-sm btn-primary"> <i class="icon-feather-layers mr-2"></i> <translate>Actividades</translate> </button>
          <button @click.prevent="$router.push({name:'ProjectList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todos los Proyectos</translate> </button>
      </h6>

      <div class="element-box print-ready" id="print-wrapper" v-if="report!=''">
        <table class="report print-ready">

          <!-- REPORT HEADER -->
          <thead>
            <tr>

              <td colspan="3">
                <h1 v-translate>COTIZACIÓN</h1>
                <h4><span v-translate>Proyecto:</span> <b>{{project.name}}</b></h4>
              </td>

              <td class="b-l" style="width:20%">
                <img :src="appDomain + auth.company_logo" onerror="javascript:$(this).hide()" /><br>
                <div class="value-pair">
                  <div class="label" v-translate>Razon Social:</div>
                  <div class="value b">{{auth.company.name}}</div>
                </div><br>
                <div class="value-pair">
                  <div class="label" v-translate>RFC:</div>
                  <div class="value b">{{auth.company.rfc}}</div>
                </div><br>
                <div class="value-pair">
                  <div class="label" v-translate>Dirección:</div>
                  <div class="value b">{{auth.company.address+', '+auth.company.city+', '+auth.company.post_code}}</div>
                </div><br>
                <div class="value-pair">
                  <div class="label" v-translate>Fecha:</div>
                  <div class="value b"> {{curDate}}</div>
                </div>
              </td>

            </tr>
          </thead>

          <!-- REPORT BODY -->
          <tbody>

            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">
                <div class="row m-b mt-2 b-b">

                  <!-- DESCRIPTIONs & AVANCE -->
                  <div class="col-12">
                    <div class="element-info-with-icon smaller mb-2">
                      <div class="element-info-icon">
                        <div class="icon-feather-info"></div>
                      </div>
                      <div class="element-info-text">
                        <h5 class="element-inner-header" v-translate>Description</h5>
                      </div>
                    </div>
                    <div class="mb-3" >
                      {{project.description}}
                    </div>
                  </div>

                </div>
              </td>
            </tr>
            <tr v-if="project.quote_html">
              <td colspan="4" class="">
                  <div class="row m-b">
                    <!-- QUOTE HTML -->
                    <div class="col-12" v-safe-html="project.quote_html"></div>
                  </div>
              </td>
            </tr>
            <!-- >> TASK PAGE -->
            <tr>
              <td colspan="4">
                <legend class="mt-2"><translate>DESGLOSE DE ACTIVIDADES</translate></legend>            
                <!-- TABLE TASKs -->
                <template >
                  <div class="m-b">
                    <table class="table" width="100%">
                      <thead>
                          <tr>
                            <th class="text-left" style="padding:0;border: 0px" colspan="3" v-translate></th>
                            <th class="text-left" v-show="versionComplete" v-translate>Proveedor</th>
                            <th class="text-center" v-translate>Cantidad</th>
                            <th class="text-left" v-translate>Articulo</th>
                            <th class="text-left" v-translate>Descripcíon</th>
                            <th class="text-right" v-translate>Precio/Unit.</th>
                            <th class="text-right" v-show="versionComplete" v-translate>Costo/Unit.</th>
                            <th class="text-right" v-show="versionComplete" v-translate>Margen</th>
                            <th class="text-right" v-translate>Total</th>
                          </tr>
                      </thead>
                      <tbody>
                        <template v-for="(task, index) in operationsTask" >                         
                          <tr :key="index">
                            <td  :rowspantest="task.length+1"  style="padding:0;border: 0px" class="nowrap" colspan="3" ><h6 class="element-inner-header">{{index}}</h6><div> {{task[0].taskDescription}} </div></td><td colspan="8"></td>
                          </tr>
                          <tr v-for="(item, key) in task" v-if="item.type=='debit'" :key="key" >                            
                            <td style="border: 0px" colspan="3"></td>
                            <td v-show="versionComplete" style="padding:0" class="nowrap">{{(item.client=='')?'-':item.client}}</td>
                            <td style="padding:0" class="text-center bolder nowrap">{{(item.qt)?item.qt:0}}</td>
                            <td style="padding:0" class="text-left smaller"><img alt=""><span>{{(item.article=='')?'-':item.article}}</span></td>
                            <td style="padding:0" class="text-left smaller">{{(item.comment=='')?'-':item.comment}}</td>
                            <td style="padding:0" class="text-right bolder nowrap"><span class=""><small>$</small>&nbsp;{{toNumber(item.amount)}}</span></td>
                            <td v-show="versionComplete" style="padding:0" class="text-right bolder nowrap"><span class="text-danger"><small>$</small>&nbsp;{{toNumber(item.cost)}}</span></td>
                            <td v-show="versionComplete" style="padding:0" class="text-right bolder nowrap"><span class="text-info"><small>$</small>&nbsp;{{parseFloat(toNumber(item.amount)*toNumber(item.qt)) - parseFloat(toNumber(item.cost)*toNumber(item.qt))}}</span></td>                            
                            <td style="padding:0" class="text-right bolder nowrap"><span class="text-success"><small>$</small>&nbsp;{{parseFloat(toNumber(item.amount)*toNumber(item.qt)) - parseFloat(toNumber(item.cost)*toNumber(item.qt))}}</span></td>                            
                          </tr>
                        </template>
                      </tbody>
                    </table>
                  </div>
                </template>
              </td>
            </tr>
            <!-- >> TASK PAGE -->
            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4" >

                <!-- BALANCEs OPERATIONs -->
                <div v-if="versionComplete"  class="element-balances mt-0 pt-0 task-form">
                  <div class="balance smaller">
                      <div class="balance-title" v-translate>Precio Total</div>
                      <div class="balance-value">
                          <span class="text-success">${{sumPrice.toLocaleString('en')}}</span>
                      </div>
                  </div>
                  <div class="balance">
                      <div class="balance-title" v-translate>Costo Total</div>
                      <div class="balance-value">
                          <span class="text-danger">${{sumCost.toLocaleString('en')}}</span>
                      </div>
                  </div>
                  <div class="balance bg-light ">
                    <div class="balance-title"><translate>Margen</translate></div>
                    <div class="balance-value">
                      <span>${{sumMargen.toLocaleString('en')}}</span>
                      <span v-if="sumMargen>0" class="trending trending-up-basic"><i class="os-icon os-icon-arrow-2-up"></i></span>
                      <span v-else class="trending trending-down-basic"><i class="os-icon os-icon-arrow-2-down"></i></span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-right">
                  <div class="element-balances mt-0 pt-0 task-form d-inline-block">
                    <div class="balance bg-light b-t pl-4 pr-2">
                      <div class="balance-title"><translate>TOTAL</translate></div>
                      <div class="balance-value pl-4">
                        <span>$ {{sumPrice.toLocaleString('en')}}</span>
                      </div>
                    </div>
                  </div>
                </div>

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
  import {saveAs, isNumber, toNumber} from "../../core/helpers.js"
	export default {
        props:['auth','appDomain','siteName'],
        data(){
            return {
                loading : true,
                form: {
                  programs : [],
                  projects : [],
                  tasks : []
                },     
                versionComplete:true,
                programId : '',
                projectId : '',
                taskId : '',   
                report: '',        
                curDate: moment().format('DD/MM/YYYY'),
                program: [],        
                project: [],        
                projects: [],        
                tasks: [],        
                contacts: [],        
                proofs: [],        
                files: [],        
                operations: [],        
                balance: 0,        
                sumIncome: 0,        
                sumOutcome: 0,        
                sumCredit: 0,        
                sumDebit: 0, 
                sumPrice: 0, 
                sumCost: 0, 
                sumMargen:0,
                operationsTask:[],       
                milestones: [],        
                comments: [] 
            }
        },  
       
       
        methods:{
            //Data Report Options
            dataReport:async function(){
                var vm = this;     
                await axios.post(api.dataReport)
                .then(function (response) {
                    if(response.data.success){
                      vm.form.programs = response.data.programs;
                      vm.form.projects = response.data.projects;
                      vm.form.tasks = response.data.tasks;
                    } else {
                      console.log('error');
                      toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log('Catch : '+error);
                })
            },
            toNumber(val){
              return toNumber(val)
            },
            //Project Report
            projectReport:async function( programId, projectId, taskId ){
                var vm = this;     
                await axios.post(api.projectReport, {programId: programId, projectId: projectId, taskId: taskId })
                .then(function (response) {
                    if(response.data.success){
                      vm.program = response.data.program[0];
                      vm.project = response.data.projects[0];
                      vm.projects = response.data.projects;
                      vm.tasks = response.data.tasks;
                      vm.operations = response.data.operations;
                      vm.operationsTask = response.data.operationsTask;
                      vm.comments = response.data.comments;
                      vm.milestones = response.data.milestones;
                      vm.sumPrice  = 0;
                      vm.sumCost  = 0;
                      vm.sumMargen = 0;
                      
                      _.each(vm.operations, function(value, key){
                        if (value.type && value.type=="debit") {
                          vm.sumPrice = parseFloat(vm.sumPrice) + ( parseFloat(toNumber(value.amount)) * parseFloat(toNumber(value.qt)) )
                          vm.sumCost = parseFloat(vm.sumCost) + ( parseFloat(toNumber(value.cost)) * parseFloat(toNumber(value.qt)) )
                        }
                      });
                      vm.sumMargen = parseFloat(vm.sumPrice )- parseFloat(vm.sumCost);

                    } else {
                      console.log('error');
                      toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log('Catch : '+error);
                })
            },
        },
        mounted: async function() {

          var vm = this;
            vm.$route.params.projectId = 1
            //PROJECT REPORT
            if (vm.$route.params.projectId) {
              vm.projectId = vm.$route.params.projectId;
   
            } 
          this.report = 'project';              
         await this.projectReport('',vm.projectId,'');
          vm.loading = false;

        }
    }

</script>

<style>
@media print {
  .el-tablo.padded {
    padding: 1rem;
  }
  .activity-box {
    max-width: 60%;
    overflow: hidden;
  }
  .support-index .support-ticket-content-w {
    display: block !important;
  }
}
</style>
