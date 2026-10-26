<template>
<div class="content-box donor-report">
    <div class="element-wrapper print-ready">

        <div class="element-actions p-3">
            <button onClick="window.print()" class="btn btn-success"> <i class="icon-feather-printer"></i> </button>
        </div>

        <div class="public-report element-box print-ready" id="print-wrapper">
            <table class="report print-ready">
                <!-- REPORT HEADER -->
                <thead>
                    <tr>
                        <td colspan="4" class="text-center">
                            <img :src="appDomain + '/images/logo_main.png'" style="max-width:300px" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <h4 v-translate>Reporte de Transparencia {{curYear}}</h4>
                            <h2>
                                <translate>{{donor.name}}</translate>
                            </h2>
                        </td>
                        <td class="text-right">
                            <small v-translate>Actualizado el {{curDate}}</small> 
                        </td>
                    </tr>
                </thead>

                <!-- REPORT BODY -->
                <tbody>
                    <!-- >> REPORT PAGE -->
                    <tr v-if="!auth">
                        <td colspan="4">
                            <h6 class="text-center alert alert-warning p-3">
                                Este reporte le permite comprobar que se contabilizo correctamente sus donativos, seguir el avance de las actividades & de los proyectos apoyados.
                                <br><br>
                                <b>Nuestro objetivo es de hacer nuestra acción siempre mas transparente para llegar un alcance siempre mas grande.</b>
                                <br><br>
                                Agradecemos tanto su apoyo y su colaboración !
                            </h6>
                            <legend>
                                <translate>IMPACTO SOCIAL GRACIAS A SU APOYO</translate>
                            </legend>
                            <div class="row">
                                <div class="col-md-6 text-right">
                                    <h1 style="color:#EB333C">{{beneficiary_direct}}</h1>
                                    <h5>
                                        <translate>Beneficiarios Directos</translate>
                                    </h5>
                                    <p>Recibieron un apoyo directo.</p>
                                </div>
                                <div class="col-md-6 infographic">
                                    <i style="color:#EB333C" class="fa fa-child fa-5x"></i><br>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 text-right">
                                    <h2 style="color:#2E7CF0">{{beneficiary_indirect}}</h2>
                                    <h5>
                                        <translate>Beneficiarios Indirectos</translate>
                                    </h5>
                                    <p>Recibieron un apoyo preventivo o beneficia indirectamente del apoyo.</p>
                                </div>
                                <div class="col-md-6 infographic">
                                    <i style="color:#2E7CF0" class="fa fa-child fa-5x"></i><br>
                                </div>
                            </div>                            
                        </td>
                    </tr>

                    <!-- >> REPORT PAGE -->
                    <tr>
                        <td colspan="4">
                            <legend class="mt-2 mb-2">
                                <translate>DATOS DE DONADOR</translate>
                            </legend>

                            <div class="row contact-details">
                                <div class="col-6">
                                    <h5>Nombre</h5>
                                    <div class="value" v-safe-html="donor.name"></div>
                                    <template v-if="donor.type=='personal'">
                                        <h5>Edad</h5>
                                        <div class="value" v-safe-html="donor.age"></div>
                                        <h5>Genero</h5>
                                        <div class="value" v-safe-html="donor.gender"></div>
                                    </template>
                                    <template v-else>
                                        <h5>Razón Social</h5>
                                        <div class="value" v-safe-html="donor.company"></div>
                                        <h5>Giro Empresarial</h5>
                                        <div class="value" v-safe-html="donor.activity"></div>
                                    </template>
                                </div>
                                <div class="col-6">
                                    <h5>Email</h5>
                                    <div class="value" v-safe-html="donor.email"></div>
                                    <h5>Telefono</h5>
                                    <div class="value" v-safe-html="donor.phone"></div>
                                    <h5>Dirección</h5>
                                    <div class="value smaller">{{donor.address+' '+donor.city+' '+donor.postal+' '+donor.country}}</div>
                                </div>
                            </div>
                            <div class="row contact-details" v-if="auth">
                                <!-- <div class="col-6" v-if="donor.budget">
                                    <h5>Presupuesto estimado anual para colaboración con Fondo Unido</h5>
                                    <div class="value">${{donor.budget}}</div>
                                </div> -->
                                <div class="col-6" v-if="donor.comment">
                                    <h5>Comentario</h5>
                                    <div class="value">{{donor.comment}}</div>
                                </div>
                                <div class="col-6" v-if="donor.brands">
                                    <h5>Marcas</h5>
                                    <div class="value" v-safe-html="brandsFormat(donor.brands)"></div>
                                </div>
                                <div class="col-6" v-if="donor.areas_interest">
                                    <h5>Areas de Interes</h5>
                                    <div class="value" v-safe-html="interestFormat(donor.areas_interest)"></div>
                                </div>
                                <div class="col-6" v-if="donor.social">
                                    <h5>Valores de Responsabilidad Social</h5>
                                    <div class="value" v-safe-html="donor.social"></div>
                                </div>
                                <!-- <div class="col-6" v-if="donor.description_html">
                                    <h5>Detalle de su operación</h5>
                                    <div class="value" v-safe-html="donor.description_html"></div>
                                </div> -->
                                <div class="col-6" v-if="donor.global_strategy">
                                    <h5>Estrategía Global</h5>
                                    <div class="value" v-safe-html="donor.global_strategy"></div>
                                </div>
                                <!-- <div class="col-6" v-if="donor.allied_rse">
                                    <h5>Aliados RSE</h5>
                                    <div class="value" v-safe-html="donor.allied_rse"></div>
                                </div> -->
                                <!-- <div class="col-6" v-if="donor.voluntary_fum">
                                    <h5>Voluntariado antes de FUM</h5>
                                    <div class="value" v-safe-html="donor.voluntary_fum"></div>
                                </div> -->
                                <!-- <div class="col-6" v-if="donor.budget_rse_themes">
                                    <h5>Presupuesto asignado a temas de RSE</h5>
                                    <div class="value" v-safe-html="donor.budget_rse_themes"></div>
                                </div> -->
                                <!-- <div class="col-6" v-if="donor.groups_affinity">
                                    <h5>Grupos de Afinidad</h5>
                                    <div class="value" v-safe-html="donor.groups_affinity"></div>
                                </div> -->
                             
                               
                             
                                <!-- <div class="col-6" v-if="donor.direct_competition">
                                    <h5>Competencia directa</h5>
                                    <div class="value" v-safe-html="donor.direct_competition"></div>
                                </div> -->
                                <!-- <div class="col-6" v-if="donor.notes">
                                    <h5>Notas Mediaticas de interés</h5>
                                    <div class="value" v-safe-html="donor.notes"></div>
                                </div> -->
                            </div>
                        </td>
                    </tr>

                  
                    <tr>
                        <td colspan="4">

                            <legend class="mt-2 mb-2">
                                <translate>PROYECTOS APOYADOS</translate>
                            </legend>

                            <!-- TABLE PROJECTs -->
                            <table class="table zoom-07 table-padded mt-1 mb-3" v-if="projects.length">
                                <thead>
                                    <tr>
                                        <th class="text-center">Pilar</th>
                                        <th class="text-center">Programa</th>
                                        <th class="text-center">Proyecto sombrilla</th>
                                        <th class="text-center">Nombre</th>
                                        <th class="text-center">Objetivo</th>                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(pjt, projectIndex ) in projects" :key="projectIndex">
                                        <td class="text-center"><span class="badge badge-warning">{{pjt.pilar}}</span></td>
                                        <td class="text-center"><span class="badge badge-primary">{{pjt.program}}</span></td>
                                        <td class="text-center"><span class="badge badge-primary">{{pjt.project_umbrella}}</span></td>
                                        <td class="text-center"><span class="small badge badge-success">{{pjt.name}}</span></td>
                                        <td class="smaller text-center" v-safe-html="pjt.description"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-else class="text-center alert alert-warning p-3">
                                No se tienen proyectos apoyados
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <div v-if="ods.length" class="col-12 col-centered">
                                <div class="p-1 text-center">
                                    <img :src="appDomain + '/nu/'+$language.current+'/SDG.png'" style="max-width: 80%;" onerror="javascript:$(this).hide()" />
                                    <p class="text-center">
                                        <translate>Los proyectos apoyados cumplen con estos objectivos de Desarrollo Sostenible para 2030 (Indicadores ONU©)</translate> :
                                    </p>
                                </div>
                                <div class="text-center">
                                    <img v-for="(item, index) in ods" :key="index" v-if="item" :src="appDomain + '/nu/'+$language.current+'/'+item.slug+'.jpg'" style="padding:1px; margin:10px;width: 100px;" onerror="javascript:$(this).hide()" />
                                </div>
                            </div>
                        </td>
                    </tr>    
                      <!-- >> REPORT PAGE -->
                    <tr>
                        <td colspan="4">

                            <legend class="mt-2 mb-2">
                                <translate>SUS DONATIVOS</translate>
                            </legend>
                            <!-- TABLE OPERATIONs -->                                                                       

                            <!-- BALANCEs OPERATIONs -->
                            <div class="row balances"   v-if="auth">
                                <div class="col-sm-4 col-xxxl-3">
                                    <a class="element-box el-tablo" href="#">
                                        <div class="label">Donaciones Recibidas</div>
                                        <div class="value" style="font-size:30px!important">${{(donations)}}</div>
                                        <div class="trending trending-up-basic">
                                            <i class="os-icon os-icon-arrow-2-up"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-4 col-xxxl-3">
                                    <a class="element-box el-tablo" href="#">
                                        <div class="label">Egresos contabilizados</div>
                                        <div class="value" style="font-size:30px!important">${{(outcomes)}}</div>
                                        <div class="trending trending-down-basic">
                                            <i class="os-icon os-icon-arrow-2-down"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-4 col-xxxl-3">
                                    <a class="element-box el-tablo" href="#">
                                        <div class="label">Saldo</div>
                                        <div class="value" style="font-size:30px!important">${{(balance)}}</div>
                                        <div class="trending trending-up-basic">
                                            <i class="os-icon os-icon-arrow-2-up"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="row" v-if="auth">
                                <div class="col-sm-4 col-xxxl-3">
                                    <a class="element-box el-tablo" href="#">
                                        <div class="label">Fee</div>
                                        <div class="value" style="font-size:30px!important">${{(fee)}}</div>
                                        <div class="trending trending-up-basic">
                                            <i class="os-icon os-icon-arrow-2-up"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-4 col-xxxl-3">
                                    <a class="element-box el-tablo" href="#">
                                        <div class="label">Costo Operativo </div>
                                        <div class="value" style="font-size:30px!important">${{(monitoring)}}</div>
                                        <div class="trending trending-up-basic">
                                            <i class="os-icon os-icon-arrow-2-up"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-4 col-xxxl-3">
                                    <a class="element-box el-tablo" href="#">
                                        <div class="label">Saldo Disponible </div>
                                        <div class="value" style="font-size:30px!important">${{(balanceAvailable)}}</div>
                                        <div class="trending trending-up-basic">
                                            <i class="os-icon os-icon-arrow-2-up"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                                                                                  

                        </td>
                    </tr>         

                    <tr v-if="auth">
                        <td colspan="4">

                            <legend class="mt-2 mb-2">
                                <translate>OPERACIONES REGISTRADAS</translate>
                            </legend>

                            <!-- TABLE PROJECTs -->                            
                            <table  class="table zoom-07 j table-padded  mt-0" v-if="operations && operations.length">
                                <thead>
                                    <tr>
                                    <th v-translate class="text-left">Fecha</th>
                                    <th v-translate class="text-left">Referencia</th>
                                    <th v-translate class="text-left">Tipo</th>
                                    <th v-translate class="text-left">Persona</th>
                                    <th v-translate class="text-left">Donador</th>
                                    <th v-translate class="text-left">Categoría</th>										
                                    <th v-translate class="text-left">Sub Categoría</th>										
                                    <th v-translate class="text-left">Proyecto</th>										
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
                                        <td class="smaller text-left"><span :class="'badge badge-info'">{{op.project_name}}</span></td>											
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
                            <div v-else class="text-center alert alert-warning p-3">
                                No se tienen operaciones registradas
                            </div>

                        </td>
                    </tr>
                    <!-- >> REPORT PAGE -->
                  
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
import {
    appDomain
} from '../../config/app';
import {
    api
} from "../../config/jwt-routes";

