<template>
    <div class="content-box projects project-list">
        <div class="element-wrapper">
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'ProjectForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i>
                    Nuevo Proyecto
                </button>
            </h6>
            <!-- SUMMARY: follows the search and the status filter -->
            <div class="row pl-summary">
                <div class="col-6 col-xl-3">
                    <div class="pl-stat">
                        <span class="pl-stat-label">Proyectos</span>
                        <strong class="pl-stat-value">{{ stats.count.toLocaleString('en') }}</strong>
                        <span class="pl-stat-hint" v-if="stats.count !== stats.total">de {{ stats.total.toLocaleString('en') }}</span>
                    </div>
                </div>
                <!-- Amounts: full on wider screens, compact (with the full one as title) on phones -->
                <div class="col-6 col-xl-3">
                    <div class="pl-stat" :title="money(stats.donated)">
                        <span class="pl-stat-label">Donaciones recibidas</span>
                        <strong class="pl-stat-value"><span class="pl-full">{{ money(stats.donated) }}</span><span class="pl-compact">{{ compactMoney(stats.donated) }}</span></strong>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="pl-stat" :title="money(stats.spent)">
                        <span class="pl-stat-label">Gastos realizados</span>
                        <strong class="pl-stat-value"><span class="pl-full">{{ money(stats.spent) }}</span><span class="pl-compact">{{ compactMoney(stats.spent) }}</span></strong>
                        <span class="pl-stat-hint">{{ spentPercent(stats.donated, stats.spent) }}% ejercido</span>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="pl-stat" :title="money(stats.available)">
                        <span class="pl-stat-label">Saldo disponible</span>
                        <strong class="pl-stat-value" :class="balanceClass(stats.available)"><span class="pl-full">{{ money(stats.available) }}</span><span class="pl-compact">{{ compactMoney(stats.available) }}</span></strong>
                    </div>
                </div>
            </div>
            <!-- STATUS FILTER -->
            <div class="pl-filters" v-if="statusFilters.length">
                <button v-for="filter in statusFilters" :key="filter.value" type="button"
                    class="pl-chip" :class="{ 'is-active': statusFilter === filter.value }"
                    @click="setStatusFilter(filter.value)">
                    {{ filter.label }} <span class="pl-chip-count">{{ filter.count.toLocaleString('en') }}</span>
                </button>
            </div>
            <!-- DataTable: clearfix keeps the floated pagination from narrowing what follows -->
            <div class="element-box-tp clearfix">
                <table class="table table-padded project-list-table" ref="table" width="100%"></table>
            </div>
        </div>
    </div>
</template>

<script>
import {
    api
} from "../../config/jwt-routes";
import {escapeHtml, plainText} from "../../core/helpers.js";

const STATUS_CLASS = { 'En ejecución': 'is-running', 'Concluido': 'is-done' };
const NO_STATUS = '__none__';
// Column indexes, used to filter by status and to know which columns Responsive hid.
const COL = { status: 6, available: 11, donor: 12, actions: 15 };

