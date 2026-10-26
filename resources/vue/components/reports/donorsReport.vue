<template>
	<div class="content-box donors-report" >
		<div class="element-wrapper print-ready">
      <div class="element-actions">
          <form class="form-inline ">
            <div class="row">
              <div class="col-12">
                <div class="form-group pt-1">
                    <label v-translate>Grupo</label> 
                    <el-select
                        v-model="groups"
                        filterable   
                        @change="reportLoad()"                                                                        
                        placeholder="...">
                        <el-option
                        :key="'all'"
                        :label="'TODOS'"
                        :value="'all'">
                        </el-option>
                        <el-option
                        v-for="item in optionsDonorsGroups"
                        :key="item.groups"
                        :label="item.groups"
                        :value="item.groups">
                        </el-option>
                    </el-select>
                    <label v-translate>Donador</label> 
                    <el-select
                        v-model="donor"
                        filterable   
                        @change="reportLoad()"                                                                        
                        placeholder="...">
                        <el-option
                        :key="'all'"
                        :label="'TODOS'"
                        :value="'all'">
                        </el-option>
                        <el-option
                        v-for="item in optionsDonors"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id">
                        </el-option>
                    </el-select>
                    <label v-translate>Categoria</label>
                    <el-select
                        v-model="deal"
                        @change="reportLoad()"
                        placeholder="...">
                        <el-option                        
                        :label="'TODOS'"
                        :value="'all'">
                        </el-option>
                        <el-option                                                                                        
                        :label="'Unidad de Negocio'"
                        :value="'Unidad de Negocio'">
                        </el-option>
                        <el-option                                                                                        
                        :label="'Planta'"
                        :value="'Planta'">
                        </el-option>
                    </el-select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12 mt-3">
                <div class="form-group">
                    <label v-translate>Desde</label> 
                    <div class="date-input">
                        <input type="text" name="from" v-model="from" class="single-date from form-control" placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('from') }" >
                        <div class="invalid-feedback" v-show="errors.has('from')" >{{ errors.first('from') }}</div>  
                    </div>
                    <label v-translate>Hasta</label> 
                    <div class="date-input">
                        <input type="text" name="to" v-model="to" class="single-date to form-control" placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('to') }" >
                        <div class="invalid-feedback" v-show="errors.has('to')" >{{ errors.first('to') }}</div>  
                    </div>
                </div>
              </div>
            </div>
            <button onClick="window.print()" class="btn btn-success ml-3"> <i class="icon-feather-printer"></i> </button>
          </form>
      </div>
            
      <h6 class="element-header">        
          <span class="default-title"> <i class="icon-feather-file-text mr-1"></i> <translate>Reporte Donantes</translate></span>
      </h6>

      <div class="element-box print-ready" id="print-wrapper">

        <table class="report print-ready table-scroll">

          <!-- REPORT HEADER -->
          <thead>
            <tr>
              <td colspan="3">
                <h4 v-translate>Reporte                 
                </h4>
                <h2 v-translate>Donantes</h2>
              </td>
              <td class="text-right" style="width:30%">
                <img :src="appDomain + auth.company_logo"/>
                <br>
                {{curDate}}
                <br>
                <small class="pr-1"><translate>Desde</translate> <b>{{from}}</b> <translate>hasta</translate> <b>{{to}}</b></small>
              </td>
            </tr>
          </thead>

          <!-- REPORT BODY -->
          <tbody>

            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">

                <div class="row">
                  <div class="col-12">

                    <legend class="mt-2 mb-2"><translate>RESUMEN</translate></legend>
              
                    <div class="row">
                        <div class="form-check form-filter-large col-12"  >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>TOTAL DONATIVOS CONTABILIZADOS</span>
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumIncome)}}</span>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                            </a>
                        </div>
                        <div class="form-check form-filter-large col-6"  >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>TOTAL DE FEE</span>
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumFEE)}}</span><br>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                            </a>
                        </div>
                        <div class="form-check form-filter-large col-6"  >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>TOTAL DE SEGUIMIENTO Y MONITOREO</span>
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumMonitoring)}}</span><br>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                            </a>
                        </div>
                        <div class="form-check text-danger form-filter-large col-6"  >
                          <a class="element-box el-tablo bg-light p-2">
                              <div class="label">
                                  <span>TOTAL DE EGRESOS EJERCIDOS</span>
                              </div>
                              <div class="value">
                                  <label class="form-check-label">
                                      <span><small>$</small>{{formatPrice(totalOutcomes)}}</span>
                                  </label>
                              </div><br>
                              <div class="trending">
                                  <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                              </div>
                          </a>
                        </div>
                      
                        <div class="form-check text-success form-filter-large col-6"  >
                            <a class="element-box el-tablo bg-light p-2">
                                <div class="label">
                                    <span>SALDO POR EJECUTAR</span>
                                </div>
                                <div class="value">
                                    <label class="form-check-label">
                                        <span><small>$</small>{{formatPrice(sumIncome - totalOutcomes - sumFEE - sumMonitoring)}}</span>
                                    </label>
                                </div><br>
                                <div class="trending">
                                    <translate>Entre</translate> {{from}} <translate>y</translate> {{to}}
                                </div>
                            </a>
                        </div>
                      
                    </div>

                  </div>
                  <div class="col-6">

                    <legend class="mt-1 mb-2"><translate>REGISTRO DE AVANCE</translate></legend>
                    
                   
                     <div class="os-progress-bar primary bg-light " >
                        <div class="bar-labels">
                            <div class="bar-label-left label"><span>Actividades Concluidas</span></div>
                            <div class="bar-label-right">
                                <span class="info">{{countTaskDone}}/{{countTaskTotal}}</span>
                            </div>
                        </div>
                        <div class="bar-level-1" style="width: 100%">
                            <div class="bar-level-2" :style="'width:'+Math.round(countTaskDone*100/countTaskTotal)+'%'"></div>
                        </div>
                    </div>

                    
                  </div>
                </div>
               
                </td>
            </tr>

            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">

                <legend class="mt-2 mb-1"><translate>Donantes | PERSONAS FISICAS</translate></legend>

                <!-- TABLE DONORs -->
                <div class="scroll-x">
                  <table class="table table-sm smaller mt-1 mb-3" >
                    <thead>
                      <tr>
                          <th v-translate class="text-left">Grupo</th>
                          <th v-translate class="text-left">Nombre</th>
                          <th v-translate class="text-left">Categoria</th>
                          <th v-translate class="text-left">Edad</th>
                          <th v-translate class="text-left">Genero</th>
                          <th v-translate class="text-left">Correo</th>
                          <th v-translate class="text-left">Telefono</th>
                          <th v-translate class="text-left">Intereses</th>
                          <th v-translate class="text-left">Pres. para FUM</th>
                          <th v-translate class="text-left">Dirección</th>
                          <th v-translate class="text-left">Comentario</th>
                          <th v-translate class="text-right">Donaciones Comprometidas</th>
                          <th v-translate class="text-right">Dononaciones Contabilizado</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(el, ind ) in donors" :key="ind" v-if="el.type=='personal'">
                          <td class="text-left bolder">{{el.groups||'-'}}</td>
                          <td class="text-left bolder">{{el.name}}</td>
                          <td class="text-left bolder">{{el.deal||'-'}}</td>
                          <td class="text-left">{{el.age}}</td>
                          <td class="text-left">{{el.gender}}</td>
                          <td class="text-left">{{el.email}}</td>
                          <td class="text-left">{{el.phone}}</td>
                          <td class="text-left" v-safe-html="interestFormat(el.areas_interest||0)"></td>
                          <td class="text-left">${{ formatPrice(el.budget||0)}}</td>
                          <td class="text-left smaller">{{(el.address||'')+' '+(el.city||'')+' '+(el.postal||'')+' '+(el.country||'') }}</td>
                          <td class="text-left smaller">{{el.comment}}</td>
                          <td class="text-right bolder text-info">${{formatPrice(el.sumCredit)}}</td>
                          <td class="text-right bolder text-success">${{formatPrice(el.sumIncome)}}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <legend class="mt-2 mb-1"><translate>Donantes | PERSONAS MORALES</translate></legend>

                <!-- TABLE DONORs -->
                <div class="scroll-x">
                  <table class="table table-sm smaller mt-1 mb-3" >
                    <thead>
                      <tr>
                          <th v-translate class="text-left">Grupo</th>
                          <th v-translate class="text-left">Nombre</th>
                          <th v-translate class="text-left">Categoria</th>
                          <th v-translate class="text-left">Razon Social</th>
                          <th v-translate class="text-left">Giro</th>
                          <th v-translate class="text-left">Correo</th>
                          <th v-translate class="text-left">Telefono</th>
                          <th v-translate class="text-left">Areas de Interés</th>
                          <th v-translate class="text-left">Pres. RSE</th>
                          <th v-translate class="text-left">Pres. para FUM</th>
                          <th v-translate class="text-left">Marcas</th>
                          <th v-translate class="text-left">Dirección</th>
                          <th v-translate class="text-left">Comentario</th>
                          <th v-translate class="text-right">Donaciones Comprometidas</th>
                          <th v-translate class="text-right">Dononaciones Contabilizado</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(el, ind ) in donors" :key="ind" v-if="el.type=='company'">
                          <td class="text-left bolder">{{el.groups||'-'}}</td>
                          <td class="text-left bolder">{{el.name}}</td>
                          <td class="text-left bolder">{{el.deal||'-'}}</td>
                          <td class="text-left">{{el.company}}</td>
                          <td class="text-left">{{el.activity}}</td>
                          <td class="text-left">{{el.email}}</td>
                          <td class="text-left">{{el.phone}}</td>
                          <td class="text-left" v-safe-html="interestFormat(el.areas_interest)"></td>
                          <td class="text-left">${{el.budget}}</td>
                          <td class="text-left">${{el.budget_rse_themes}}</td>
                          <td class="text-left" v-safe-html="brandsFormat(el.brands)"></td>
                          <td class="text-left smaller">{{(el.address||'')+' '+(el.city||'')+' '+(el.postal||'')+' '+(el.country||'') }}</td>
                          <td class="text-left smaller">{{el.comment}}</td>
                          <td class="text-right bolder text-info">${{formatPrice(el.sumCredit)}}</td>
                          <td class="text-right bolder text-success">${{formatPrice(el.sumIncome)}}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                </td>
            </tr>

            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">

                <legend class="mt-2 mb-1"><translate>AGRUPADO DE DONATIVOS</translate></legend>

                <!-- TABLE CATEGORIEs -->
                <table class="table table-sm border smaller mt-1 mb-3" v-for="(groupItems, group ) in operationsGroup">
                  <thead>
                    <tr>
                      <th>Grupo: {{group||'-'}}</th>
                      <th></th>
                      <th></th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(projectItems, project ) in groupItems">
                        <tr>
                          <td style="border-bottom:1px solid rgba(83, 101, 140, 0.33);"></td>
                          <td style="border-bottom:1px solid rgba(83, 101, 140, 0.33);" class="text-left bolder">Proyecto: {{project}}</td>
                          <td style="border-bottom:1px solid rgba(83, 101, 140, 0.33);"></td>
                          <td style="border-bottom:1px solid rgba(83, 101, 140, 0.33);"></td>
                        </tr>
                        <template v-for="(contactItems, contact ) in projectItems">
                          <tr>
                            <td class="nb-cell"></td>
                            <td class="nb-cell"></td>
                            <td style="border-left:1px solid rgba(83, 101, 140, 0.33);border-bottom:1px solid rgba(83, 101, 140, 0.33);" class="text-left bolder">Donador: {{contact}}</td>
                            <td></td>
                          </tr>
                          <template v-for="(value, concept ) in contactItems">
                            <tr>
                              <td class="nb-cell"></td>
                              <td class="nb-cell"></td>
                              <td style="border-left:1px solid rgba(83, 101, 140, 0.33)" class="nb-cell"></td>
                              <td style="border-left:1px solid rgba(83, 101, 140, 0.33)" class="text-left bolder">Concepto:  {{concept}} : <span class="text-success bolder">$&nbsp;{{formatPrice(value)}}</span></td>
                            </tr>
                          </template>
                        </template>
                    </template>
                  </tbody>
                </table>
              </td>
            </tr>
            <!-- >> REPORT PAGE -->
            <tr>
              <td colspan="4">

                <legend class="mt-2 mb-1"><translate>DONATIVOS DEL PERIODO</translate></legend>

                <!-- TABLE CATEGORIEs -->
                <table class="table table-sm smaller mt-1 mb-3">
                  <thead>
                    <tr>
                        <th v-translate class="text-left">Fecha</th>
                        <th v-translate class="text-left">Grupo</th>
                        <th v-translate class="text-left">Donador</th>
                        <th v-translate class="text-left">Concepto</th>
                        <th v-translate class="text-left">Proyecto/Actividad</th>
                        <th v-translate class="text-left">Cuenta</th>
                        <th v-translate class="text-left">Importe</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(el, ind ) in operations" :key="ind">
                        <td class="text-left">{{el.date}}</td>
                        <td class="text-left bolder">{{el.groups||'-'}}</td>
                        <td class="text-left bolder">{{el.contact}}</td>
                        <td class="text-left ">{{el.category}}</td>
                        <td class="text-left"><span class="badge badge-info-inverted">{{el.project}}</span> <span class="badge badge-light">{{el.task}}</span></td>
                        <td class="text-left">{{el.account}}</td>
                        <td class="text-right bolder text-success">${{el.amount}}</td>
                    </tr>
                  </tbody>
                </table>

                </td>
            </tr>

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
                curDate: moment().format('YYYY-MM-DD'),
                groups:'all',
                donor: 'all', 
                deal:'all',       
                from: moment().startOf('year').format('YYYY-MM-DD'),
                to: moment().endOf('year').format('YYYY-MM-DD'),
                operations: [],
                donors: [],        
                categories: [],
                optionsDonorsGroups:[],
                optionsDonors:[],
                operationsGroup:[],
                sumCredit: 0,
                countTaskDone: 0,
                countTaskTotal: 0,
                sumIncome: 0,
                sumFEE:0,
                sumMonitoring:0,
                rowspanGroup: 1,
                totalOutcomes:0
            }
        },  
        methods:{
            // GET DATA
            data: async function(){
              let vm = this
              await axios.post(api.dataReportDonors)
              .then(function (response) {
                  if(response.data.success){

                      //DATA
                      vm.optionsDonorsGroups = response.data.donorsGroups;                     
                      vm.optionsDonors = response.data.optionsDonors;                     

                  } else {
                    toastr.error(response.data.msg);
                  }
              })
              .catch(function (error) {
                  toastr.error(error);
              })
            },
            //FORMAT INTEREST
            interestFormat: function(areas) {
              let txt='';
              areas = JSON.parse(areas);
              if(areas){
                _.each(areas,function(el) {
                  txt = txt+'<span class="badge-warning badge">'+el+'</span> ';
                });
              }
              return txt
            },
            //FORMAT INTEREST
            brandsFormat: function(brands) {
              let txt='';
              brands = JSON.parse(brands);
             
              _.each(brands,function(el) {
                txt = txt+'<span class="badge-info-inverted badge">'+el+'</span> ';
              });
              return txt
            },
            // Report
            reportLoad:async function(){
                var vm = this;     
                await axios.post(api.reportDonors, {from: vm.from, to: vm.to, groups: vm.groups, donor:vm.donor , deal:vm.deal})
                .then(function (response) {
                    if(response.data.success){

                        //DATA
                        vm.donors = response.data.donors;
                        vm.totalOutcomes = response.data.totalOutcomes 
                        vm.operations = response.data.operations;
                        vm.operationsGroup = response.data.operationsGroup;
                        vm.categories = response.data.categories;
                        vm.sumIncome = response.data.donors.reduce((prev,cur) =>  { return parseInt(cur.sumIncome) + prev}, 0);
                        vm.sumFEE = response.data.categories.reduce((prev,cur) =>  { return parseInt(cur.sumFee) + prev}, 0);
                        vm.sumMonitoring = response.data.categories.reduce((prev,cur) =>  { return parseInt(cur.sumMonitoring) + prev}, 0);
                        vm.sumCredit = response.data.donors.reduce((prev,cur) =>  { return parseInt(cur.sumCredit) + prev}, 0);

                        vm.countTaskTotal = response.data.taskCount
                        vm.countTaskDone = response.data.taskDone
                        // //PIE CHARTs
                        // setTimeout(function() {
                        //     new Chart($("#pieChart1"), {
                        //         type: 'pie',
                        //         data: {
                        //             labels: _.pluck(vm.categories, 'type'),
                        //             datasets: [{
                        //                     data: _.pluck(vm.categories, 'sumIncome'),
                        //                     backgroundColor: vm.blend_colors('#487918','#97d45d',_.pluck(vm.categories, 'sumIncome').length)
                        //                 }]
                        //         },
                        //         options: {
                        //             legend: {
                        //                 position: 'bottom',
                        //                 labels: {
                        //                     boxWidth: 15,
                        //                     fontColor: '#3e4b5b'
                        //                 }
                        //             },
                        //             animation:{
                        //                 animateScale:true
                        //             }
                        //         }
                        //     });
                        // },500);

                    } else {
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
            await vm.data();
            //PROJECT REPORT
            await vm.reportLoad(); 

            //DATEPICKER WITH AUTO SET
            $('.single-date').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                },
                opens: "left",
                autoApply: true,
                autoUpdateInput: false
            }, function(start, end, label) {
                var selectedStartDate = start.format('YYYY-MM-DD');
                var selectedEndDate = end.format('YYYY-MM-DD');
                var from = $('.single-date.from');
                var to = $('.single-date.to');
                from.val(selectedStartDate);
                to.val(selectedEndDate);
                var toPicker = to.data('daterangepicker');
                toPicker.setStartDate(selectedStartDate);
                toPicker.setEndDate(selectedEndDate);
                var fromPicker = from.data('daterangepicker');
                fromPicker.setStartDate(selectedStartDate);
                fromPicker.setEndDate(selectedEndDate);
                //RELOAD DATATABLE
                vm.from = selectedStartDate;
                vm.to = selectedEndDate;
                vm.reportLoad();
            });

            vm.loading = false;

        }
    }

</script>

<style>
  .groupOperations ul{
      list-style: none;
  }
  .nb-cell{
    border-bottom: 0!important;
    border-top: 0!important;
  }
  /* A named page: an unnamed @page here applied to every printout in the app */
  @page donors-report {
    size: landscape;
    margin: 12mm;
  }
  @media print {
    .donors-report {
      page: donors-report;
    }
    /* The donor lists have ~15 columns: on paper their cells wrap instead of running off the page */
    .donors-report th,
    .donors-report td {
      white-space: normal !important;
      overflow-wrap: anywhere;
    }
  }
</style>