export default {
    props: ['auth'],
    data() {
        return {
            loading: true,
            appDomain: appDomain,
            curYear: moment().startOf('year').format('YYYY'),
            curDate: moment().format('DD/MM/YYYY'),
            from: moment().startOf('year').format('YYYY-MM-DD'),
            to: moment().endOf('year').format('YYYY-MM-DD'),
            donor: [],
            projects: [],
            operations: [],            
            ods: [],
            proofs: [],
            chart: [],
            beneficiary_indirect: 0,
            beneficiary_direct: 0,      
            incomes:0,  
            monitoring:0, 
            balanceAvailable:0, 
            donations:0, 
            outcomes:0,   
            balance:0,   
            fee:0,
            showRsoi: false,
            showUs: false
        }
    },
    methods: {
        //FORMAT INTEREST
        interestFormat: function (areas) {
            let txt = '';
            areas = JSON.parse(areas);
            areas.forEach(function (el) {
                txt = txt + '<span class="badge-warning badge">' + el + '</span> ';
            });
            return txt
        },
        //FORMAT INTEREST
        brandsFormat: function (brands) {
            let txt = '';
            brands = JSON.parse(brands);
            brands.forEach(function (el) {
                txt = txt + '<span class="badge-info-inverted badge">' + el + '</span> ';
            });
            return txt
        },
        //Report
        report: async function (id) {
            var vm = this;
            await axios.post(api.reportDonor, {
                    from: vm.from,
                    to: vm.to,
                    id: id
                })
                .then(function (response) {
                    if (response.data.success) {
                        if (response.data.share_token && vm.$route.params.id !== response.data.share_token) {
                            vm.$router.replace({name: 'DonorReport', params: {id: response.data.share_token}});
                        }
                        //DATA
                        vm.donor = response.data.donor
                  
                        vm.projects = response.data.projects;
                        vm.operations = response.data.operationsData;
                        vm.fee = vm.formatPrice(response.data.fee);
                        vm.donations = vm.formatPrice(response.data.donations);
                        vm.incomes = vm.formatPrice(response.data.incomes);
                        vm.outcomes = vm.formatPrice(response.data.outcomes);
                        vm.balance = vm.formatPrice(response.data.balance);
                        vm.balanceAvailable = vm.formatPrice(response.data.balanceAvailable);
                        vm.monitoring = vm.formatPrice(response.data.monitoring);
                        vm.ods =  response.data.ods;
                        vm.beneficiary_indirect = response.data.beneficiary_indirect
                        vm.beneficiary_direct = response.data.beneficiary_direct
                    
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                })
        }
    },
    mounted: async function () {

        var vm = this;
        
        //IF KEY
        if (vm.$route.params.id) {
            //GET REPORT
            await vm.report(vm.$route.params.id);
        }

        vm.loading = false;

        if(!vm.auth){
            $( document ).ready(function() {
                $('.all-wrapper.menu-side.with-pattern').css('padding-top', '50px')
            })
        }
    }
}
</script>

<style>
/* Landscape: the operations table has 15 columns */
@page donor-report {
    size: landscape;
    margin: 12mm;
}
/* On paper the tables' descriptions (rich text, often joined with &nbsp;) break instead of running off the page */
@media print {
    .donor-report {
        page: donor-report;
    }
    .donor-report td.smaller {
        overflow-wrap: anywhere;
    }
}
.all-wrapper.with-pattern {
    background-size: 100% !important;
}
.balances .el-tablo {
	    
	-webkit-transform: translateY(-5px) scale(1.02);
	transform: translateY(-5px) scale(1.02);
	-webkit-box-shadow: 0px 5px 12px rgb(126 142 177 / 20%);
	box-shadow: 0px 5px 12px rgb(126 142 177 / 20%);
}
.balances .el-tablo .value{
	color: #047bf8;
}
.balances .el-tablo .trending{
	margin-left: 0;
}
</style>
