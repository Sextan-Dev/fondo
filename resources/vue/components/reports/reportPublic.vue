<template>
	<div class="content-box" >
		<div class="element-wrapper print-ready">


            <div class="public-report element-box print-ready" id="print-wrapper">

                <table class="report print-ready">

                <!-- REPORT HEADER -->
                <thead>
                    <tr>
                        <td colspan="4" class="text-center">
                            <img :src="appDomain + '/images/logo_main.png'" style="max-width:300px"/>
                        </td>
                    </tr>
                    <tr>
                    <td colspan="3">
                        <h4 v-translate>Reporte de</h4>
                        <h2><translate>Transparencia</translate> {{curYear}}</h2>
                    </td>
                    <td class="text-right" style="width:15%">
                        <br><br>
                        <small v-translate>Actualizado el</small> {{curDate}}
                    </td>
                    </tr>
                </thead>

                <!-- REPORT BODY -->
                <tbody>

                    <!-- >> REPORT PAGE -->
                    <tr>

                        <td colspan="4">

                            <legend><translate>IMPACTO SOCIAL</translate></legend>

                            <div class="row">
                                <div class="col-md-6 text-right">
                                    <h1 style="color:#EB333C">{{sumBenef1}}</h1>
                                    <h5><translate>Beneficiarios Directos</translate></h5>
                                    <p>Recibieron un apoyo directo.</p>
                                </div>

                                <div class="col-md-6 infographic">

                                    <i style="color:#EB333C" class="fa fa-child fa-5x" ></i><br>
                                    <!-- <i style="color:#2E7CF0" class="fa fa-child b2" ></i><br> -->
                                    <!-- <i style="color:#999" class="fa fa-child b3" ></i> -->
 
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 text-right">

                                    <h2 style="color:#2E7CF0">{{sumBenef2}}</h2>
                                    <h5><translate>Beneficiarios Indirectos</translate></h5>
                                    <p>Recibieron un apoyo preventivo o beneficia indirectamente del apoyo.</p> 
                                </div>

                                <div class="col-md-6 infographic">

                                     <i style="color:#2E7CF0" class="fa fa-child fa-5x" ></i><br> 
 
                                </div>
                            </div>

                            <legend><translate>INDICES</translate></legend>

                            <div class="row">
                                <div class="form-check form-filter-large col-6">
                                    <a class="element-box el-tablo bg-light text-center p-3 clickable" @click="showRsoi=!showRsoi">
                                        <div class="label p-1">
                                            <span>RSOI <small>(RETORNO SOCIAL SOBRE INVERSIÓN)</small></span>
                                        </div>
                                        <div class="value p-1">
                                            <label class="form-check-label">
                                                <span :class="{'bigger':showRsoi}">{{rsoi}}</span>
                                            </label>
                                        </div>
                                        <div class="trending" v-show="!showRsoi">
                                            <translate>¿Qué es el RSOI ?</translate>
                                        </div>
                                        <div class="description alert alert-warning" v-show="showRsoi">
                                            <i class="icon-feather-info pull-left"></i>
                                            <translate>El Retorno Social sobre Inversión (RSOI) corresponde al impacto económico que tienen las acciones de la organización en la sociedad mexicana en un determinado lapso de tiempo.</translate><br><br>
                                            <b><translate>Por cada peso se genera</translate> {{rsoi}} <translate>pesos de beneficio social</translate>.</b><br><br>
                                            <small v-translate>Indice calculado según metodología FUNED y generado por el sistema de información SEXTAN®.</small>
                                        </div>
                                    </a>
                                </div>
                                <div class="form-check form-filter-large col-6">
                                    <a class="element-box el-tablo bg-light text-center p-3 clickable" @click="showUs=!showUs">
                                        <div class="label p-1">
                                            <span>US <small>(UTILIDAD SOCIAL)</small></span>
                                        </div>
                                        <div class="value p-1">
                                            <label class="form-check-label">
                                                <span :class="{'bigger':showUs}">{{us}}</span>
                                            </label>
                                        </div>
                                        <div class="trending" v-show="!showUs">
                                            <translate>¿Qué es la US ?</translate>
                                        </div>
                                        <div class="description alert alert-warning" v-show="showUs">
                                            <i class="icon-feather-info pull-left"></i>
                                            <translate>Número promedio ponderado, de personas beneficiarias de los servicios institucionales que genera cada donación de $1,000 MXN.</translate><br><br>
                                            <b><translate>Con 1000 pesos se ayuda en promedio a</translate> {{us}} <translate>personas</translate>.</b><br><br>
                                            <small v-translate>Indice SEXTAN® calculado con los datos controlados por el sistema de información SEXTAN®.</small>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        
                        
                        
                        </td>
                    </tr>
                     <tr>
                        <td>
                            <legend  class="mt-2"><translate>OBJETIVOS DE DESARROLLO SOSTENIBLE</translate></legend>
                            <div class="row">
                                <div class="col-2 " v-for="(item, index) in sdgImg" :key="index" v-if="item">
                                    <img  :src="appDomain + '/nu/'+$language.current+'/'+item+'.jpg'"  style="padding:1px;max-width: 100px;" onerror="javascript:$(this).hide()" />
                                </div>
                            </div>     
                        </td>
                    </tr>
                    <!-- >> REPORT PAGE -->
                    <tr>
                        <td colspan="4">

                        <legend class="mt-2 mb-2"><translate>PROYECTOS</translate></legend>

                        <!-- TABLE PROJECTs -->
                        <table class="table mt-1 mb-3">
                            <thead>
                            <tr>
                                <th v-translate class="text-center">Programa</th>
                                <th v-translate class="text-center">Nombre</th>
                                <th v-translate class="text-left ">Descripción</th>
                                <th v-translate class="text-left">Categoría</th>
                                <th v-translate class="text-right">Presupuesto</th>
                                <th v-translate class="text-center">Benef.</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(pjt, projectIndex ) in projects" :key="projectIndex">
                                <td class="text-center"><span class="badge badge-primary">{{pjt.program}}</span></td>
                                <td class="text-center">
                                    {{pjt.name}}
                                </td>
                                <td class="smaller text-left">{{pjt.description}}</td>
                                <td class="text-left"><span class="badge badge-primary-inverted">{{pjt.category}}</span></td>
                                <td class="bolder text-right text-nowrap">$ {{pjt.sumDebit}}</td>
                                <td class="text-right text-nowrap">Niv. 1: <b>{{pjt.sumBenef1}}</b><br> Niv. 2: <b>{{pjt.sumBenef2}}</b></td>
                            </tr>
                            </tbody>
                        </table>

                        </td>
                    </tr>
                   

                    <!-- >> REPORT PAGE -->
                    <tr>
                        <td colspan="4">

                            <div class="row">
                                <div class="col-6">

                                    <legend class="mt-1 mb-2"><translate>EGRESOS POR PROGRAMAS</translate></legend>

                                    <div class="el-chart-w">
                                        <canvas width="100%" id="pieChart2"></canvas>
                                    </div>
                                    
                                </div>
                                <div class="col-6">
                                    
                                    <legend class="mt-1 mb-2"><translate>EGRESOS POR PÚBLICO OBJETIVO</translate></legend>

                                    <div class="el-chart-w">
                                        <canvas width="100%" id="pieChart1"></canvas>
                                    </div>
                                    
                                </div>
                                
                            </div>
                            
                        </td>
                    </tr>
                    

                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="4" class="text-center pt-3">
                            <span class="copyright">Powered by <img :src="appDomain+'/images/logo_sextan_grey.png'" /></span>
                        </td>
                    </tr>
                </tfoot>

                </table>

            </div>

		</div>
	</div>
  
