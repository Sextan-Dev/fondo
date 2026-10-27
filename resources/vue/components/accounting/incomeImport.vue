<template>
    <!-- INCOME LAYOUT IMPORT: upload → review of the whole file → booking, all or nothing -->
    <div class="modal fade income-import" ref="modal" tabindex="-1" role="dialog" aria-labelledby="ii-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" :class="{'modal-lg': step != 'file'}" role="document">
            <form class="modal-content" ref="form" enctype="multipart/form-data" @submit.prevent="primaryAction">
                <div class="modal-header">
                    <h5 class="modal-title" id="ii-title" v-translate>Importar ingresos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" :disabled="busy"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <ol class="ii-steps" aria-label="Pasos">
                        <li v-for="(item, index) in steps" :key="item.key" :class="{'is-active': step == item.key, 'is-done': stepIndex > index}">
                            <span class="ii-step-number">{{index + 1}}</span>{{item.label}}
                        </li>
                    </ol>

                    <!-- 1. FILE -->
                    <div v-show="step == 'file'">
                        <p class="ii-lead">
                            Sube el layout de ingresos con el formato de la plantilla. Antes de guardar nada se revisa todo el archivo:
                            si tiene algún error, no se importa ningún ingreso.
                        </p>
                        <div class="ii-template">
                            <button type="button" class="btn btn-sm btn-white" @click="downloadTemplate" :disabled="downloading">
                                <i class="icon-feather-download"></i> {{downloading ? 'Generando...' : 'Descargar plantilla'}}
                            </button>
                            <span class="ii-hint">Trae listas con tus donadores, cuentas y clasificaciones actuales.</span>
                        </div>
                        <div class="form-group">
                            <label for="ii-file" v-translate>Archivo del layout (.xlsx)</label>
                            <input id="ii-file" ref="file" name="incomes" type="file" accept=".xlsx" class="form-control" :class="{'is-invalid': fileError}" @change="onFileChange" :disabled="busy">
                            <div class="invalid-feedback">{{fileError}}</div>
                        </div>
                        <el-checkbox v-model="createDonors" :disabled="busy">Crear los donadores que no existan</el-checkbox>
                        <p class="ii-hint ii-checkbox-hint">
                            Sin marcarla, un donador que no esté en el sistema se reporta como error. Los donadores que se creen quedan ligados a la carga.
                        </p>
                    </div>

                    <!-- 2. REVIEW -->
                    <div v-if="step == 'review' && result">
                        <div class="ii-verdict" :class="canImport ? 'is-ok' : 'is-error'" role="status">
                            <i :class="canImport ? 'icon-feather-check-circle' : 'icon-feather-alert-octagon'"></i>
                            <div>
                                <strong v-if="canImport">El archivo está listo para importar.</strong>
                                <strong v-else>{{count(result.errors_total)}} {{result.errors_total == 1 ? 'error' : 'errores'}}: no se importó nada.</strong>
                                <span v-if="canImport">Revisa el resumen{{result.warnings_total ? ' y los avisos' : ''}} y confirma.</span>
                                <span v-else>Corrígelos en el archivo y vuelve a subirlo.</span>
                            </div>
                        </div>

                        <div class="ii-stats">
                            <div class="ii-stat">
                                <span class="ii-stat-label">Ingresos</span>
                                <strong class="ii-stat-value">{{count(summary.valid_rows)}}<small v-if="summary.data_rows != summary.valid_rows"> de {{count(summary.data_rows)}}</small></strong>
                                <span class="ii-stat-hint">{{period}}</span>
                            </div>
                            <div class="ii-stat">
                                <span class="ii-stat-label">Importe</span>
                                <strong class="ii-stat-value">{{money(summary.total_amount)}}</strong>
                                <span class="ii-stat-hint">{{summary.data_rows != summary.valid_rows ? 'De las filas sin error' : 'Total del archivo'}}</span>
                            </div>
                            <div class="ii-stat">
                                <span class="ii-stat-label">Fee</span>
                                <strong class="ii-stat-value">{{money(summary.total_fee)}}</strong>
                                <span class="ii-stat-hint">Según el % de cada fila</span>
                            </div>
                            <div class="ii-stat">
                                <span class="ii-stat-label">Costo operativo</span>
                                <strong class="ii-stat-value">{{money(summary.total_co)}}</strong>
                                <span class="ii-stat-hint">{{summary.with_co ? count(summary.with_co) + ' con retención' : 'Sin retenciones'}}</span>
                            </div>
                        </div>

                        <p v-if="summary.donors_new.length" class="ii-note">
                            <i class="icon-feather-user-plus"></i>
                            Se {{summary.donors_new.length == 1 ? 'creará 1 donador' : 'crearán ' + summary.donors_new.length + ' donadores'}}:
                            {{summary.donors_new.slice(0, 8).join(', ')}}{{summary.donors_new.length > 8 ? '…' : ''}}
                        </p>

                        <div class="ii-tabs" role="tablist">
                            <button v-for="tab in tabs" :key="tab.key" type="button" role="tab" class="ii-tab" :class="['is-' + tab.key, {'is-active': activeTab == tab.key}]"
                                :aria-selected="activeTab == tab.key ? 'true' : 'false'" @click="activeTab = tab.key">
                                {{tab.label}} <span class="ii-tab-count">{{count(tab.total)}}</span>
                            </button>
                        </div>
                        <div class="ii-table-wrap">
                            <table v-if="activeTab != 'categories'" class="table table-sm ii-table is-messages">
                                <thead>
                                    <tr><th>Fila</th><th>Columna</th><th>Valor</th><th>{{activeTab == 'errors' ? 'Error' : 'Aviso'}}</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in messages" :key="index">
                                        <td class="ii-num ii-row">{{item.row || '—'}}</td>
                                        <td class="ii-column">{{item.column ? item.column + ' · ' + item.field : '—'}}</td>
                                        <td class="ii-value" :class="{'is-empty': !item.value}" :title="item.value">{{item.value || '—'}}</td>
                                        <td class="ii-message">{{item.message}}</td>
                                    </tr>
                                    <tr v-if="!messages.length">
                                        <td colspan="4" class="ii-empty">{{activeTab == 'errors' ? 'Sin errores.' : 'Sin avisos.'}}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <table v-else class="table table-sm ii-table">
                                <thead>
                                    <tr><th>Clasificación</th><th>Subclasificación</th><th class="text-right">Ingresos</th><th class="text-right">Importe</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in summary.categories" :key="index">
                                        <td>{{item.category}}</td>
                                        <td>{{item.subcategory || '—'}}</td>
                                        <td class="text-right ii-num">{{count(item.count)}}</td>
                                        <td class="text-right ii-num">{{money(item.amount)}}</td>
                                    </tr>
                                    <tr v-if="!summary.categories.length"><td colspan="4" class="ii-empty">Sin filas válidas.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="truncated" class="ii-hint">Se muestran los primeros {{messages.length}} de {{count(activeTotal)}}.</p>
                    </div>

                    <!-- 3. DONE -->
                    <div v-if="step == 'done' && imported" class="ii-done">
                        <i class="icon-feather-check-circle"></i>
                        <h6>{{doneMessage}}</h6>
                        <p class="ii-hint">Todos los registros quedaron marcados con la carga #{{imported.id}}. Si algo salió mal puedes revertirla completa desde el histórico de cargas.</p>
                    </div>

                    <div v-if="busy" class="ii-progress">
                        <p class="mb-2">{{step == 'review' ? 'Importando los ingresos...' : 'Revisando el archivo...'}} No cierres esta ventana.</p>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100"></div>
                        </div>
                    </div>
                </div>

                <!-- keyed: v-translate rewrites the text of a button Vue reuses in another step -->
                <div class="modal-footer">
                    <template v-if="step == 'file'">
                        <button key="file-cancel" type="button" class="btn btn-light" data-dismiss="modal" :disabled="busy" v-translate>Cancelar</button>
                        <button key="file-check" type="submit" class="btn btn-primary" :disabled="busy">{{busy ? 'Revisando...' : 'Revisar archivo'}}</button>
                    </template>
                    <template v-else-if="step == 'review'">
                        <button key="review-reset" type="button" class="btn btn-light mr-auto" @click="reset" :disabled="busy">Elegir otro archivo</button>
                        <button key="review-cancel" type="button" class="btn btn-light" data-dismiss="modal" :disabled="busy" v-translate>Cancelar</button>
                        <button v-if="canImport" key="review-import" type="submit" class="btn btn-primary" :disabled="busy">
                            {{busy ? 'Importando...' : 'Importar ' + count(summary.valid_rows) + (summary.valid_rows == 1 ? ' ingreso' : ' ingresos')}}
                        </button>
                    </template>
                    <template v-else>
                        <button key="done-history" type="button" class="btn btn-light mr-auto" @click="goToHistory">Ir al histórico</button>
                        <button key="done-records" type="button" class="btn btn-primary" @click="goToRecords">Ver los registros</button>
                    </template>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {downloadFile} from "../../core/helpers.js";

    const STEPS = [
        { key: 'file', label: 'Archivo' },
        { key: 'review', label: 'Revisión' },
        { key: 'done', label: 'Importado' }
    ];

    export default {
        data() {
            return {
                step: 'file',
                busy: false,
                downloading: false,
                createDonors: false,
                fileError: '',
                result: null,
                canImport: false,
                token: null,
                activeTab: 'errors',
                imported: null,
                doneMessage: ''
            }
        },
        computed: {
            steps() {
                return STEPS;
            },
            stepIndex() {
                return STEPS.findIndex(item => item.key == this.step);
            },
            summary() {
                return this.result ? this.result.summary : {};
            },
            period() {
                let from = this.summary.period_from;
                let to = this.summary.period_to;
                if (!from) {
                    return 'Sin filas válidas';
                }
                return from == to ? this.date(from) : this.date(from) + ' – ' + this.date(to);
            },
            tabs() {
                let tabs = [
                    { key: 'errors', label: 'Errores', total: this.result.errors_total },
                    { key: 'warnings', label: 'Avisos', total: this.result.warnings_total }
                ];
                if (this.summary.categories.length) {
                    tabs.push({ key: 'categories', label: 'Por clasificación', total: this.summary.categories.length });
                }
                return tabs;
            },
            messages() {
                return this.activeTab == 'errors' ? this.result.errors : this.result.warnings;
            },
            activeTotal() {
                return this.activeTab == 'errors' ? this.result.errors_total : this.result.warnings_total;
            },
            truncated() {
                return this.activeTab != 'categories' && this.activeTotal > this.messages.length;
            }
        },
        watch: {
            // What was checked depends on this choice: check again
            createDonors() {
                if (this.step == 'review') {
                    this.reset();
                }
            }
        },
        methods: {
            open() {
                this.reset();
                this.createDonors = false;
                $(this.$refs.modal).modal('show');
            },
            reset() {
                this.step = 'file';
                this.result = null;
                this.token = null;
                this.canImport = false;
                this.imported = null;
                this.fileError = '';
                if (this.$refs.file) {
                    this.$refs.file.value = '';
                }
            },
            count(value) {
                return (Number(value) || 0).toLocaleString('en');
            },
            money(value) {
                return '$' + this.formatPrice(Number(value) || 0);
            },
            date(value) {
                return moment(value, 'YYYY-MM-DD').format('DD/MM/YYYY');
            },
            onFileChange() {
                this.fileError = '';
            },
            primaryAction() {
                if (this.step == 'file') {
                    this.check();
                } else if (this.step == 'review' && this.canImport) {
                    this.confirm();
                }
            },
            async downloadTemplate() {
                this.downloading = true;
                try {
                    await downloadFile(api.importIncomesTemplate, {}, 'plantilla_ingresos.xlsx');
                } catch (error) {
                    toastr.error(error.message);
                }
                this.downloading = false;
            },
            check() {
                let vm = this;
                let file = vm.$refs.file.files[0];
                if (!file) {
                    vm.fileError = 'Selecciona el archivo del layout.';
                    return;
                }
                if (!/\.xlsx$/i.test(file.name)) {
                    vm.fileError = 'El layout debe ser un archivo de Excel (.xlsx).';
                    return;
                }
                let data = new FormData();
                data.append('incomes', file);
                data.append('create_donors', vm.createDonors ? 1 : 0);
                vm.busy = true;
                axios.post(api.importIncomesCheck, data)
                .then(function (response) {
                    if (response.data.success) {
                        vm.result = response.data.result;
                        vm.canImport = response.data.canImport;
                        vm.token = response.data.token;
                        vm.activeTab = vm.canImport ? (vm.result.warnings_total ? 'warnings' : 'categories') : 'errors';
                        vm.step = 'review';
                    } else {
                        vm.fileError = response.data.msg;
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
                .then(function () {
                    vm.busy = false;
                });
            },
            confirm() {
                let vm = this;
                vm.busy = true;
                axios.post(api.importIncomesConfirm, { token: vm.token })
                .then(function (response) {
                    if (response.data.success) {
                        vm.imported = response.data.history;
                        vm.doneMessage = response.data.msg;
                        vm.step = 'done';
                        vm.$emit('imported', response.data.history);
                    } else {
                        toastr.error(response.data.msg);
                        // the file stopped passing the validation: show why
                        if (response.data.result) {
                            vm.result = response.data.result;
                            vm.canImport = false;
                            vm.token = null;
                            vm.activeTab = 'errors';
                        }
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
                .then(function () {
                    vm.busy = false;
                });
            },
            hide(next) {
                $(this.$refs.modal).one('hidden.bs.modal', next).modal('hide');
            },
            goToRecords() {
                let id = this.imported.id;
                this.hide(() => this.$router.push({ name: 'OperationList', query: { carga: id } }).catch(() => {}));
            },
            goToHistory() {
                this.hide(() => this.$router.push({ name: 'OperationHistory' }).catch(() => {}));
            }
        },
        beforeDestroy() {
            // Leaving with the modal open would keep its backdrop over the next page.
            if ($(this.$refs.modal).hasClass('show')) {
                $(this.$refs.modal).modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            }
        }
    }
</script>

<style>
    .income-import {
        --ii-text: #2b3445;
        --ii-muted: #8a93a6;
        --ii-line: #e8ebf1;
        --ii-blue: #047bf8;
        --ii-green: #1f8a4c;
        --ii-green-soft: rgba(31, 138, 76, .1);
        --ii-red: #c94a4a;
        --ii-red-soft: rgba(201, 74, 74, .1);
        --ii-amber: #8a6100;
        --ii-amber-soft: rgba(251, 211, 100, .28);
        --ii-gray-soft: #f4f5f8;
        color: var(--ii-text);
    }
    .income-import .ii-steps {
        display: flex;
        gap: 1.25rem;
        margin: 0 0 1.1rem;
        padding: 0;
        list-style: none;
        font-size: .8rem;
        color: var(--ii-muted);
    }
    .income-import .ii-steps li {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .income-import .ii-step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 50%;
        border: 1px solid var(--ii-line);
        font-size: .72rem;
        font-weight: 600;
    }
    .income-import .ii-steps li.is-active {
        color: var(--ii-text);
        font-weight: 600;
    }
    .income-import .ii-steps li.is-active .ii-step-number {
        border-color: var(--ii-blue);
        background-color: var(--ii-blue);
        color: #fff;
    }
    .income-import .ii-steps li.is-done .ii-step-number {
        border-color: var(--ii-green);
        color: var(--ii-green);
    }
    .income-import .ii-lead {
        margin-bottom: .9rem;
    }
    .income-import .ii-template {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem .75rem;
        margin-bottom: 1rem;
        padding: .75rem;
        border-radius: 6px;
        background-color: var(--ii-gray-soft);
    }
    .income-import .ii-template .btn i {
        margin-right: .3rem;
    }
    .income-import .ii-hint {
        font-size: .78rem;
        color: var(--ii-muted);
    }
    .income-import .ii-checkbox-hint {
        margin: .25rem 0 0 1.5rem;
    }
    .income-import .ii-verdict {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        margin-bottom: 1rem;
        padding: .75rem .9rem;
        border-radius: 6px;
    }
    .income-import .ii-verdict i {
        font-size: 1.2rem;
        line-height: 1.3;
    }
    .income-import .ii-verdict strong {
        display: block;
    }
    .income-import .ii-verdict.is-ok {
        background-color: var(--ii-green-soft);
        color: var(--ii-green);
    }
    .income-import .ii-verdict.is-error {
        background-color: var(--ii-red-soft);
        color: var(--ii-red);
    }
    .income-import .ii-verdict span {
        color: var(--ii-text);
    }
    .income-import .ii-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .6rem;
        margin-bottom: .9rem;
    }
    .income-import .ii-stat {
        display: flex;
        flex-direction: column;
        min-width: 0;
        padding: .65rem .8rem;
        border: 1px solid var(--ii-line);
        border-radius: 6px;
    }
    .income-import .ii-stat-label {
        font-size: .66rem;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--ii-muted);
    }
    .income-import .ii-stat-value {
        font-size: 1.05rem;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
    .income-import .ii-stat-value small {
        font-size: .75rem;
        color: var(--ii-muted);
    }
    .income-import .ii-stat-hint {
        font-size: .72rem;
        color: var(--ii-muted);
    }
    .income-import .ii-note {
        padding: .55rem .75rem;
        border-radius: 6px;
        background-color: var(--ii-amber-soft);
        color: var(--ii-amber);
        font-size: .82rem;
    }
    .income-import .ii-note i {
        margin-right: .3rem;
    }
    .income-import .ii-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
        border-bottom: 1px solid var(--ii-line);
    }
    .income-import .ii-tab {
        margin-bottom: -1px;
        padding: .45rem .75rem;
        border: 0;
        border-bottom: 2px solid transparent;
        background: none;
        font-size: .82rem;
        color: var(--ii-muted);
        cursor: pointer;
    }
    .income-import .ii-tab.is-active {
        border-bottom-color: var(--ii-blue);
        color: var(--ii-text);
        font-weight: 600;
    }
    .income-import .ii-tab-count {
        display: inline-block;
        min-width: 1.4rem;
        padding: 0 .35rem;
        border-radius: 10px;
        background-color: var(--ii-gray-soft);
        font-size: .72rem;
        text-align: center;
    }
    .income-import .ii-tab.is-errors .ii-tab-count {
        background-color: var(--ii-red-soft);
        color: var(--ii-red);
    }
    .income-import .ii-tab.is-warnings .ii-tab-count {
        background-color: var(--ii-amber-soft);
        color: var(--ii-amber);
    }
    .income-import .ii-table-wrap {
        max-height: 320px;
        overflow: auto;
    }
    .income-import .ii-table {
        margin-bottom: 0;
        font-size: .8rem;
    }
    /* the theme centers table cells */
    .income-import .ii-table th,
    .income-import .ii-table td {
        text-align: left;
    }
    .income-import .ii-table th.text-right,
    .income-import .ii-table td.text-right {
        text-align: right;
    }
    .income-import .ii-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background-color: #fff;
        font-size: .68rem;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: var(--ii-muted);
        white-space: nowrap;
    }
    .income-import .ii-num {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .income-import .ii-table td:nth-child(2) {
        white-space: nowrap;
    }
    .income-import .ii-value {
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .income-import .ii-empty {
        padding: 1rem;
        text-align: center;
        color: var(--ii-muted);
    }
    .income-import .ii-done {
        padding: 1rem 0 .5rem;
        text-align: center;
    }
    .income-import .ii-done > i {
        font-size: 2.2rem;
        color: var(--ii-green);
    }
    .income-import .ii-done h6 {
        margin: .5rem 0;
    }
    .income-import .ii-progress {
        margin-top: 1rem;
    }
    /* Phones: each message as a block instead of a four column row */
    @media (max-width: 575px) {
        .income-import .ii-table.is-messages thead {
            display: none;
        }
        .income-import .ii-table.is-messages,
        .income-import .ii-table.is-messages tbody,
        .income-import .ii-table.is-messages tr {
            display: block;
        }
        .income-import .ii-table.is-messages tr {
            padding: .55rem 0;
            border-bottom: 1px solid var(--ii-line);
        }
        .income-import .ii-table.is-messages td {
            display: block;
            padding: 0;
            border: 0;
        }
        .income-import .ii-table.is-messages td.ii-row,
        .income-import .ii-table.is-messages td.ii-column {
            display: inline;
            font-weight: 600;
        }
        .income-import .ii-table.is-messages td.ii-row::before {
            content: 'Fila ';
        }
        .income-import .ii-table.is-messages td.ii-row::after {
            content: ' · ';
        }
        .income-import .ii-table.is-messages td.ii-value {
            max-width: none;
            color: var(--ii-muted);
        }
        .income-import .ii-table.is-messages td.ii-value::before {
            content: 'Valor: ';
        }
        .income-import .ii-table.is-messages td.ii-value.is-empty {
            display: none;
        }
    }
    @media (max-width: 767px) {
        .income-import .ii-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .income-import .ii-steps {
            gap: .75rem;
        }
    }
</style>
