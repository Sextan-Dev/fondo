<template>
    <div class="content-box operation-list">
        <div class="element-wrapper">
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header ol-header">
                <default-header></default-header>
                <span class="ol-header-actions">
                    <button @click.prevent="$router.push({name:'OperationForm'})" class="btn btn-sm btn-primary">
                        <i class="icon-feather-plus"></i><span><translate>Nueva Operación</translate></span>
                    </button>
                    <span class="dropdown">
                        <button class="btn btn-sm btn-white dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Importar" aria-label="Importar">
                            <i class="icon-feather-upload"></i><span><translate>Importar</translate></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a v-for="(item, key) in availableImports" :key="key" class="dropdown-item" href="#" @click.prevent="openImport(key)">{{item.label}}</a>
                        </div>
                    </span>
                    <button @click.prevent="$router.push({name:'OperationHistory'})" class="btn btn-sm btn-white" title="Histórico Ingresos" aria-label="Histórico Ingresos">
                        <i class="icon-feather-clock"></i><span><translate>Histórico Ingresos</translate></span>
                    </button>
                </span>
            </h6>

            <!-- ONE IMPORT: every operation it booked, whatever the period -->
            <div v-if="importId" class="element-box ol-import-banner" role="status">
                <i class="icon-feather-layers"></i>
                <div class="ol-import-text">
                    <strong>Registros de la carga #{{importId}}</strong>
                    <span v-if="importInfo">
                        {{plain(importInfo.original_name)}} · {{count(importInfo.rows_count)}} ingresos · subida el {{shortDate(String(importInfo.date).slice(0, 10))}}
                        <span v-if="importInfo.status == 0" class="ol-import-reverted">Revertida</span>
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-white" @click="clearImport">
                    <i class="icon-feather-x"></i> <translate>Ver todas las operaciones</translate>
                </button>
            </div>

            <!-- FEE and counts of the whole history: they don't change with the filters -->
            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>FEE & Costo de Operación</h6>
                <div class="row">
                    <div class="form-check form-filter-large col-md-3" >
                        <a class="element-box el-tablo">
                            <div class="label mb-2">
                                <span>FEE</span>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                    <span><small>$</small>{{ formatPrice(fee||0).split('.')[0]}}<small><small>.{{ formatPrice(fee||0).split('.')[1]}}</small></small></span>
                                </label>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>Tipo de Operacion</h6>
                <div class="row">
                    <div class="form-check form-filter-large col-md-3" v-for="(cat , index) in types" :key="index" >
                        <a class="element-box el-tablo">
                            <div class="label mb-2">
                                <translate>Operación</translate>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                    <input class="form-check-input" name="category" @change="onCategoryChange" type="radio" :value="cat.value" v-model="category" >
                                    <span>{{cat.name}}</span>
                                </label>
                            </div>
                            <div class="trending hidden-sm-up hidden-md-down">
                                <span class="badge badge-primary">{{cat.count}}</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- FILTERS: one line on phones until opened, so the operations show without scrolling -->
            <div v-show="!importId" class="element-box ol-filters" :class="{'is-open': filtersOpen}">
                <button type="button" class="ol-filters-toggle" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen ? 'true' : 'false'" aria-controls="ol-filters-body">
                    <i class="icon-feather-sliders"></i>
                    <span class="ol-filters-summary">{{filtersSummary}}</span>
                    <i class="icon-feather-chevron-down ol-filters-chevron"></i>
                </button>
                <div class="ol-filters-body" id="ol-filters-body">
                    <div class="ol-filter ol-filter-period">
                        <label v-translate>Periodo</label>
                        <el-date-picker
                            v-model="range"
                            type="daterange"
                            unlink-panels
                            :editable="false"
                            :clearable="false"
                            :picker-options="pickerOptions"
                            @change="onFiltersChange"
                            range-separator="–"
                            format="dd/MM/yyyy"
                            value-format="yyyy-MM-dd"
                            start-placeholder="Desde"
                            end-placeholder="Hasta">
                        </el-date-picker>
                    </div>
                    <div class="ol-filter">
                        <label v-translate>Donantes/Aliados/Proveedores</label>
                        <el-select v-model="donor" filterable @change="onFiltersChange" placeholder="...">
                            <el-option key="all" label="TODOS" value="all"></el-option>
                            <el-option-group v-for="group in donorGroups" :key="group.label" :label="group.label">
                                <el-option v-for="item in group.options" :key="item.id" :label="item.contact" :value="item.id"></el-option>
                            </el-option-group>
                        </el-select>
                    </div>
                    <div class="ol-filter">
                        <label v-translate>Proyectos</label>
                        <el-select v-model="project" filterable @change="onFiltersChange" placeholder="...">
                            <el-option key="all" label="TODOS" value="all"></el-option>
                            <el-option v-for="item in optionsProjects" :key="item.id" :label="item.name" :value="item.id"></el-option>
                        </el-select>
                    </div>
                    <button v-if="filtersChanged" type="button" class="btn btn-sm btn-link ol-clear" @click="resetFilters">
                        <i class="icon-feather-x"></i> <translate>Limpiar filtros</translate>
                    </button>
                </div>
            </div>

            <!-- DataTable: clearfix keeps the floated pagination from narrowing what follows -->
            <div class="element-box-tp clearfix">
                <table class="table table-padded ol-table" id="operationList" ref="table" width="100%"></table>
            </div>
        </div>

        <!-- UPLOAD LAYOUT MODAL (one for every import type) -->
        <div class="modal fade" ref="importModal" tabindex="-1" role="dialog" aria-labelledby="ol-import-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" ref="importForm" enctype="multipart/form-data" data-vv-scope="import" @submit.prevent="submitImport">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ol-import-title">{{importing.title}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" :disabled="uploading"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning borderless">
                            <p>
                                <translate>Aquí puedes importar un layout con la lista de</translate> {{importing.entity}}
                                <translate>que desees registrar en el sistema. Es importante tener un buen conocimiento de las consecuencias antes de cargar cualquier documento.</translate>
                            </p>
                            <p class="mb-0">
                                <translate>Importante.- Para una carga exitosa es importante respetar el formato preestablecido, favor de usar el siguiente documento como referencia</translate>
                                <b><a target="_blank" rel="noopener" :href="appDomain + importing.layout">{{importing.layout.split('/').pop()}}</a></b>
                            </p>
                        </div>
                        <div class="form-group mb-0" v-show="!uploading">
                            <label for="ol-import-file" v-translate>Agregar Archivo</label>
                            <input :key="importKey" id="ol-import-file" ref="importFile" :name="importKey" type="file" accept=".xlsx" class="form-control"
                                :class="{'is-invalid': errors.has('import.' + importKey)}" v-validate="'required|ext:xlsx'">
                            <div class="invalid-feedback">{{errors.first('import.' + importKey)}}</div>
                        </div>
                        <div v-show="uploading">
                            <p class="mb-2" v-translate>Cargando Documento... Esto puede tardar algunos minutos, por favor no cierre esta ventana hasta que el proceso finalice, gracias.</p>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="uploading" v-translate>Cerrar</button>
                        <button type="submit" class="btn btn-primary" :disabled="uploading">
                            <span v-if="uploading"><translate>Subiendo</translate>...</span>
                            <span v-else v-translate>Subir</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <income-import ref="incomeImport" @imported="onImported"></income-import>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {escapeHtml, plainText} from "../../core/helpers.js";
    import IncomeImport from "./incomeImport.vue";

    // Kept for the session, so coming back from an edit shows the same operations.
    const FILTERS_KEY = 'operationList.filters';

    const IMPORTS = {
        // checked and confirmed in its own modal (incomeImport.vue)
        incomes: { label: 'Ingresos', support: false },
        projects: { label: 'Proyectos', entity: 'Proyectos', title: 'Registro masivo de Proyectos y Costo Operativo', url: 'importMasiveProjects', layout: '/files/operations/imports/layout.csv', support: true },
        outcomes: { label: 'Egresos', entity: 'Egresos', title: 'Registro masivo de Egresos', url: 'importMasiveOutcomes', layout: '/files/operations/imports/layout.csv', support: true },
        transfers: { label: 'Traspasos', entity: 'Traspasos', title: 'Registro masivo de Traspasos', url: 'importMasiveTransfers', layout: '/files/operations/imports/layout.csv', support: true }
    };

    const TYPES = {
        income: { label: 'Ingreso', tone: 'income', icon: 'icon-feather-arrow-down-left' },
        outcome: { label: 'Egreso', tone: 'outcome', icon: 'icon-feather-arrow-up-right' },
        balance: { label: 'Balance', tone: 'balance', icon: 'icon-feather-dollar-sign' }
    };


    // Display columns that Responsive may hide: their content then shows inside the operation cell.
    const INLINE_COLUMNS = ['date', 'category', 'donor', 'pay', 'retention', 'amount', 'actions'];

    const CONTACT_GROUPS = [['Donador', 'Donadores'], ['Aliado', 'Aliados'], ['Proveedor', 'Proveedores']];

    function defaultRange() {
        return [moment().startOf('year').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD')];
    }

    function storedFilters() {
        try {
            return JSON.parse(sessionStorage.getItem(FILTERS_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function shortcut(text, from, to) {
        return { text: text, onClick: picker => picker.$emit('pick', [from().toDate(), to().toDate()]) };
    }

    export default {
        components: { IncomeImport },
        props:['appDomain','siteName', 'auth'],
        data(){
            let saved = storedFilters();
            return {
                range: Array.isArray(saved.range) ? saved.range : defaultRange(),
                category: saved.category || 'all',
                donor: saved.donor || 'all',
                project: saved.project || 'all',
                optionsDonors: [],
                optionsProjects: [],
                datatable: null,
                // Counts per type and FEE of the whole history (operation/index)
                types: [],
                fee: 0,
                filtersOpen: false,
                // The import whose operations are shown (?carga=), and its history row
                importId: Number(this.$route.query.carga) || null,
                importInfo: null,
                importKey: 'outcomes',
                uploading: false,
                pickerOptions: {
                    firstDayOfWeek: 1,
                    shortcuts: [
                        shortcut('Este mes', () => moment().startOf('month'), () => moment().endOf('month')),
                        shortcut('Mes anterior', () => moment().subtract(1, 'month').startOf('month'), () => moment().subtract(1, 'month').endOf('month')),
                        shortcut('Últimos 12 meses', () => moment().subtract(11, 'month').startOf('month'), () => moment().endOf('month')),
                        shortcut('Este año', () => moment().startOf('year'), () => moment().endOf('year')),
                        shortcut('Año anterior', () => moment().subtract(1, 'year').startOf('year'), () => moment().subtract(1, 'year').endOf('year'))
                    ]
                }
            }
        },
        computed: {
            availableImports() {
                let support = this.auth && this.auth.user == 'support';
                return _.pick(IMPORTS, item => support || !item.support);
            },
            importing() {
                return IMPORTS[this.importKey];
            },
            donorGroups() {
                return CONTACT_GROUPS
                    .map(([category, label]) => ({ label: label, options: this.optionsDonors.filter(item => item.category == category) }))
                    .filter(group => group.options.length);
            },
            filtersChanged() {
                return this.donor != 'all' || this.project != 'all' || !_.isEqual(this.range, defaultRange());
            },
            // What the folded filters show on phones
            filtersSummary() {
                let parts = [this.shortDate(this.range[0]) + ' – ' + this.shortDate(this.range[1])];
                if (this.donor != 'all') {
                    let donor = this.optionsDonors.find(item => item.id == this.donor);
                    parts.push(donor ? donor.contact : 'Un contacto');
                }
                if (this.project != 'all') {
                    let project = this.optionsProjects.find(item => item.id == this.project);
                    parts.push(project ? project.name : 'Un proyecto');
                }
                return parts.join(' · ');
            }
        },
        methods: {
            money(value) {
                value = Number(value) || 0;
                return (Math.round(value * 100) < 0 ? '-$' : '$') + this.formatPrice(Math.abs(value));
            },
            count(value) {
                return (Number(value) || 0).toLocaleString('en');
            },
            shortDate(value) {
                let date = new Date(plainText(value) + 'T00:00:00');
                return isNaN(date) ? '' : date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
            },
            plain(value) {
                return plainText(value);
            },
            // FILTERS
            saveFilters() {
                try {
                    sessionStorage.setItem(FILTERS_KEY, JSON.stringify({ range: this.range, category: this.category, donor: this.donor, project: this.project }));
                } catch (e) {
                    // private mode or storage disabled: the filters just won't survive the next visit
                }
            },
            onFiltersChange() {
                this.saveFilters();
                this.reloadTable();
            },
            onCategoryChange() {
                this.saveFilters();
                this.reloadTable();
            },
            resetFilters() {
                this.range = defaultRange();
                this.donor = 'all';
                this.project = 'all';
                this.onFiltersChange();
            },
            loadFilterOptions: async function(){
                var vm = this;
                await axios.post(api.listOperation)
                .then(function (response) {
                    if(response.data.success){
                        vm.optionsDonors = response.data.contacts
                        vm.optionsProjects = response.data.projects
                        vm.types = response.data.type
                        vm.fee = response.data.fee
                    }else{
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
            },
            // RELOAD DATATABLE
            reloadTable(keepPage) {
                if (this.datatable) {
                    this.datatable.ajax.reload(null, !keepPage);
                }
            },

            // IMPORTS
            openImport(key) {
                if (key == 'incomes') {
                    this.$refs.incomeImport.open();
                    return;
                }
                this.importKey = key;
                this.$nextTick(() => {
                    this.errors.clear('import');
                    if (this.$refs.importFile) {
                        this.$refs.importFile.value = '';
                    }
                    $(this.$refs.importModal).modal('show');
                });
            },
            submitImport() {
                var vm = this;
                vm.$validator.validateAll('import').then(function (result) {
                    if (!result) {
                        return;
                    }
                    vm.uploading = true;
                    axios.post(api[vm.importing.url], new FormData(vm.$refs.importForm))
                    .then(function (response) {
                        if(response.data.success){
                            toastr.success(response.data.msg);
                            $(vm.$refs.importModal).modal('hide');
                            vm.reloadTable();
                        }else{
                            toastr.error(response.data.msg);
                        }
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                    })
                    .then(function () {
                        vm.uploading = false;
                        if (vm.$refs.importFile) {
                            vm.$refs.importFile.value = '';
                        }
                    });
                });
            },

            onImported() {
                this.reloadTable();
            },
            loadImportInfo() {
                var vm = this;
                vm.importInfo = null;
                if (!vm.importId) {
                    return;
                }
                axios.post(api.historyOperationShow, { id: vm.importId })
                .then(function (response) {
                    if (response.data.success) {
                        vm.importInfo = response.data.history;
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
            },
            showImport(id) {
                this.$router.push({ name: 'OperationList', query: { carga: id } }).catch(() => {});
            },
            clearImport() {
                this.$router.replace({ name: 'OperationList' }).catch(() => {});
            },

            // UPDATE ACTIVE 1 -> 0
            deleteItem(id, type){
                swal({
                    title: "Seguro ?",
                    html: (type=='transfer' ? "Estas a punto de eliminar un traspaso, esta acción eliminara no solo el registro seleccionado sino tambien el Ingreso y/o Egreso relacionado a este, deseas continuar?" : "Estas a punto de eliminar este registro, deseas continuar?"),
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                }).then((confirm) => {
                    if (confirm.value) {
                        var vm = this;
                        axios.post(api.deleteOperation, {id:id})
                        .then(function (response) {
                            if(response.data.success){
                                vm.reloadTable(true);
                                swal({ html : response.data.msg, type: "success", timer: 1500 });
                            }else{
                                swal.close();
                                toastr.error(response.data.msg);
                            }
                        })
                        .catch(function (error) {
                            console.log(error);
                        })
                    } else {
                        swal({title : "Operación Cancelada", type : "error", timer:1200});
                    }
                });
            },
            onAction(event) {
                let target = $(event.currentTarget);
                // let ctrl/cmd-click open the edit form in a new tab
                if (event.ctrlKey || event.metaKey || event.shiftKey) {
                    return;
                }
                event.preventDefault();
                target.tooltip('hide');
                let id = target.attr('data-id');
                switch (target.attr('data-action')) {
                    case 'edit':
                        this.$router.push({ name: 'OperationForm', params: { id: id } });
                        break;
                    case 'delete':
                        this.deleteItem(id, target.attr('data-transfer') == 1 ? 'transfer' : 'item');
                        break;
                    case 'import':
                        this.showImport(id);
                        break;
                }
            },

            // VALUES shared by the cells and the exports. The server escapes every value.
            signedAmount(row) {
                let value = Number(plainText(row.amount)) || 0;
                return row.type == 'outcome' ? -value : value;
            },
            // FEE and operating cost retention only apply to incomes
            retentions(row) {
                if (row.type == 'outcome') {
                    return [];
                }
                let fee = Number(plainText(row.fee)) || 0;
                let coPercent = Number(plainText(row.percentage_operating_cost)) || 0;
                let co = Number(plainText(row.val_operating_cost)) || 0;
                let lines = [];
                if (fee) {
                    lines.push({ label: 'FEE', amount: (Number(plainText(row.amount)) || 0) * fee / 100, percent: fee });
                }
                if (co || coPercent) {
                    lines.push({ label: 'CO', amount: co, percent: coPercent });
                }
                return lines;
            },
            exportDate(data) {
                return data ? moment(plainText(data), 'YYYY-MM-DD').format('DD/MM/YYYY') : '';
            },

            // CELLS: markup only, the hidden export columns give the exports their plain values
            muted(text) {
                return '<span class="ol-muted">' + text + '</span>';
            },
            amountBlock(row) {
                let info = TYPES[row.type] || TYPES.outcome;
                let reference = plainText(row.transfer_reference);
                let kind = row.transfer == 1
                    ? '<span class="ol-kind is-transfer"' + (reference ? ' title="Clave de traspaso ' + escapeHtml(reference) + '"' : '') + '><i class="icon-feather-repeat"></i>Traspaso</span>'
                    : '<span class="ol-kind"><i class="' + info.icon + '"></i>' + info.label + '</span>';
                return '<span class="ol-amount is-' + info.tone + '"><span class="ol-money">' + this.money(this.signedAmount(row)) + '</span>' + kind + '</span>';
            },
            categoryTags(row) {
                let category = plainText(row.category);
                let subcategory = plainText(row.subcategory);
                // AVN, Costo Operativo... repeat the category as subcategory
                return (category ? '<span class="ol-tag">' + escapeHtml(category) + '</span>' : '') +
                    (subcategory && subcategory != category ? '<span class="ol-tag-sub">' + escapeHtml(subcategory) + '</span>' : '');
            },
            payment(row) {
                let mode = plainText(row.mode);
                let folio = plainText(row.account);
                return { mode: mode ? escapeHtml(mode) : '', folio: folio ? 'Folio ' + escapeHtml(folio) : '' };
            },
            retentionLines(row) {
                return this.retentions(row).map(line => '<span class="ol-ret-line"><span class="ol-ret-label">' + line.label + '</span>' +
                    this.money(line.amount) + ' <span class="ol-muted">' + line.percent + '%</span></span>').join('');
            },
            actionButtons(row) {
                let id = escapeHtml(row.id);
                let edit = row.transfer == 1
                    ? '<span class="ol-btn is-disabled tip" tabindex="0" title="No es posible editar un registro realizado por medio de un traspaso." aria-label="Editar no disponible"><i class="icon-feather-edit"></i></span>'
                    : '<a href="/operation/form/' + id + '" class="ol-btn tip" title="Editar" aria-label="Editar" data-action="edit" data-id="' + id + '"><i class="icon-feather-edit"></i></a>';
                return '<div class="ol-actions">' + edit +
                    '<a href="#" class="ol-btn is-danger tip" title="Eliminar" aria-label="Eliminar" data-action="delete" data-id="' + id + '" data-transfer="' + (row.transfer == 1 ? 1 : 0) + '"><i class="icon-feather-trash-2"></i></a>' +
                    '</div>';
            },
            // Contact, concept and project; plus what Responsive hid as a column, so no row has to be expanded.
            operationCell(data, type, row) {
                if (type !== 'display') {
                    return '';
                }
                let id = escapeHtml(row.id);
                let contact = plainText(row.client) ? escapeHtml(row.client) : 'Sin contacto';
                let importId = Number(plainText(row.history_operation_id));
                let parts = [
                    '<span class="ol-inline is-date ol-eyebrow">' + escapeHtml(this.shortDate(row.date)) + '</span>',
                    row.transfer == 1
                        ? '<span class="ol-contact">' + contact + '</span>'
                        : '<a href="/operation/form/' + id + '" class="ol-contact" data-action="edit" data-id="' + id + '">' + contact + '</a>'
                ];
                if (plainText(row.comment)) {
                    parts.push('<span class="ol-concept" title="' + escapeHtml(row.comment) + '">' + escapeHtml(row.comment) + '</span>');
                }
                if (plainText(row.project_name)) {
                    parts.push('<span class="ol-project" title="' + escapeHtml(row.project_name) + '"><i class="icon-feather-folder"></i><span>' + escapeHtml(row.project_name) + '</span></span>');
                }
                // Booked by an income import: opens every operation of that import
                if (importId && !this.importId) {
                    parts.push('<a href="/operation/list?carga=' + importId + '" class="ol-import-tag" title="Ver todos los registros de esta carga" data-action="import" data-id="' + importId + '"><i class="icon-feather-layers"></i><span>Carga #' + importId + '</span></a>');
                }
                let tags = this.categoryTags(row);
                if (tags) {
                    parts.push('<span class="ol-inline is-category">' + tags + '</span>');
                }
                if (plainText(row.donor)) {
                    parts.push('<span class="ol-inline is-donor ol-detail"><span class="ol-detail-label">Donador</span>' + escapeHtml(row.donor) + '</span>');
                }
                let pay = this.payment(row);
                if (pay.mode || pay.folio) {
                    parts.push('<span class="ol-inline is-pay ol-detail"><span class="ol-detail-label">Pago</span>' + [pay.mode, pay.folio].filter(Boolean).join(' · ') + '</span>');
                }
                let retentions = this.retentionLines(row);
                if (retentions) {
                    parts.push('<span class="ol-inline is-retention ol-detail"><span class="ol-detail-label">Retenciones</span>' + retentions + '</span>');
                }
                parts.push('<span class="ol-inline is-amount">' + this.amountBlock(row) + '</span>');
                parts.push('<span class="ol-inline is-actions">' + this.actionButtons(row) + '</span>');
                return '<div class="ol-op">' + parts.join('') + '</div>';
            },
            dateCell(data, type) {
                return type !== 'display' ? '' : '<span class="ol-date">' + escapeHtml(this.shortDate(data)) + '</span>';
            },
            categoryCell(data, type, row) {
                return type !== 'display' ? '' : (this.categoryTags(row) || this.muted('—'));
            },
            donorCell(data, type) {
                if (type !== 'display') {
                    return '';
                }
                return plainText(data) ? '<span class="ol-donor" title="' + escapeHtml(data) + '">' + escapeHtml(data) + '</span>' : this.muted('—');
            },
            payCell(data, type, row) {
                if (type !== 'display') {
                    return '';
                }
                let pay = this.payment(row);
                if (!pay.mode && !pay.folio) {
                    return this.muted('—');
                }
                return '<span class="ol-pay">' + (pay.mode || this.muted('—')) + (pay.folio ? '<span class="ol-sub">' + pay.folio + '</span>' : '') + '</span>';
            },
            retentionCell(data, type, row) {
                return type !== 'display' ? '' : (this.retentionLines(row) || this.muted('—'));
            },
            amountCell(data, type, row) {
                return type !== 'display' ? '' : this.amountBlock(row);
            },
            actionsCell(data, type, row) {
                return type !== 'display' ? '' : this.actionButtons(row);
            },
            // Server-side processing only holds the current page: exports first fetch every filtered row.
            exportButton(extend, text) {
                var vm = this;
                return {
                    extend: extend,
                    text: text,
                    exportOptions: { columns: ':not(.no-export)', orthogonal: 'export' },
                    action: function (e, dt, button, config) {
                        let self = this;
                        let base = $.fn.dataTable.ext.buttons[extend + 'Html5'] || $.fn.dataTable.ext.buttons[extend];
                        let start = dt.page.info().start;
                        dt.one('preXhr', function (xhrEvent, settings, data) {
                            data.start = 0;
                            data.length = -1;
                            // export the full set instead of drawing it, then load the page that was on screen
                            dt.one('preDraw', function () {
                                base.action.call(self, e, dt, button, config);
                                dt.one('preXhr', function (backEvent, backSettings, backData) {
                                    backSettings._iDisplayStart = start;
                                    backData.start = start;
                                });
                                setTimeout(() => dt.ajax.reload(null, false), 0);
                                return false;
                            });
                        });
                        dt.ajax.reload(null, false);
                    }
                };
            },
            initTable() {
                var vm = this;
                let text = data => plainText(data);
                let number = data => Number(plainText(data)) || 0;
                let exportOnly = (value, column) => $.extend({ visible: false, render: (data, type, row) => type === 'display' ? '' : value(data, row) }, column);
                // The table shows the 'no-export' columns (searchable: false, the export ones are searched instead).
                // responsivePriority: lower stays visible longer; what Responsive hides shows inside the operation cell.
                // Excel, CSV, copy and print get the hidden columns below, in the order they always had.
                let columns = [
                    { key: 'id', data: 'id', name: 'o.id', visible: false, searchable: false, className: 'no-export' },
                    { key: 'date', data: 'date', name: 'o.date', title: 'Fecha', className: 'no-export', searchable: false, responsivePriority: 3, render: vm.dateCell },
                    { key: 'operation', data: 'client', name: 'ct.name', title: 'Operación', className: 'no-export', searchable: false, responsivePriority: 1, render: vm.operationCell },
                    { key: 'category', data: 'category', name: 'o.category', title: 'Clasificación', className: 'no-export', searchable: false, responsivePriority: 5, render: vm.categoryCell },
                    { key: 'donor', data: 'donor', name: 'd.name', title: 'Donador', className: 'no-export', searchable: false, responsivePriority: 6, render: vm.donorCell },
                    { key: 'pay', data: 'mode', name: 'ca.name', title: 'Pago', className: 'no-export', searchable: false, responsivePriority: 7, render: vm.payCell },
                    { key: 'retention', data: 'fee', name: 'o.fee', title: 'Retenciones', className: 'no-export', orderable: false, searchable: false, responsivePriority: 8, render: vm.retentionCell },
                    { key: 'amount', data: 'amount', name: 'o.amount', title: 'Importe', className: 'no-export text-right', searchable: false, responsivePriority: 2, render: vm.amountCell },
                    { key: 'actions', data: 'id', name: 'o.id', title: '', className: 'no-export text-right', orderable: false, searchable: false, responsivePriority: 4, render: vm.actionsCell },
                    exportOnly(vm.exportDate, { data: 'date', name: 'o.date', title: 'Fecha', searchable: false }),
                    exportOnly(data => (TYPES[data] || TYPES.outcome).label, { data: 'type', name: 'o.type', title: 'Tipo', searchable: false }),
                    exportOnly((data, row) => vm.signedAmount(row), { data: 'amount', name: 'o.amount', title: 'Importe' }),
                    exportOnly(text, { data: 'client', name: 'ct.name', title: 'Contacto' }),
                    exportOnly(text, { data: 'project_name', name: 'p.name', title: 'Proyecto' }),
                    exportOnly(text, { data: 'donor', name: 'd.name', title: 'Donador' }),
                    exportOnly(text, { data: 'category', name: 'o.category', title: 'Clasificación' }),
                    exportOnly(text, { data: 'subcategory', name: 'o.subcategory', title: 'Sub Clasificación' }),
                    exportOnly(text, { data: 'account', name: 'o.account', title: 'Folio' }),
                    exportOnly(text, { data: 'mode', name: 'ca.name', title: 'Forma de pago' }),
                    exportOnly((data, row) => row.type == 'outcome' ? '' : (Number(plainText(row.amount)) || 0) * number(data) / 100, { data: 'fee', name: 'o.fee', title: 'FEE', searchable: false }),
                    exportOnly((data, row) => row.type == 'outcome' ? '' : number(data), { data: 'val_operating_cost', name: 'o.val_operating_cost', title: 'Retención CO', searchable: false }),
                    exportOnly(number, { data: 'fee', name: 'o.fee', title: '% FEE', searchable: false }),
                    exportOnly(number, { data: 'percentage_operating_cost', name: 'o.percentage_operating_cost', title: '% Retención CO', searchable: false }),
                    exportOnly(text, { data: 'transfer_reference', name: 'o.transfer_reference', title: 'Clave de Traspaso' }),
                    exportOnly(text, { data: 'comment', name: 'o.comment', title: 'Comentarios' })
                ];
                let col = key => columns.findIndex(column => column.key === key);
                let syncInline = visible => INLINE_COLUMNS.forEach(key => $(vm.$refs.table).toggleClass('ol-show-' + key, !visible[col(key)]));

                vm.datatable = $(vm.$refs.table).DataTable({
                    columns: columns,
                    serverSide: true,
                    searchDelay: 400,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    // As in the previous version: by id, oldest first
                    order: [[col('id'), 'asc']],
                    stateSave: true,
                    stateDuration: -1,
                    stateSaveParams: (settings, state) => { state.idOrder = true; },
                    stateLoadParams: (settings, state) => {
                        // Visibility isn't up to the user here: a saved state must not show the export columns.
                        (state.columns || []).forEach(column => delete column.visible);
                        // States saved while the list was ordered by date: start again from the id order
                        if (!state.idOrder) {
                            delete state.order;
                            delete state.start;
                        }
                    },
                    buttons: [
                        {
                            extend: 'collection',
                            text: '<i class="icon-feather-download"></i> ',
                            className: 'btn btn-table',
                            buttons: [vm.exportButton('excel', 'Excel'), vm.exportButton('csv', 'CSV'), vm.exportButton('copy', 'Copy')]
                        },
                        $.extend(vm.exportButton('print', '<i class="icon-feather-printer"></i>'), { className: 'btn btn-table' })
                    ],
                    language: {
                        processing: '<i class="fa fa-spinner fa-2x fa-spin"></i>',
                        info: '_START_–_END_ de _TOTAL_',
                        infoEmpty: '0 operaciones',
                        infoFiltered: '',
                        emptyTable: 'No hay operaciones con estos filtros',
                        zeroRecords: 'Ninguna operación coincide con la búsqueda'
                    },
                    responsive: { details: false },
                    ajax: {
                        url: api.selectOperation,
                        type: "POST",
                        data: function (data) {
                            $.extend(data, {
                                category: vm.category,
                                project: vm.importId ? 'all' : vm.project,
                                from: vm.range[0],
                                to: vm.range[1],
                                contact: vm.importId ? 'all' : vm.donor,
                                history: vm.importId || ''
                            });
                        },
                        dataSrc: function (json) {
                            if (json.success === false) {
                                toastr.error(json.msg);
                                json.recordsTotal = json.recordsFiltered = 0;
                                return [];
                            }
                            return json.data;
                        }
                    },
                    drawCallback: function () {
                        $(vm.$refs.table).find('.tip').tooltip({ container: 'body' });
                    },
                    initComplete: function () {
                        syncInline(this.api().columns().responsiveHidden().toArray());
                    }
                });
                vm.datatable.on('responsive-resize', (event, datatable, visible) => syncInline(visible));
                $(vm.$refs.table).on('click', '[data-action]', vm.onAction);
            }
        },
        watch: {
            // Coming from the history or the banner: same screen, another set of operations
            '$route.query.carga'(value) {
                this.importId = Number(value) || null;
                if (this.importId) {
                    this.category = 'all';
                }
                this.loadImportInfo();
                this.reloadTable();
            }
        },
        mounted: function(){
            // The table doesn't need the filter options: load both at once.
            this.loadFilterOptions();
            if (this.importId) {
                this.category = 'all';
                this.loadImportInfo();
            }
            this.initTable();
        },
        beforeDestroy() {
            $(this.$refs.table).find('.tip').tooltip('dispose');
            $(this.$refs.table).off('click');
            if (this.datatable) {
                this.datatable.destroy();
            }
            // Leaving with the modal open would keep its backdrop over the next page.
            if ($(this.$refs.importModal).hasClass('show')) {
                $(this.$refs.importModal).modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            }
        }
    }
</script>
<style>
    .el-select {
        width: 100%;
    }
    /* Shared with the other operation screens that use the .operations class */
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

    /* HEADER */
    .operation-list .ol-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem 1rem;
    }
    .operation-list .ol-header-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    /* the global `.element-header .btn.btn-primary.btn-sm` margins are for a lone button beside the title */
    .operation-list .element-header.ol-header .ol-header-actions .btn {
        margin: 0;
    }
    .operation-list .ol-header-actions .btn i {
        margin-right: .4rem;
    }

    .operation-list {
        --ol-text: #2b3445;
        --ol-soft-text: #4a5468;
        --ol-muted: #8a93a6;
        --ol-line: #e8ebf1;
        --ol-blue: #047bf8;
        --ol-blue-soft: rgba(4, 123, 248, .08);
        --ol-green: #1f8a4c;
        --ol-green-soft: rgba(31, 138, 76, .1);
        --ol-red: #c94a4a;
        --ol-red-soft: rgba(201, 74, 74, .1);
        --ol-teal: #1d8fb0;
        --ol-amber: #8a6100;
        --ol-amber-soft: rgba(251, 211, 100, .28);
        --ol-gray-soft: #eef0f4;
    }

    /* FILTERS */
    .operation-list .element-box.ol-filters {
        margin-bottom: 1rem;
        padding: 0;
    }
    .operation-list .ol-filters-toggle {
        display: none;
    }
    .operation-list .ol-filters-body {
        display: grid;
        grid-template-columns: minmax(250px, 1.2fr) 1fr 1fr;
        align-items: end;
        gap: .75rem 1rem;
        padding: 1rem 1.25rem;
    }
    .operation-list .ol-filter {
        min-width: 0;
    }
    .operation-list .ol-filter label {
        display: block;
        margin-bottom: .3rem;
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--ol-muted);
    }
    .operation-list .ol-filter .el-date-editor.el-range-editor {
        width: 100%;
    }
    .operation-list .ol-filter .el-range-editor .el-range-input {
        cursor: pointer;
    }
    .operation-list .ol-clear {
        grid-column: 1 / -1;
        justify-self: start;
        padding-left: 0;
        padding-right: 0;
    }

    /* CARDS: FEE and counts per type, as in the previous version */
    .operation-list .form-filter-large .value .form-check-label span {
        font-size: 16px;
        font-weight: 700;
    }
    .operation-list .form-filter-large .label {
        font-size: 12px;
    }
    .operation-list .el-tablo .value {
        font-size: 16px;
    }

    .operation-list .is-income {
        color: var(--ol-green);
    }
    .operation-list .is-outcome {
        color: var(--ol-red);
    }

    /* TABLE */
    .operation-list .ol-table {
        margin-top: 0 !important;
    }
    .operation-list .ol-table td {
        vertical-align: middle;
        color: var(--ol-text);
    }
    .operation-list .ol-table tbody tr:hover td {
        background-color: #fafbfd;
    }
    /* The theme's `td span + span { margin-left: 5px }` shifted whatever followed the hidden date line */
    .operation-list .ol-table.table-padded tbody td span + span {
        margin-left: 0;
    }
    /* A global `.table thead th { padding: 4px !important }` takes away the room for the sort arrows */
    .operation-list table.dataTable.ol-table thead > tr > th.sorting,
    .operation-list table.dataTable.ol-table thead > tr > th.sorting_asc,
    .operation-list table.dataTable.ol-table thead > tr > th.sorting_desc {
        padding-right: 1.75rem !important;
    }
    .operation-list .dataTables_wrapper {
        position: relative;
    }
    .operation-list div.dataTables_wrapper div.dataTables_processing {
        top: 80px;
        margin-top: 0;
        padding: .75rem 0;
        background: rgba(255, 255, 255, .85);
        box-shadow: none;
        z-index: 3;
    }
    .operation-list .ol-muted {
        color: var(--ol-muted);
    }
    .operation-list .ol-date {
        white-space: nowrap;
        font-size: .8rem;
        color: var(--ol-soft-text);
        font-variant-numeric: tabular-nums;
    }
    /* min-width: Responsive measures it, so it moves the other columns in here before squeezing the names */
    .operation-list .ol-op {
        display: flex;
        flex-direction: column;
        gap: .2rem;
        min-width: 250px;
        max-width: 560px;
        line-height: 1.35;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .operation-list .ol-contact {
        font-weight: 600;
        color: var(--ol-text);
    }
    .operation-list a.ol-contact:hover {
        color: var(--ol-blue);
        text-decoration: none;
    }
    .operation-list .ol-concept {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: .8rem;
        color: var(--ol-soft-text);
    }
    .operation-list .ol-project {
        display: flex;
        align-items: baseline;
        gap: .35rem;
        font-size: .72rem;
        color: var(--ol-muted);
    }
    .operation-list .ol-project i {
        flex: 0 0 auto;
        font-size: 11px;
    }
    .operation-list .ol-project > span {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .operation-list .ol-tag {
        display: inline-block;
        white-space: nowrap;
        padding: .15rem .55rem;
        border-radius: 999px;
        background-color: var(--ol-amber-soft);
        color: var(--ol-amber);
        font-size: .72rem;
        font-weight: 600;
        line-height: 1.3;
    }
    .operation-list .ol-tag-sub,
    .operation-list .ol-sub {
        display: block;
        margin-top: .15rem;
        font-size: .72rem;
        color: var(--ol-muted);
    }
    .operation-list .ol-donor {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-width: 110px;
        max-width: 180px;
        font-size: .8rem;
        line-height: 1.35;
    }
    .operation-list .ol-pay {
        display: block;
        font-size: .8rem;
        white-space: nowrap;
    }
    .operation-list .ol-ret-line {
        display: block;
        white-space: nowrap;
        font-size: .78rem;
        font-variant-numeric: tabular-nums;
    }
    .operation-list .ol-ret-label {
        display: inline-block;
        min-width: 2.1rem;
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .4px;
        color: var(--ol-muted);
    }
    .operation-list .ol-amount {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .15rem;
    }
    .operation-list .ol-money {
        font-weight: 700;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .operation-list .ol-amount.is-income .ol-money { color: var(--ol-green); }
    .operation-list .ol-amount.is-outcome .ol-money { color: var(--ol-red); }
    .operation-list .ol-amount.is-balance .ol-money { color: var(--ol-teal); }
    .operation-list .ol-kind {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .7rem;
        color: var(--ol-muted);
        white-space: nowrap;
    }
    .operation-list .ol-kind i {
        font-size: 12px;
    }
    .operation-list .ol-kind.is-transfer {
        color: var(--ol-blue);
    }
    .operation-list .ol-actions {
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        white-space: nowrap;
    }
    .operation-list .ol-btn {
        display: inline-flex;
        align-items: center;
        padding: .35rem .45rem;
        border-radius: 6px;
        color: #6b7489;
        line-height: 1;
    }
    .operation-list .ol-btn i {
        font-size: 16px;
    }
    .operation-list .ol-btn:hover {
        background-color: var(--ol-gray-soft);
        color: var(--ol-text);
        text-decoration: none;
    }
    .operation-list .ol-btn.is-danger:hover {
        background-color: var(--ol-red-soft);
        color: var(--ol-red);
    }
    .operation-list .ol-btn.is-disabled {
        opacity: .4;
        cursor: not-allowed;
    }
    .operation-list .ol-btn.is-disabled:hover {
        background-color: transparent;
        color: #6b7489;
    }

    .operation-list a.ol-import-tag {
        display: inline-flex;
        align-items: center;
        align-self: flex-start;
        justify-self: start;
        gap: .3rem;
        padding: 0 .4rem;
        border-radius: 3px;
        background-color: var(--ol-blue-soft);
        font-size: .7rem;
        line-height: 1.5;
        color: var(--ol-blue);
    }
    .operation-list a.ol-import-tag:hover {
        text-decoration: none;
        background-color: rgba(4, 123, 248, .16);
    }
    .operation-list a.ol-import-tag i {
        font-size: 10px;
    }

    /* IMPORT BANNER */
    .operation-list .ol-import-banner {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1rem;
        padding: .8rem 1.1rem;
        border-left: 3px solid var(--ol-blue);
    }
    .operation-list .ol-import-banner > i {
        font-size: 1.1rem;
        color: var(--ol-blue);
    }
    .operation-list .ol-import-text {
        flex: 1 1 auto;
        min-width: 0;
        font-size: .85rem;
        color: var(--ol-soft-text);
        overflow-wrap: anywhere;
    }
    .operation-list .ol-import-text strong {
        display: block;
        color: var(--ol-text);
    }
    .operation-list .ol-import-reverted {
        margin-left: .35rem;
        padding: 0 .4rem;
        border-radius: 3px;
        background-color: var(--ol-red-soft);
        color: var(--ol-red);
        font-size: .72rem;
        font-weight: 600;
    }
    .operation-list .ol-import-banner .btn {
        flex: 0 0 auto;
    }
    @media (max-width: 575px) {
        .operation-list .ol-import-banner {
            flex-wrap: wrap;
        }
    }

    /* Inside the operation cell, only for what Responsive could not show as a column */
    .operation-list .ol-inline {
        display: none;
    }
    .operation-list .ol-show-date .ol-inline.is-date,
    .operation-list .ol-show-donor .ol-inline.is-donor,
    .operation-list .ol-show-pay .ol-inline.is-pay,
    .operation-list .ol-show-retention .ol-inline.is-retention,
    .operation-list .ol-show-actions .ol-inline.is-actions {
        display: block;
    }
    .operation-list .ol-show-category .ol-inline.is-category,
    .operation-list .ol-show-amount .ol-inline.is-amount {
        display: flex;
    }
    .operation-list .ol-eyebrow {
        font-size: .72rem;
        color: var(--ol-muted);
        font-variant-numeric: tabular-nums;
    }
    .operation-list .ol-inline.is-category {
        flex-wrap: wrap;
        align-items: center;
        gap: .2rem .45rem;
        margin-top: .15rem;
    }
    .operation-list .ol-inline.is-category .ol-tag-sub {
        margin-top: 0;
    }
    .operation-list .ol-detail {
        font-size: .78rem;
    }
    .operation-list .ol-detail-label {
        margin-right: .35rem;
        color: var(--ol-muted);
    }
    .operation-list .ol-detail .ol-ret-line {
        display: inline;
        margin-right: .5rem;
    }
    /* With the amount inside, it goes top right, beside the date and the contact */
    .operation-list .ol-show-amount .ol-op {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        column-gap: .75rem;
        row-gap: .2rem;
    }
    .operation-list .ol-show-amount .ol-op > * {
        grid-column: 1 / -1;
    }
    .operation-list .ol-show-amount .ol-op > .is-date,
    .operation-list .ol-show-amount .ol-op > .ol-contact {
        grid-column: 1;
    }
    .operation-list .ol-show-amount .ol-op > .is-amount {
        grid-column: 2;
        grid-row: 1 / span 2;
        align-self: start;
        justify-content: flex-end;
    }
    .operation-list .ol-inline.is-actions {
        margin-top: .1rem;
        margin-left: -.45rem;
    }

    @media (max-width: 991px) {
        .operation-list .ol-filters-body {
            grid-template-columns: 1fr 1fr;
        }
        .operation-list .ol-filter-period {
            grid-column: 1 / -1;
        }
    }
    @media (max-width: 767px) {
        .operation-list .ol-filters-toggle {
            display: flex;
            align-items: center;
            gap: .6rem;
            width: 100%;
            padding: .75rem 1rem;
            border: 0;
            background: none;
            color: var(--ol-text);
            font-size: .85rem;
            text-align: left;
            cursor: pointer;
        }
        .operation-list .ol-filters-toggle > i {
            flex: 0 0 auto;
            color: var(--ol-muted);
        }
        .operation-list .ol-filters-summary {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .operation-list .ol-filters-chevron {
            transition: transform .15s;
        }
        .operation-list .ol-filters.is-open .ol-filters-chevron {
            transform: rotate(180deg);
        }
        .operation-list .ol-filters-body {
            display: none;
            grid-template-columns: 1fr;
            padding: .25rem 1rem 1rem;
            border-top: 1px solid var(--ol-line);
        }
        .operation-list .ol-filters.is-open .ol-filters-body {
            display: grid;
        }
        .operation-list .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: left;
        }
        .operation-list .dataTables_wrapper .dataTables_filter label,
        .operation-list .dataTables_wrapper .dataTables_filter input {
            width: 100%;
        }
        .operation-list .dataTables_wrapper .dataTables_filter label {
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .operation-list .dataTables_wrapper .dataTables_filter input {
            margin-left: 0;
        }
        .operation-list .dataTables_wrapper .dataTables_paginate {
            float: none;
            margin-top: .5rem;
        }
        .operation-list .dataTables_wrapper .dataTables_paginate .pagination {
            justify-content: center;
        }
    }
    @media (max-width: 575px) {
        .operation-list .ol-header-actions .btn span {
            display: none;
        }
        .operation-list .ol-header-actions .btn i {
            margin-right: 0;
        }
        .operation-list .ol-header-actions .btn-primary span {
            display: inline;
        }
        .operation-list .ol-header-actions .btn-primary i {
            margin-right: .4rem;
        }
        /* Wide enough that Responsive moves every column but the amount in here */
        .operation-list .ol-op {
            min-width: 230px;
        }
    }
</style>
