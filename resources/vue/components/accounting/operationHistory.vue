<template>
    <div class="content-box operation-history">
        <div class="element-wrapper">
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header oh-header">
                <default-header></default-header>
                <span class="oh-header-actions">
                    <button @click.prevent="$router.push({name:'OperationList'})" class="btn btn-sm btn-white">
                        <i class="icon-feather-list"></i><span><translate>Operaciones</translate></span>
                    </button>
                    <button @click.prevent="$refs.incomeImport.open()" class="btn btn-sm btn-primary">
                        <i class="icon-feather-upload"></i><span><translate>Importar ingresos</translate></span>
                    </button>
                </span>
            </h6>

            <!-- FILTERS -->
            <div class="element-box oh-filters">
                <div class="oh-filter oh-filter-period">
                    <label v-translate>Fecha de carga</label>
                    <el-date-picker
                        v-model="range"
                        type="daterange"
                        unlink-panels
                        :editable="false"
                        :clearable="false"
                        :picker-options="pickerOptions"
                        @change="reloadTable"
                        range-separator="–"
                        format="dd/MM/yyyy"
                        value-format="yyyy-MM-dd"
                        start-placeholder="Desde"
                        end-placeholder="Hasta">
                    </el-date-picker>
                </div>
                <div class="oh-filter">
                    <label v-translate>Estado</label>
                    <el-select v-model="status" @change="reloadTable">
                        <el-option label="Todas" value=""></el-option>
                        <el-option label="Activas" value="1"></el-option>
                        <el-option label="Revertidas" value="0"></el-option>
                    </el-select>
                </div>
                <p class="oh-intro">
                    Cada carga marca los registros que creó. Revertirla elimina todos sus registros (ingresos y retenciones de costo operativo)
                    y desactiva los donadores que creó si nada más los usa.
                </p>
            </div>

            <!-- DataTable -->
            <div class="element-box-tp clearfix">
                <table class="table table-padded oh-table" ref="table" width="100%"></table>
            </div>
        </div>

        <income-import ref="incomeImport" @imported="reloadTable"></income-import>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {escapeHtml, plainText, downloadFile} from "../../core/helpers.js";
    import IncomeImport from "./incomeImport.vue";

    function shortcut(text, from, to) {
        return { text: text, onClick: picker => picker.$emit('pick', [from().toDate(), to().toDate()]) };
    }

	export default {
        components: { IncomeImport },
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                range:[
                    moment().startOf('year').format('YYYY-MM-DD'),
                    moment().endOf('month').format('YYYY-MM-DD')
                ],
                status: '',
                datatable: null,
                pickerOptions: {
                    firstDayOfWeek: 1,
                    shortcuts: [
                        shortcut('Este mes', () => moment().startOf('month'), () => moment().endOf('month')),
                        shortcut('Últimos 12 meses', () => moment().subtract(11, 'month').startOf('month'), () => moment().endOf('month')),
                        shortcut('Este año', () => moment().startOf('year'), () => moment().endOf('year')),
                        shortcut('Año anterior', () => moment().subtract(1, 'year').startOf('year'), () => moment().subtract(1, 'year').endOf('year')),
                        shortcut('Todas', () => moment('2018-01-01'), () => moment().endOf('month'))
                    ]
                }
            }
        },
		methods: {
            reloadTable() {
                if (this.datatable) {
                    this.datatable.ajax.reload();
                }
            },
            money(value) {
                return '$' + this.formatPrice(Number(plainText(value)) || 0);
            },
            count(value) {
                return (Number(plainText(value)) || 0).toLocaleString('en');
            },
            date(value, withTime) {
                let text = plainText(value);
                if (!text) {
                    return '';
                }
                return moment(text, withTime ? 'YYYY-MM-DD HH:mm:ss' : 'YYYY-MM-DD').format(withTime ? 'DD/MM/YYYY HH:mm' : 'DD/MM/YYYY');
            },
            muted(text) {
                return '<span class="oh-muted">' + text + '</span>';
            },

            // CELLS (Yajra sends every value HTML-escaped: plainText, then escape what goes into the markup)
            importCell(data, type, row) {
                if (type !== 'display') {
                    return plainText(data);
                }
                let name = plainText(row.original_name) || 'Sin nombre';
                // On phones the other columns are hidden: their essentials go here
                let inline = plainText(row.type)
                    ? this.count(row.rows_count) + ' ingresos · ' + this.money(row.total_amount)
                    : 'Sin enlazar';
                return '<div class="oh-import">' +
                    '<span class="oh-eyebrow">Carga #' + escapeHtml(row.id) + '</span>' +
                    '<span class="oh-file" title="' + escapeHtml(name) + '">' + escapeHtml(name) + '</span>' +
                    '<span class="oh-sub">' + escapeHtml(this.date(row.date, true)) + (plainText(row.name) ? ' · ' + escapeHtml(row.name) : '') + '</span>' +
                    '<span class="oh-inline">' + escapeHtml(inline) + ' ' + (plainText(row.status) == '1'
                        ? '<span class="oh-status is-active">Activa</span>' : '<span class="oh-status is-reverted">Revertida</span>') + '</span>' +
                    '</div>';
            },
            incomesCell(data, type, row) {
                if (type !== 'display') {
                    return data;
                }
                if (!plainText(row.type)) {
                    return this.muted('Sin enlazar');
                }
                let from = this.date(row.period_from);
                let to = this.date(row.period_to);
                return '<span class="oh-num">' + this.count(row.rows_count) + '</span>' +
                    (from ? '<span class="oh-sub">' + escapeHtml(from == to ? from : from + ' – ' + to) + '</span>' : '');
            },
            amountCell(data, type, row) {
                if (type !== 'display') {
                    return data;
                }
                if (!plainText(row.type)) {
                    return this.muted('—');
                }
                let co = Number(plainText(row.total_co)) || 0;
                return '<span class="oh-num oh-amount">' + this.money(row.total_amount) + '</span>' +
                    '<span class="oh-sub">Fee ' + this.money(row.total_fee) + (co ? ' · CO ' + this.money(co) : '') + '</span>';
            },
            donorsCell(data, type, row) {
                if (type !== 'display') {
                    return data;
                }
                let donors = Number(plainText(data)) || 0;
                return donors ? '<span class="oh-num">' + donors + '</span>' : this.muted('—');
            },
            statusCell(data, type, row) {
                if (type !== 'display') {
                    return data;
                }
                if (plainText(data) == '1') {
                    return '<span class="oh-status is-active">Activa</span>';
                }
                let reason = plainText(row.revert_reason);
                return '<span class="oh-status is-reverted">Revertida</span>' +
                    '<span class="oh-sub">' + escapeHtml(this.date(row.date_delete, true)) + (plainText(row.name_delete) ? ' · ' + escapeHtml(row.name_delete) : '') + '</span>' +
                    (reason ? '<span class="oh-reason" title="' + escapeHtml(reason) + '">' + escapeHtml(reason) + '</span>' : '');
            },
            actionsCell(data, type, row) {
                if (type !== 'display') {
                    return '';
                }
                let id = escapeHtml(row.id);
                let linked = !!plainText(row.type);
                let active = plainText(row.status) == '1';
                let buttons = [];
                if (linked) {
                    buttons.push('<a href="/operation/list?carga=' + id + '" class="oh-btn tip" title="Ver registros" aria-label="Ver registros" data-action="records" data-id="' + id + '"><i class="icon-feather-list"></i></a>');
                }
                if (plainText(row.file_path)) {
                    buttons.push('<a href="#" class="oh-btn tip" title="Descargar archivo" aria-label="Descargar archivo" data-action="file" data-id="' + id + '"><i class="icon-feather-download"></i></a>');
                }
                if (active && linked) {
                    buttons.push('<a href="#" class="oh-btn is-danger tip" title="Revertir carga" aria-label="Revertir carga" data-action="revert" data-id="' + id + '"><i class="icon-feather-rotate-ccw"></i></a>');
                } else if (active) {
                    buttons.push('<span class="oh-btn is-disabled tip" tabindex="0" title="Carga anterior sin registros marcados: no se puede revertir desde aquí." aria-label="Revertir no disponible"><i class="icon-feather-rotate-ccw"></i></span>');
                }
                return '<div class="oh-actions">' + buttons.join('') + '</div>';
            },

            onAction(event) {
                let target = $(event.currentTarget);
                if (target.attr('data-action') == 'records' && (event.ctrlKey || event.metaKey || event.shiftKey)) {
                    return;
                }
                event.preventDefault();
                target.tooltip('hide');
                let id = target.attr('data-id');
                switch (target.attr('data-action')) {
                    case 'records':
                        this.$router.push({ name: 'OperationList', query: { carga: id } });
                        break;
                    case 'file':
                        downloadFile(api.historyOperationFile, { id: id }, 'carga_' + id + '.xlsx').catch(error => toastr.error(error.message));
                        break;
                    case 'revert':
                        this.revert(id);
                        break;
                }
            },

            async revert(id) {
                let vm = this;
                let response;
                try {
                    response = await axios.post(api.historyOperationShow, { id: id });
                } catch (error) {
                    toastr.error(error);
                    return;
                }
                if (!response.data.success) {
                    toastr.error(response.data.msg);
                    return;
                }
                let history = response.data.history;
                let impact = response.data.impact;
                let items = [
                    '<li><b>' + vm.count(impact.incomes) + '</b> ingresos por <b>' + vm.money(impact.amount) + '</b></li>'
                ];
                if (impact.operating_cost) {
                    items.push('<li><b>' + vm.count(impact.operating_cost) + '</b> movimientos de retención de costo operativo</li>');
                }
                if (impact.donors_created) {
                    items.push('<li><b>' + impact.donors_to_deactivate + '</b> de ' + impact.donors_created + ' donadores creados por la carga se desactivarán' +
                        (impact.donors_created > impact.donors_to_deactivate ? ' (los demás ya tienen otros registros)' : '') + '</li>');
                }
                let edited = impact.edited
                    ? '<p class="oh-alert">' + vm.count(impact.edited) + ' registros se editaron después de la carga: también se eliminarán con esos cambios.</p>'
                    : '';

                const {value: reason} = await swal({
                    title: 'Revertir la carga #' + escapeHtml(history.id),
                    html: '<div class="oh-revert"><p class="oh-revert-file">' + escapeHtml(history.original_name || '') + '</p>' +
                        '<p>Se eliminarán:</p><ul>' + items.join('') + '</ul>' + edited +
                        '<p><b>No se puede deshacer.</b> Para recuperar los registros habría que volver a cargar el archivo.</p></div>',
                    type: 'warning',
                    input: 'textarea',
                    inputPlaceholder: 'Motivo de la reversión (obligatorio)',
                    inputValidator: value => Promise.resolve((value || '').trim().length < 5 ? 'Escribe el motivo (mínimo 5 caracteres).' : undefined),
                    showCancelButton: true,
                    focusCancel: true,
                    confirmButtonText: 'Revertir carga',
                    confirmButtonColor: '#c94a4a',
                    cancelButtonText: 'Cancelar'
                });
                if (!reason) {
                    return;
                }

                swal({ title: 'Revirtiendo la carga...', allowOutsideClick: false, allowEscapeKey: false, onOpen: () => swal.showLoading() });
                axios.post(api.historyOperationDelete, { id: id, reason: reason.trim() })
                .then(function (result) {
                    if (result.data.success) {
                        swal({ html: escapeHtml(result.data.msg), type: 'success', timer: 3000 });
                        vm.reloadTable();
                    } else {
                        swal.close();
                        toastr.error(result.data.msg);
                    }
                })
                .catch(function (error) {
                    swal.close();
                    toastr.error(error);
                    console.log(error);
                });
            }
		},
        mounted: function(){
            var vm = this;
            vm.datatable = $(vm.$refs.table).DataTable({
                serverSide: true,
                searchDelay: 400,
                pageLength: 25,
                order: [[0, 'desc']],
                responsive: { details: false },
                columns: [
                    { data: 'id', name: 'a.id', visible: false, searchable: false },
                    { data: 'original_name', name: 'a.original_name', title: 'Carga', responsivePriority: 1, render: vm.importCell },
                    // min-tablet-l: hidden on phones, where the import cell shows a summary of them (.oh-inline)
                    { data: 'rows_count', name: 'a.rows_count', title: 'Ingresos', className: 'min-tablet-l', searchable: false, responsivePriority: 4, render: vm.incomesCell },
                    { data: 'total_amount', name: 'a.total_amount', title: 'Importe', className: 'text-right min-tablet-l', searchable: false, responsivePriority: 3, render: vm.amountCell },
                    { data: 'donors_created', name: 'a.donors_created', title: 'Donadores nuevos', className: 'text-center min-tablet-l', searchable: false, responsivePriority: 6, render: vm.donorsCell },
                    { data: 'status', name: 'a.status', title: 'Estado', className: 'min-tablet-l', searchable: false, responsivePriority: 5, render: vm.statusCell },
                    { data: 'id', name: 'a.id', title: '', className: 'text-right', orderable: false, searchable: false, responsivePriority: 2, render: vm.actionsCell },
                    { data: 'name', name: 'b.name', visible: false }
                ],
                language: {
                    processing: '<i class="fa fa-spinner fa-2x fa-spin"></i>',
                    info: '_START_–_END_ de _TOTAL_',
                    infoEmpty: '0 cargas',
                    infoFiltered: '',
                    emptyTable: 'No hay cargas en este periodo',
                    zeroRecords: 'Ninguna carga coincide con la búsqueda'
                },
                ajax: {
                    url: api.historyOperation,
                    type: "POST",
                    data: function (data) {
                        $.extend(data, { from: vm.range[0], to: vm.range[1], status: vm.status });
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
                }
            });
            $(vm.$refs.table).on('click', '[data-action]', vm.onAction);
        },
        beforeDestroy() {
            $(this.$refs.table).find('.tip').tooltip('dispose');
            $(this.$refs.table).off('click');
            if (this.datatable) {
                this.datatable.destroy();
            }
        }
    }
