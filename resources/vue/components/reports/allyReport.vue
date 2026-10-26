<template>
<div class="content-box ally-report">
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
                            <h4 v-translate>Reporte de Transparencia {{curYear}} para Aliado</h4>
                            <h2>
                                <translate>{{ally.name}}</translate>
                            </h2>
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

                            <legend class="mt-2 mb-2">
                                <translate>DATOS DEL ALIADO</translate>
                            </legend>

                            <div class="row contact-details">
                                <div class="col-6">
                                    <h5>Nombre</h5>
                                    <div class="value" v-safe-html="ally.name"></div>
                                    <h5>Teléfono</h5>
                                    <div class="value" v-safe-html="ally.phone"></div>
                                </div>
                                <div class="col-6">
                                    <h5>Email</h5>
                                    <div class="value" v-safe-html="ally.email"></div>
                                    <h5>Dirección</h5>
                                    <div class="value smaller">{{ally.address+' '+ally.city+' '+ally.postal+' '+ally.country}}</div>
                                </div>
                            </div>

                        </td>
                    </tr>

                    <!-- >> REPORT PAGE -->
                    <tr>
                        <td colspan="4">

                            <legend class="mt-2 mb-2">
                                <translate>PROYECTOS</translate>
                            </legend>

                            <!-- TABLE PROJECTs -->
                            <table class="table mt-1 mb-3">
                                <thead>
                                    <tr>
                                        <th v-translate class="text-center">Nombre</th>
                                        <th v-translate class="text-left">Description</th>
                                        <th v-translate class="text-center">Programa</th>
                                        <th v-translate class="text-left">Categoría</th>
                                        <th v-translate class="text-left">Actividades</th>
                                        <th v-translate class="text-center">Benef.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(pjt, projectIndex ) in projects" :key="projectIndex">
                                        <td class="text-center">

                                            {{pjt.name}}
                                        </td>
                                        <td class="smaller text-left">{{pjt.description}}</td>
                                        <td class="text-center"><span class="badge badge-primary">{{pjt.program}}</span></td>
                                        <td class="text-left"><span class="badge badge-primary-inverted">{{pjt.category}}</span></td>
                                        <td class="text-left">{{pjt.countTask}}</td>
                                        <td class="text-right text-nowrap">Niv. 1: <b>{{pjt.sumBenef1}}</b><br> Niv. 2: <b>{{pjt.sumBenef2}}</b></td>
                                    </tr>
                                </tbody>
                            </table>

                        </td>
                    </tr>

                    <!-- >> REPORT PAGE -->
                    <tr>
                        <td colspan="4">

                            <legend class="mt-2 mb-2">
                                <translate>ACTIVIDADES</translate>
                            </legend>

                            <!-- TABLE TASKs -->
                            <table class="table table-sm smaller mt-0" v-if="tasks.length>0">
                                <thead>
                                    <tr>
                                        <th v-translate class="text-left">Nombre</th>
                                        <th v-translate class="text-center">Estatus</th>
                                        <th v-translate class="text-left">Description</th>
                                        <th v-translate class="text-left">Próximo Recordatorio</th>
                                        <th v-translate class="text-center">Beneficiarios</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(task, taskIndex ) in tasks" :key="taskIndex">
                                        <td class="text-left bolder">{{task.name}}</td>
                                        <td class="text-center smaller"><span :class="'badge badge-'+task.statusClass">{{task.statusName}}</span></td>
                                        <td class="smaller text-left">{{task.description}}</td>
                                        <td class="text-left"><small>{{task.lastReminderDate}}</small> <b>{{task.lastReminder}}</b></td>
                                        <td class="text-center bolder">{{task.benef_1}} | {{task.benef_2}} | {{task.benef_3}}</td>
                                    </tr>
                                </tbody>
                            </table>

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
            curDate: moment().format('YYYY-MM-DD'),
            from: moment().startOf('year').format('YYYY-MM-DD'),
            to: moment().endOf('year').format('YYYY-MM-DD'),
            ally: [],
            projects: [],
            operations: [],
            tasks: [],
            indicators: [],
            goals: [],
            categories_ids: [],
            proofs: [],
            chart: [],
            sumBenef1: 0,
            sumBenef2: 0,
            sumBenef3: 0,
            sumIncome: 0,
            sumCredit: 0,
            balance: 0,
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
            await axios.post(api.reportAlly, {
                    from: vm.from,
                    to: vm.to,
                    id: id
                })
                .then(function (response) {
                    if (response.data.success) {
                        if (response.data.share_token && vm.$route.params.id !== response.data.share_token) {
                            vm.$router.replace({name: 'AllyReport', params: {id: response.data.share_token}});
                        }

                        //DATA
                        vm.ally = response.data.ally[0];
                        vm.tasks = response.data.tasks;
                        vm.goals = response.data.goals;
                        vm.projects = response.data.projects;
                        vm.operations = response.data.operations;
                        vm.indicators = response.data.indicators;
                        vm.proofs = response.data.proofs;

                        //SUM DATA
                        vm.sumBenef1 = vm.tasks.reduce((prev, cur) => {
                            return Number(cur.benef_1) + prev
                        }, 0);
                        vm.sumBenef2 = vm.tasks.reduce((prev, cur) => {
                            return Number(cur.benef_2) + prev
                        }, 0);
                        vm.sumBenef3 = vm.tasks.reduce((prev, cur) => {
                            return Number(cur.benef_3) + prev
                        }, 0);

                        // DEFINE CATEGORIES_IDS FOR GOAL
                        _.each(vm.projects, function (item, index) {
                            if (!_.isNull(item.category_ids)) {
                                _.each(item.category_ids.split(','), function (value, key) {
                                    vm.categories_ids.push(value)
                                })
                            }
                        })
                        let formCategoryIds = []
                        if (_.isArray(vm.categories_ids)) {
                            _.uniq(vm.categories_ids).forEach(function (val, index) {
                                formCategoryIds.push(_.findWhere(vm.goals, {
                                    value: Number(val)
                                }))
                            })
                            vm.categories_ids = formCategoryIds
                        }

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

    }
}
</script>

<style>
/* On paper the tables' descriptions (rich text, often joined with &nbsp;) break instead of running off the page */
@media print {
    .ally-report td.smaller {
        overflow-wrap: anywhere;
    }
}
</style>