export default {
    props: ['auth', 'appDomain', 'siteName'],
    data() {
        return {
            categories: [],
            programs: [],
            types: [],
            category: '',
            program: '',
            datatable: null,
            loading: true,
            statusFilter: '',
            statusFilters: [],
            stats: { count: 0, total: 0, donated: 0, spent: 0, available: 0 }
        }
    },
    methods: {
        // UPDATE ACTIVE 1 -> 0
        deleteItem(id) {
            swal({
                title: "Seguro ?",
                html: "Estas a punto de eliminar este registro, deseas continuar?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
            }).then((confirm) => {
                if (confirm.value) {
                    var vm = this;
                    axios.post(api.deleteProject, {
                            id: id
                        })
                        .then(async function (response) {
                            if (response.data.success) {
                                vm.datatable.ajax.reload();
                                swal({
                                    html: response.data.msg,
                                    type: "success",
                                    timer: 1500
                                });
                            } else {
                                swal.close();
                                toastr.error(response.data.msg);
                            }
                        })
                        .catch(function (error) {
                            console.log(error);
                        })
                } else {
                    swal({
                        title: "Operación Cancelada",
                        type: "error",
                        timer: 1200
                    });
                }
            });
        },

        duplicateItem(id) {
            swal({
                title: "Seguro ?",
                html: "Estas a punto de duplicar este proyecto junto con todo lo relacionado a el, a exepción de los estados de cuenta de sus actividades, deseas continuar?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
            }).then((confirm) => {
                if (confirm.value) {
                    var vm = this;
                    axios.post(api.duplicateProject, {
                            id: id
                        })
                        .then(async function (response) {
                            if (response.data.success) {
                                vm.datatable.ajax.reload();
                                swal({
                                    html: response.data.msg,
                                    type: "success",
                                    timer: 1500
                                });
                            } else {
                                swal.close();
                                toastr.error(response.data.msg);
                            }
                        })
                        .catch(function (error) {
                            console.log(error);
                        })
                } else {
                    swal({
                        title: "Operación Cancelada",
                        type: "error",
                        timer: 1200
                    });
                }
            });
        },
        // RELOAD DATATABLE
        reloadTable: function () {
            this.datatable.ajax.reload();
        },
        spentPercent(donated, spent) {
            if (!donated) {
                return spent ? 100 : 0;
            }
            return Math.round(spent * 100 / donated);
        },
        // In cents: the server's float subtraction leaves residues such as 7e-12 for a zero balance.
        balanceClass(value) {
            let cents = Math.round(value * 100);
            return cents > 0 ? 'is-positive' : (cents < 0 ? 'is-negative' : 'is-zero');
        },
        money(value) {
            return (Math.round(value * 100) < 0 ? '-$' : '$') + this.formatPrice(Math.abs(value));
        },
        compactMoney(value) {
            return (Math.round(value * 100) < 0 ? '-$' : '$') + Math.abs(value).toLocaleString('es-MX', { notation: 'compact', maximumFractionDigits: 1 });
        },
        formatDate(value) {
            let date = new Date(plainText(value) + 'T00:00:00');
            return isNaN(date) ? '' : date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
        },
        // STATUS FILTER & SUMMARY
        setStatusFilter(value) {
            this.statusFilter = value;
            let search = '';
            if (value === NO_STATUS) {
                search = '^$';
            } else if (value) {
                search = '^' + $.fn.dataTable.util.escapeRegex(value) + '$';
            }
            this.datatable.column(COL.status).search(search, true, false).draw();
        },
        refreshSummary() {
            let all = this.datatable.rows().data().toArray();
            let shown = this.datatable.rows({ search: 'applied' }).data().toArray();
            let sum = (rows, key) => rows.reduce((total, row) => total + (Number(row[key]) || 0), 0);
            this.stats = {
                count: shown.length,
                total: all.length,
                donated: sum(shown, 'incomes_amount'),
                spent: sum(shown, 'outcomes_amount'),
                available: sum(shown, 'available')
            };
            let counts = {};
            all.forEach(row => {
                let status = plainText(row.status) || NO_STATUS;
                counts[status] = (counts[status] || 0) + 1;
            });
            this.statusFilters = [{ value: '', label: 'Todos', count: all.length }].concat(
                Object.keys(counts).sort((a, b) => counts[b] - counts[a]).map(status => ({
                    value: status,
                    label: status === NO_STATUS ? 'Sin estatus' : status,
                    count: counts[status]
                }))
            );
        },
        // What Responsive hides as a column is shown inside the project cell instead: no row to expand.
        syncInlineDetails(visible) {
            $(this.$refs.table)
                .toggleClass('pl-inline-status', !visible[COL.status])
                .toggleClass('pl-inline-finance', !visible[COL.available])
                .toggleClass('pl-inline-more', !visible[COL.donor])
                .toggleClass('pl-inline-actions', !visible[COL.actions]);
        },
        // CELLS: plain values for sorting, searching and exports; markup only for display
        textCell(data, type) {
            if (type !== 'display') {
                // The server escapes values: search and sort on the text people see.
                return plainText(data);
            }
            return data ? escapeHtml(data) : '<span class="pl-muted">—</span>';
        },
        statusPill(status) {
            status = plainText(status);
            if (!status) {
                return '<span class="pl-status is-empty">Sin estatus</span>';
            }
            return '<span class="pl-status ' + (STATUS_CLASS[status] || 'is-other') + '">' + escapeHtml(status) + '</span>';
        },
        moneyCell(data, type) {
            let value = Number(data) || 0;
            if (type !== 'display') {
                return value;
            }
            return '<span class="pl-money' + (Math.round(value * 100) ? '' : ' is-zero') + '">' + this.money(value) + '</span>';
        },
        balanceCell(row, type) {
            let value = Number(row.available) || 0;
            if (type !== 'display') {
                return value;
            }
            return this.financeSummary(row, false);
        },
        financeSummary(row, withTotals) {
            let donated = Number(row.incomes_amount) || 0;
            let spent = Number(row.outcomes_amount) || 0;
            let available = Number(row.available) || 0;
            let percent = this.spentPercent(donated, spent);
            return '<div class="pl-finance">' +
                '<span class="pl-balance ' + this.balanceClass(available) + '">' + this.money(available) + '</span>' +
                '<span class="pl-bar' + (percent > 100 ? ' is-over' : '') + '" title="' + percent + '% de lo donado ya se gastó"><span style="width: ' + Math.min(percent, 100) + '%"></span></span>' +
                '<span class="pl-finance-note">' + percent + '% ejercido' +
                (withTotals ? ' · Donado ' + this.money(donated) + ' · Gastado ' + this.money(spent) : '') +
                '</span>' +
                '</div>';
        },
        personCell(name) {
            name = plainText(name);
            if (!name) {
                return '<span class="pl-muted">—</span>';
            }
            let initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(word => word[0]).join('').toUpperCase();
            return '<span class="pl-person"><span class="pl-avatar">' + escapeHtml(initials) + '</span><span>' + escapeHtml(name) + '</span></span>';
        },
        projectCell(row) {
            let meta = ['#' + escapeHtml(row.id)];
            if (plainText(row.program)) {
                meta.push(escapeHtml(row.program));
            }
            let from = this.formatDate(row.date_from);
            let to = this.formatDate(row.date_to);
            if (from || to) {
                meta.push(escapeHtml((from || '…') + ' – ' + (to || '…')));
            }
            let more = [];
            if (plainText(row.donor)) {
                more.push('<span><span class="pl-more-label">Donador</span> ' + escapeHtml(row.donor) + '</span>');
            }
            if (plainText(row.respName)) {
                more.push('<span><span class="pl-more-label">Responsable</span> ' + escapeHtml(row.respName) + '</span>');
            }
            return '<div class="pl-project">' +
                '<a href="#" class="pl-name" data-action="tasks" data-id="' + escapeHtml(row.id) + '">' + escapeHtml(row.name) + '</a>' +
                '<div class="pl-meta">' + meta.join(' · ') + '</div>' +
                '<div class="pl-inline is-status">' + this.statusPill(row.status) + '</div>' +
                '<div class="pl-inline is-finance">' + this.financeSummary(row, true) + '</div>' +
                (more.length ? '<div class="pl-inline is-more">' + more.join('') + '</div>' : '') +
                '<div class="pl-inline is-actions">' + this.actionsCell(row.id) + '</div>' +
                '</div>';
        },
        actionsCell(id) {
            id = escapeHtml(id);
            return '<div class="pl-actions">' +
                '<a href="#" class="pl-btn is-primary" title="Actividades del proyecto" data-action="tasks" data-id="' + id + '"><i class="icon-feather-layers"></i><span>Actividades</span></a>' +
                '<a href="#" class="pl-btn tip" title="Reporte" aria-label="Reporte" data-action="report" data-id="' + id + '"><i class="icon-feather-file-text"></i></a>' +
                '<a href="#" class="pl-btn tip" title="Editar" aria-label="Editar" data-action="edit" data-id="' + id + '"><i class="icon-feather-edit"></i></a>' +
                '<a href="#" class="pl-btn is-danger tip" title="Eliminar" aria-label="Eliminar" data-action="delete" data-id="' + id + '"><i class="icon-feather-trash-2"></i></a>' +
                '</div>';
        },
        // OPTIONS EVENTS EDIT & DELETE
        onAction(event) {
            event.preventDefault();
            let target = $(event.currentTarget);
            let id = target.attr('data-id');
            target.tooltip('hide');
            switch (target.attr('data-action')) {
                case 'tasks':
                    this.$router.push({ name: 'TaskList', params: { project: parseInt(id) } });
                    break;
                case 'report':
                    this.$router.push({ name: 'ProjectReport', params: { projectId: id } });
                    break;
                case 'edit':
                    this.$router.push({ name: 'ProjectForm', params: { id: id } });
                    break;
                case 'delete':
                    this.deleteItem(id);
                    break;
                case 'duplicate':
                    this.duplicateItem(id);
                    break;
            }
        }
    },
    mounted: async function () {
        var vm = this;

        //DATATABLE
        // responsivePriority: lower stays visible longer. 'never' columns are only for search and
        // exports (the project cell shows program and dates), as they were with d-none before.
        vm.datatable = $(vm.$refs.table).DataTable({
            responsive: { details: false },
            columns: [
                {
                    data: 'id',
                    title: 'ID',
                    className: 'never'
                },
                {
                    data: 'name',
                    title: 'Proyecto',
                    responsivePriority: 1,
                    render: function (data, type, row) {
                        return type !== 'display' ? plainText(data) : vm.projectCell(row);
                    }
                },
                {
                    data: 'pilar',
                    title: 'Pilar',
                    className: 'never',
                    render: vm.textCell
                },
                {
                    data: 'program',
                    title: 'Programa',
                    className: 'never',
                    render: vm.textCell
                },
                {
                    data: 'project_umbrella',
                    title: 'Proyecto sombrilla',
                    className: 'never',
                    render: vm.textCell
                },
                {
                    data: 'respName',
                    title: 'Responsable',
                    responsivePriority: 7,
                    render: function (data, type) {
                        return type !== 'display' ? plainText(data) : vm.personCell(data);
                    }
                },
                {
                    data: 'status',
                    title: 'Estatus',
                    responsivePriority: 2,
                    render: function (data, type) {
                        return type !== 'display' ? plainText(data) : vm.statusPill(data);
                    }
                },
                {
                    data: 'amount_invertion',
                    title: 'Monto de Inversión',
                    className: 'text-right',
                    responsivePriority: 9,
                    render: vm.moneyCell
                },
                {
                    data: 'incomes_amount',
                    title: 'Donaciones Recibidas',
                    className: 'text-right',
                    responsivePriority: 5,
                    render: vm.moneyCell
                },
                {
                    data: 'outcomes_amount',
                    title: 'Gastos Realizados',
                    className: 'text-right',
                    responsivePriority: 6,
                    render: vm.moneyCell
                },
                {
                    data: 'monitoring_transfer',
                    title: 'Costo Operativo Transferido',
                    className: 'text-right',
                    responsivePriority: 10,
                    render: vm.moneyCell
                },
                {
                    data: 'available',
                    title: 'Saldo disponible',
                    responsivePriority: 3,
                    render: function (data, type, row) {
                        return vm.balanceCell(row, type);
                    }
                },
                {
                    data: 'donor',
                    title: 'Donador',
                    className: 'pl-donor',
                    responsivePriority: 8,
                    render: vm.textCell
                },
                {
                    data: 'date_from',
                    title: 'Inicio',
                    className: 'never',
                    render: vm.textCell
                },
                {
                    data: 'date_to',
                    title: 'Termino',
                    className: 'never',
                    render: vm.textCell
                },
                {
                    data: 'id',
                    title: '',
                    className: 'text-right',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 4,
                    render: function (data, type) {
                        return type !== 'display' ? '' : vm.actionsCell(data);
                    }
                },
            ],
            ajax: {
                url: api.listingProject,
                type: "GET",
                data: function(){ return {program: vm.program}; }
            },
            drawCallback: function () {
                $(vm.$refs.table).find('.tip').tooltip();
                if (vm.datatable) {
                    vm.refreshSummary();
                }
            },
            initComplete: async function (settings, json) {
                vm.ready = true;
                vm.datatable.columns.adjust().responsive.recalc();
                vm.syncInlineDetails(vm.datatable.columns().responsiveHidden().toArray());
                vm.refreshSummary();
            },
            order: [
                [0, 'asc']
            ],
        });
        vm.datatable.on('responsive-resize', function (event, datatable, columns) {
            vm.syncInlineDetails(columns);
        });
        $(vm.$refs.table).on('click', '[data-action]', vm.onAction);
        vm.loading = false;
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
    .option {
        cursor: pointer;
    }
    .project-list {
        --pl-text: #2b3445;
        --pl-muted: #8a93a6;
        --pl-line: #e8ebf1;
        --pl-blue: #047bf8;
        --pl-blue-soft: rgba(4, 123, 248, .08);
        --pl-green: #1f8a4c;
        --pl-green-soft: rgba(31, 138, 76, .1);
        --pl-red: #c94a4a;
        --pl-red-soft: rgba(201, 74, 74, .1);
        --pl-gray-soft: #eef0f4;
    }

    /* SUMMARY */
    .project-list .pl-summary {
        margin: 0 -.375rem 1rem;
    }
    .project-list .pl-summary > [class*="col-"] {
        padding: 0 .375rem;
    }
    .project-list .pl-stat {
        display: flex;
        flex-direction: column;
        height: calc(100% - .75rem);
        margin-bottom: .75rem;
        padding: .9rem 1.1rem;
        border-radius: 6px;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(126, 142, 177, .12);
    }
    .project-list .pl-stat-label {
        font-size: .68rem;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--pl-muted);
    }
    .project-list .pl-stat-value {
        margin-top: .2rem;
        font-size: 1.3rem;
        line-height: 1.2;
        color: var(--pl-text);
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
    .project-list .pl-stat-hint {
        font-size: .75rem;
        color: var(--pl-muted);
    }

    /* STATUS FILTER */
    .project-list .pl-filters {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-bottom: .75rem;
    }
    .project-list .pl-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .3rem .75rem;
        border: 1px solid var(--pl-line);
        border-radius: 999px;
        background-color: #fff;
        color: var(--pl-text);
        font-size: .8rem;
        cursor: pointer;
    }
    .project-list .pl-chip:hover {
        border-color: var(--pl-blue);
    }
    .project-list .pl-chip.is-active {
        border-color: var(--pl-blue);
        background-color: var(--pl-blue-soft);
        color: var(--pl-blue);
        font-weight: 600;
    }
    .project-list .pl-chip-count {
        color: var(--pl-muted);
        font-weight: 400;
    }

    /* TABLE */
    .project-list .project-list-table td {
        vertical-align: middle;
        color: var(--pl-text);
    }
    .project-list .project-list-table tbody tr:hover td {
        background-color: #fafbfd;
    }
    /* A global `.table thead th { padding: 4px !important }` takes away the room for the sort arrows */
    .project-list table.dataTable.project-list-table thead > tr > th.sorting,
    .project-list table.dataTable.project-list-table thead > tr > th.sorting_asc,
    .project-list table.dataTable.project-list-table thead > tr > th.sorting_desc {
        padding-right: 1.75rem !important;
    }
    .project-list .pl-muted {
        color: var(--pl-muted);
    }
    .project-list .pl-project {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        min-width: 220px;
    }
    .project-list .pl-name {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        color: var(--pl-text);
        font-weight: 600;
        line-height: 1.3;
    }
    .project-list .pl-name:hover {
        color: var(--pl-blue);
        text-decoration: none;
    }
    .project-list .pl-meta {
        font-size: .75rem;
        color: var(--pl-muted);
    }
    .project-list .pl-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
        background-color: var(--pl-gray-soft);
        color: #5a637e;
    }
    .project-list .pl-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: currentColor;
    }
    .project-list .pl-status.is-running {
        background-color: var(--pl-blue-soft);
        color: var(--pl-blue);
    }
    .project-list .pl-status.is-done {
        background-color: var(--pl-green-soft);
        color: var(--pl-green);
    }
    .project-list .pl-status.is-empty {
        background-color: transparent;
        color: var(--pl-muted);
        font-weight: 400;
    }
    .project-list .pl-status.is-empty::before {
        display: none;
    }
    .project-list .pl-money {
        font-weight: 500;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .project-list .pl-money.is-zero {
        color: var(--pl-muted);
    }
    .project-list .pl-finance {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 120px;
    }
    .project-list .pl-balance {
        font-weight: 700;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .project-list .is-positive {
        color: var(--pl-green);
    }
    .project-list .is-negative {
        color: var(--pl-red);
    }
    .project-list .pl-balance.is-zero,
    .project-list .pl-stat-value.is-zero {
        color: var(--pl-muted);
    }
    .project-list .pl-bar {
        display: block;
        height: 4px;
        border-radius: 2px;
        background-color: var(--pl-line);
        overflow: hidden;
    }
    .project-list .pl-bar > span {
        display: block;
        height: 100%;
        border-radius: 2px;
        background-color: var(--pl-blue);
    }
    .project-list .pl-bar.is-over > span {
        background-color: var(--pl-red);
    }
    .project-list .pl-finance-note {
        font-size: .72rem;
        color: var(--pl-muted);
    }
    .project-list .pl-person {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        font-size: .82rem;
    }
    .project-list .pl-avatar {
        flex: 0 0 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: var(--pl-gray-soft);
        color: #5a637e;
        font-size: .68rem;
        font-weight: 700;
    }
    .project-list .pl-donor {
        font-size: .8rem;
    }
    .project-list .pl-actions {
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        white-space: nowrap;
    }
    .project-list .pl-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .45rem;
        border-radius: 6px;
        color: #6b7489;
        line-height: 1;
    }
    .project-list .pl-btn i {
        font-size: 16px;
    }
    .project-list .pl-btn:hover {
        background-color: var(--pl-gray-soft);
        color: var(--pl-text);
        text-decoration: none;
    }
    .project-list .pl-btn.is-primary {
        padding: .35rem .65rem;
        background-color: var(--pl-blue-soft);
        color: var(--pl-blue);
        font-size: .8rem;
        font-weight: 600;
    }
    .project-list .pl-btn.is-primary:hover {
        background-color: rgba(4, 123, 248, .15);
    }
    .project-list .pl-btn.is-danger:hover {
        background-color: var(--pl-red-soft);
        color: var(--pl-red);
    }

    /* Inside the project cell, only for what Responsive could not show as a column */
    .project-list .pl-inline {
        display: none;
    }
    .project-list .pl-inline-status .pl-inline.is-status,
    .project-list .pl-inline-finance .pl-inline.is-finance,
    .project-list .pl-inline-more .pl-inline.is-more,
    .project-list .pl-inline-actions .pl-inline.is-actions {
        display: block;
    }
    .project-list .pl-inline.is-finance {
        max-width: 360px;
        margin-top: .15rem;
    }
    .project-list .pl-inline.is-more {
        font-size: .78rem;
    }
    .project-list .pl-inline.is-more > span {
        display: block;
        margin-left: 0;
    }
    .project-list .pl-more-label {
        color: var(--pl-muted);
    }
    .project-list .pl-inline.is-actions {
        margin-top: .2rem;
        margin-left: -.45rem;
    }

    .project-list .pl-compact {
        display: none;
    }
    @media (max-width: 575px) {
        .project-list .pl-full {
            display: none;
        }
        .project-list .pl-compact {
            display: inline;
        }
        /* Wide enough that Responsive moves every other column into this card */
        .project-list .pl-project {
            min-width: 270px;
        }
    }
    @media (max-width: 767px) {
        .project-list .element-header .btn {
            margin-top: .5rem;
            margin-left: 0;
        }
        .project-list .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: left;
        }
    }
    @media (min-width: 992px) {
        .project-list .pl-project {
            min-width: 260px;
        }
        .project-list .pl-stat-value {
            font-size: 1.45rem;
        }
    }
</style>