</script>

<style>
    .operation-history {
        --oh-text: #2b3445;
        --oh-soft-text: #4a5468;
        --oh-muted: #8a93a6;
        --oh-line: #e8ebf1;
        --oh-blue: #047bf8;
        --oh-green: #1f8a4c;
        --oh-green-soft: rgba(31, 138, 76, .1);
        --oh-red: #c94a4a;
        --oh-red-soft: rgba(201, 74, 74, .1);
    }
    .operation-history .oh-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }
    .operation-history .oh-header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .operation-history .oh-header-actions .btn i {
        margin-right: .4rem;
    }
    .operation-history .element-box.oh-filters {
        display: grid;
        grid-template-columns: minmax(250px, 340px) 200px 1fr;
        align-items: end;
        gap: .75rem 1rem;
        margin-bottom: 1rem;
        padding: 1rem 1.25rem;
    }
    .operation-history .oh-filter label {
        display: block;
        margin-bottom: .3rem;
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--oh-muted);
    }
    .operation-history .oh-filter .el-date-editor.el-range-editor,
    .operation-history .oh-filter .el-select {
        width: 100%;
    }
    .operation-history .oh-intro {
        margin: 0;
        font-size: .8rem;
        color: var(--oh-muted);
    }
    .operation-history .oh-table {
        margin-top: 0 !important;
    }
    .operation-history .oh-table td {
        vertical-align: middle;
        color: var(--oh-text);
    }
    .operation-history .oh-table.table-padded tbody td span + span {
        margin-left: 0;
    }
    .operation-history .oh-import {
        display: flex;
        flex-direction: column;
        min-width: 220px;
        max-width: 420px;
        line-height: 1.35;
        white-space: normal;
    }
    .operation-history .oh-eyebrow {
        font-size: .72rem;
        color: var(--oh-muted);
        font-variant-numeric: tabular-nums;
    }
    .operation-history .oh-file {
        font-weight: 600;
        overflow-wrap: anywhere;
    }
    .operation-history .oh-sub {
        display: block;
        font-size: .75rem;
        color: var(--oh-muted);
        white-space: nowrap;
    }
    .operation-history .oh-inline {
        display: none;
        margin-top: .2rem;
        font-size: .78rem;
        color: var(--oh-soft-text);
    }
    @media (max-width: 768px) {
        .operation-history .oh-inline {
            display: block;
        }
    }
    .operation-history .oh-num {
        display: block;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .operation-history .oh-amount {
        font-weight: 600;
    }
    .operation-history .oh-muted {
        color: var(--oh-muted);
    }
    .operation-history .oh-status {
        display: inline-block;
        padding: 0 .45rem;
        border-radius: 3px;
        font-size: .75rem;
        font-weight: 600;
    }
    .operation-history .oh-status.is-active {
        background-color: var(--oh-green-soft);
        color: var(--oh-green);
    }
    .operation-history .oh-status.is-reverted {
        background-color: var(--oh-red-soft);
        color: var(--oh-red);
    }
    .operation-history .oh-reason {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        max-width: 260px;
        overflow: hidden;
        font-size: .75rem;
        font-style: italic;
        color: var(--oh-soft-text);
        white-space: normal;
    }
    .operation-history .oh-actions {
        display: inline-flex;
        gap: .15rem;
        white-space: nowrap;
    }
    .operation-history .oh-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 4px;
        color: #6b7489;
    }
    .operation-history .oh-btn:hover {
        background-color: #eef0f4;
        color: var(--oh-blue);
        text-decoration: none;
    }
    .operation-history .oh-btn.is-danger:hover {
        background-color: var(--oh-red-soft);
        color: var(--oh-red);
    }
    .operation-history .oh-btn.is-disabled {
        opacity: .4;
        cursor: not-allowed;
    }
    .operation-history .oh-btn.is-disabled:hover {
        background-color: transparent;
        color: #6b7489;
    }
    /* swal content: it lives outside the component */
    .oh-revert {
        text-align: left;
        font-size: .9rem;
    }
    .oh-revert .oh-revert-file {
        font-weight: 600;
        overflow-wrap: anywhere;
    }
    .oh-revert ul {
        padding-left: 1.2rem;
    }
    .oh-revert .oh-alert {
        padding: .5rem .7rem;
        border-radius: 4px;
        background-color: rgba(251, 211, 100, .28);
        color: #8a6100;
    }
    @media (max-width: 991px) {
        .operation-history .element-box.oh-filters {
            grid-template-columns: 1fr 1fr;
        }
        .operation-history .oh-intro {
            grid-column: 1 / -1;
        }
    }
    @media (max-width: 575px) {
        .operation-history .element-box.oh-filters {
            grid-template-columns: 1fr;
        }
    }
</style>