</template>

<script>

    import {appDomain} from '../../config/app';
    import {api} from "../../config/jwt-routes";

	export default {
        data(){
            return {
                sdgImg:[],
                loading : true,
                appDomain: appDomain,     
                curYear: moment().startOf('year').format('YYYY'),
                curDate: moment().format('YYYY-MM-DD'),
                from: moment().startOf('year').format('YYYY-MM-DD'),
                to: moment().endOf('year').format('YYYY-MM-DD'),
                programs: [],
                projects: [],        
                chart: [],        
                chartPublic: [],        
                sumBenef1: 0,
                sumBenef2: 0,
                sumBenef3: 0,
                sumIncome: 0,
                sumOutcome: 0,
                rsoi: 0,
                us: 0,
                showRsoi: false,
                showUs: false,
                goals:[],
                categories_ids:[]
            }
        },  
        methods:{
            //Report
            reportPublic:async function(){
                var vm = this;     
                await axios.post(api.reportPublic, {from: vm.from, to: vm.to })
                .then(async function (response) {
                    if(response.data.success){

                        //DATA
                        vm.programs = response.data.programs;
                        vm.projects = response.data.projects;
                        vm.goals = response.data.goals;
                        vm.chart = response.data.chart;
                        vm.chartPublic = response.data.chartPublic;
                        
                        //SUM DATA
                        vm.sumBenef1 = vm.projects.reduce((prev,cur) =>  { return Number(cur.sumBenef1) + prev}, 0);
                        vm.sumBenef2 = vm.projects.reduce((prev,cur) =>  { return Number(cur.sumBenef2) + prev}, 0);
                        vm.sumBenef3 = vm.projects.reduce((prev,cur) =>  { return Number(cur.sumBenef3) + prev}, 0);
                        vm.sumIncome = vm.projects.reduce((prev,cur) =>  { return Number(cur.sumIncome) + prev}, 0);
                        vm.sumOutcome = vm.projects.reduce((prev,cur) =>  { return Number(cur.sumOutcome) + prev}, 0);

                        //KPI
                        vm.rsoi = ((vm.sumBenef1*10 + vm.sumBenef2*0.5 + vm.sumBenef3*0.05) / (vm.sumIncome)).toFixed(2);
                        vm.us = ((vm.sumBenef1 + vm.sumBenef2*0.1 + vm.sumBenef3*0.05) / (vm.sumIncome/100)).toFixed(2);
                       
                       _.each(vm.projects, function(item, index){
                            if( !_.isNull(item.category_ids) ){
                                _.each(item.category_ids.split(',') , function(value, key){
                                    vm.categories_ids.push(value)
                                })
                            }
                        })
                        let formCategoryIds = []
                        if(_.isArray(vm.categories_ids)){
                            await  _.uniq(vm.categories_ids).forEach(function(val, index){
                                formCategoryIds.push(_.findWhere(vm.goals, {value: Number(val)}))
                            })
                            vm.categories_ids = formCategoryIds
                        }
                        let sdgImg = []
                        if(vm.categories_ids){

                            _.each( vm.categories_ids, function(item,key){
                                if(item){
                                    sdgImg.push( item.slug )
                                }
                            })
                            vm.sdgImg = sdgImg.filter(function(elem, index, self) {
                                return index === self.indexOf(elem);
                            })
                        }

                        //PIE CHARTs
                        setTimeout(function() {
                            new Chart($("#pieChart1"), {
                                type: 'pie',
                                data: {
                                    labels: _.pluck(vm.chartPublic, 'public'),
                                    datasets: [{
                                            data: _.pluck(vm.chartPublic, 'sumOutcome'),
                                            backgroundColor: vm.blend_colors('#487918','#97d45d',_.pluck(vm.chartPublic, 'sumOutcome').length)
                                        }]
                                },
                                options: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 15,
                                            fontColor: '#3e4b5b'
                                        }
                                    },
                                    animation:{
                                        animateScale:true
                                    }
                                }
                            });
                            new Chart($("#pieChart2"), {
                                type: 'pie',
                                data: {
                                    labels: _.pluck(vm.chart, 'program'),
                                    datasets: [{
                                            data: _.pluck(vm.chart, 'sumOutcome'),
                                            backgroundColor: vm.blend_colors('#922520','#e68884',_.pluck(vm.chart, 'sumOutcome').length)
                                        }]
                                },
                                options: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 15,
                                            fontColor: '#3e4b5b'
                                        }
                                    },
                                    animation:{
                                        animateScale:true
                                    }
                                }
                            });
                        },50);

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

            //PROJECT REPORT
            await vm.reportPublic(); 

            vm.loading = false;

        }
    }

</script>