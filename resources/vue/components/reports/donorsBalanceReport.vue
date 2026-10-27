<template>
    <div class="content-box donors-balance">
        <div class="element-wrapper">
            <!-- HEADER & BUTTONS: screen only -->
            <h6 class="element-header db-header">
                <default-header></default-header>
                <div class="db-actions">
                    <button type="button" class="btn btn-sm btn-white" @click="$router.push({name: 'OperationForm'})">
                        <i class="icon-feather-plus"></i><span class="db-action-label"> Nueva operación</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-white" :disabled="!shown.length" title="Descarga las fuentes que se muestran" @click="exportExcel">
                        <i class="icon-feather-download"></i><span class="db-action-label"> Excel</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" :disabled="!rows" @click="print">
                        <i class="icon-feather-printer"></i><span class="db-action-label"> Imprimir</span>
                    </button>
                </div>
            </h6>

            <!-- PERIOD -->
            <header class="element-box db-cover">
                <div class="db-cover-title">
                    <div class="db-eyebrow"><span class="db-screen">Periodo</span><span class="db-paper">Saldos de donantes</span></div>
                    <h1>{{monthLabel}}</h1>
                    <p class="db-muted">Saldo anterior al {{prevEnd}} · saldo contable al {{monthEnd}}</p>
                </div>
                <div class="db-period" role="group" aria-label="Periodo">
                    <button type="button" class="btn btn-white db-step" aria-label="Mes anterior" @click="step(-1)">
                        <i class="icon-feather-chevron-left"></i>
                    </button>
                    <el-date-picker v-model="month" type="month" value-format="yyyy-MM" format="MM/yyyy"
                                    :clearable="false" :editable="false" class="db-month" aria-label="Elegir mes"></el-date-picker>
                    <button type="button" class="btn btn-white db-step" aria-label="Mes siguiente" @click="step(1)">
                        <i class="icon-feather-chevron-right"></i>
                    </button>
                </div>
                <div class="db-print-meta">
                    <img v-if="auth && auth.company_logo" :src="appDomain + auth.company_logo" alt="">
                    <span>Generado el {{generatedAt}}</span>
                </div>
            </header>

            <!-- ERROR -->
            <div v-if="error" class="element-box db-state">
                <div class="db-state-icon"><i class="icon-feather-alert-circle"></i></div>
                <p>{{error}}</p>
                <button type="button" class="btn btn-primary btn-sm" @click="load">Intentar de nuevo</button>
            </div>

            <template v-else>
                <!-- TABLE -->
                <section class="element-box db-section">
                    <div class="db-toolbar">
                        <div class="db-search">
                            <i class="icon-feather-search"></i>
                            <input v-model="search" type="search" class="form-control" placeholder="Buscar donante o fuente"
                                   autocomplete="off" aria-label="Buscar donante o fuente">
                        </div>
                    </div>
                    <p class="db-print-filter">
                        <template v-if="search">Búsqueda «{{search}}» · </template>{{number(shown.length)}} fuentes de {{number(groups.length)}} donantes · importes en pesos
                    </p>

                    <div v-if="!rows" class="db-skeleton" aria-label="Cargando saldos">
                        <span v-for="n in 8" :key="n" class="db-skeleton-line" :style="{width: (96 - n * 5) + '%'}"></span>
                    </div>

                    <div v-else-if="!groups.length" class="db-empty">
                        <template v-if="search">
                            <i class="icon-feather-search"></i>
                            <p>No encontramos donantes ni fuentes con «{{search}}».</p>
                            <button type="button" class="btn btn-white btn-sm" @click="search = ''">Ver todas las fuentes</button>
                        </template>
                        <template v-else>
                            <i class="icon-feather-users"></i>
                            <p>No hay donantes con operaciones hasta {{monthLabel.toLowerCase()}}.</p>
                        </template>
                    </div>

                    <div v-else class="db-table-wrap" :class="{'is-loading': loading}">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="db-name" :aria-sort="ariaSort('donor')" title="Importes en pesos mexicanos">
                                        <span class="db-sort" role="button" tabindex="0" @click="sortBy('donor')" @keydown.enter.space.prevent="sortBy('donor')">Donante / fuente <i :class="sortIcon('donor')"></i></span>
                                    </th>
                                    <th rowspan="2" class="db-amount" :aria-sort="ariaSort('previous')">
                                        <span class="db-sort" role="button" tabindex="0" @click="sortBy('previous')" @keydown.enter.space.prevent="sortBy('previous')">Saldo anterior <i :class="sortIcon('previous')"></i></span>
                                        <small>al {{prevEnd}}</small>
                                    </th>
                                    <th colspan="6" class="db-band is-in">Entradas del mes</th>
                                    <th colspan="5" class="db-band is-out">Salidas del mes</th>
                                    <th rowspan="2" class="db-amount db-balance" :aria-sort="ariaSort('balance')">
                                        <span class="db-sort" role="button" tabindex="0" @click="sortBy('balance')" @keydown.enter.space.prevent="sortBy('balance')">Saldo contable <i :class="sortIcon('balance')"></i></span>
                                        <small>al {{monthEnd}}</small>
                                    </th>
                                </tr>
                                <tr>
                                    <th v-for="col in MONTH_COLUMNS" :key="col.key" class="db-amount" :class="'is-' + col.band" :aria-sort="ariaSort(col.key)" :title="col.hint">
                                        <span class="db-sort" role="button" tabindex="0" @click="sortBy(col.key)" @keydown.enter.space.prevent="sortBy(col.key)">{{col.label}} <i :class="sortIcon(col.key)"></i></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody v-for="group in groups" :key="group.id" :class="{'is-split': group.rows.length > 1}">
                                <tr class="db-donor">
                                    <th class="db-name" scope="row">
                                        <a :href="appDomain + '/operation/donor/' + group.id" target="_blank" rel="noopener" title="Abrir estado de cuenta">
                                            {{group.donor}} <i class="icon-feather-external-link"></i>
                                        </a>
                                        <small v-if="group.rows.length > 1">{{group.rows.length}} fuentes</small>
                                        <small v-else>{{group.rows[0].source}}</small>
                                    </th>
                                    <td v-for="col in COLUMNS" :key="col.key" class="db-amount" :class="cellClass(col, group.totals[col.key])">{{amount(group.totals[col.key])}}</td>
                                </tr>
                                <template v-if="group.rows.length > 1">
                                    <tr v-for="row in group.rows" :key="row.key" class="db-source">
                                        <th class="db-name" scope="row">{{row.source}}</th>
                                        <td v-for="col in COLUMNS" :key="col.key" class="db-amount" :class="cellClass(col, row.cents[col.key])">{{amount(row.cents[col.key])}}</td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="db-name" scope="row">Total <small>{{number(shown.length)}} fuentes · {{number(groups.length)}} donantes</small></th>
                                    <td v-for="col in COLUMNS" :key="col.key" class="db-amount" :class="cellClass(col, totals[col.key])">{{amount(totals[col.key])}}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </section>
            </template>
        </div>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {figures, cents} from "./donorsBalance";
    import {downloadXlsx} from "../../helpers/xlsx";

    // Amount columns, in report order. band: in/out of the month; note: shown for reference, not part of the balance
    const COLUMNS = [
        { key: 'previous', label: 'Saldo anterior' },
        { key: 'incomes', label: 'Ingresos', band: 'in' },
        { key: 'fee', label: 'Fee retenido', band: 'in', note: true },
        { key: 'incomesNet', label: 'Ingresos sin fee', band: 'in' },
        { key: 'withheld', label: 'Retención CO', band: 'in', note: true, hint: 'Costo operativo retenido del primer ingreso del mes' },
        { key: 'received', label: 'Traspasos recibidos', band: 'in' },
        { key: 'projects', label: 'Saldo en proyectos', band: 'in' },
        { key: 'given', label: 'Traspasos otorgados', band: 'out' },
        { key: 'outcomes', label: 'Egresos', band: 'out' },
        { key: 'operatingCost', label: 'Costo operativo', band: 'out' },
        { key: 'releases', label: 'Liberaciones', band: 'out' },
        { key: 'remnants', label: 'Remanentes', band: 'out' },
        { key: 'balance', label: 'Saldo contable', balance: true }
    ];
    const MONTH_COLUMNS = COLUMNS.filter(col => col.band);
    const zeros = () => Object.fromEntries(COLUMNS.map(col => [col.key, 0]));
    // Search ignores case and accents: "mexico" finds "MÉXICO".
    const normalize = value => String(value == null ? '' : value).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const byName = (a, b) => a.localeCompare(b, 'es', { sensitivity: 'base', numeric: true });

	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                COLUMNS,
                MONTH_COLUMNS,
                month: moment().format('YYYY-MM'),
                rows: null,
                loading: false,
                error: '',
                search: '',
                sort: { key: 'donor', desc: false },
                generatedAt: moment().format('DD/MM/YYYY HH:mm'),
                request: 0
            }
        },
        computed: {
            monthLabel(){
                let label = moment(this.month + '-01').format('MMMM YYYY');
                return label.charAt(0).toUpperCase() + label.slice(1);
            },
            prevEnd(){
                return moment(this.month + '-01').subtract(1, 'day').format('DD/MM/YYYY');
            },
            monthEnd(){
                return moment(this.month + '-01').endOf('month').format('DD/MM/YYYY');
            },
            // Every source of the month, with or without balance or movements, that matches the search
            shown(){
                let terms = normalize(this.search).split(/\s+/).filter(Boolean);
                return (this.rows || []).filter(row => terms.every(term => row.searchKey.includes(term)));
            },
            // One group per donor, with the totals of the sources shown, in the chosen order
            groups(){
                let groups = new Map();
                this.shown.forEach(row => {
                    if (!groups.has(row.id)) {
                        groups.set(row.id, { id: row.id, donor: row.donor, rows: [], totals: zeros() });
                    }
                    let group = groups.get(row.id);
                    group.rows.push(row);
                    COLUMNS.forEach(col => { group.totals[col.key] += row.cents[col.key]; });
                });
                let { key, desc } = this.sort;
                let sign = desc ? -1 : 1;
                let compareRows = key === 'donor'
                    ? (a, b) => byName(a.source, b.source)
                    : (a, b) => sign * (a.cents[key] - b.cents[key]) || byName(a.source, b.source);
                let compareGroups = key === 'donor'
                    ? (a, b) => sign * byName(a.donor, b.donor) || a.id - b.id
                    : (a, b) => sign * (a.totals[key] - b.totals[key]) || byName(a.donor, b.donor);
                return Array.from(groups.values())
                    .map(group => Object.assign(group, { rows: group.rows.sort(compareRows) }))
                    .sort(compareGroups);
            },
            totals(){
                let totals = zeros();
                this.shown.forEach(row => COLUMNS.forEach(col => { totals[col.key] += row.cents[col.key]; }));
                return totals;
            }
        },
        watch: {
            month(){
                this.load();
            }
        },
        methods: {
            number(value){
                return (Number(value) || 0).toLocaleString('es-MX');
            },
            // Amounts are kept in cents and printed with the digits of formatPrice
            amount(value){
                let text = (Math.abs(value) / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return (value < 0 ? '-' : '') + text;
            },
            cellClass(col, value){
                return {
                    'is-zero': !value,
                    'is-note': col.note,
                    'db-balance': col.balance,
                    'is-negative': value < 0 && (col.balance || col.key === 'previous')
                };
            },
            step(months){
                this.month = moment(this.month + '-01').add(months, 'month').format('YYYY-MM');
            },
            sortBy(key){
                // Names go A→Z first; amounts, largest first
                this.sort = this.sort.key === key ? { key, desc: !this.sort.desc } : { key, desc: key !== 'donor' };
            },
            sortIcon(key){
                if (this.sort.key !== key) return 'db-sort-icon';
                return 'db-sort-icon is-active ' + (this.sort.desc ? 'icon-feather-arrow-down' : 'icon-feather-arrow-up');
            },
            ariaSort(key){
                return this.sort.key === key ? (this.sort.desc ? 'descending' : 'ascending') : 'none';
            },
            async load(){
                let request = ++this.request;
                let month = this.month;
                this.loading = true;
                this.error = '';
                try {
                    let response = await axios.post(api.donorsBalanceReport, { month });
                    if (request !== this.request) return;
                    if (!Array.isArray(response.data.data)) {
                        throw new Error(response.data.msg);
                    }
                    // Frozen: the report is read-only, so Vue need not watch hundreds of rows
                    this.rows = Object.freeze(response.data.data.map(row => {
                        let amounts = figures(row, month);
                        let rowCents = Object.fromEntries(Object.entries(amounts).map(([key, value]) => [key, cents(value)]));
                        let source = [row.category, row.subcategory].filter(text => text && text !== '-').join(' · ') || 'Sin fuente';
                        return Object.freeze({
                            key: row.id + '|' + row.category + '|' + row.subcategory,
                            id: row.id,
                            donor: row.donor,
                            source,
                            searchKey: normalize(row.donor + ' ' + source),
                            cents: Object.freeze(rowCents)
                        });
                    }));
                } catch (error) {
                    if (request !== this.request) return;
                    this.rows = null;
                    this.error = 'No se pudieron cargar los saldos de ' + this.monthLabel.toLowerCase() + '. Intenta de nuevo.';
                } finally {
                    if (request === this.request) {
                        this.loading = false;
                    }
                }
            },
            async exportExcel(){
                let header = ['Donante', 'Fuente'].concat(COLUMNS.map(col =>
                    col.key === 'previous' ? 'Saldo anterior al ' + this.prevEnd
                        : col.key === 'balance' ? 'Saldo contable al ' + this.monthEnd
                        : col.label));
                let cell = value => ({ value: value / 100, style: 'money' });
                let lines = [];
                this.groups.forEach(group => group.rows.forEach(row => {
                    lines.push([row.donor, row.source].concat(COLUMNS.map(col => cell(row.cents[col.key]))));
                }));
                let sheet = [
                    [{ value: 'Saldos de donantes · ' + this.monthLabel, style: 'bold' }],
                    [(this.search ? 'Búsqueda «' + this.search + '» · generado el ' : 'Generado el ') + this.generatedAt],
                    [],
                    header.map(value => ({ value, style: 'bold' }))
                ].concat(lines, [
                    [{ value: 'Total', style: 'bold' }, null].concat(COLUMNS.map(col => ({ value: this.totals[col.key] / 100, style: 'boldMoney' })))
                ]);
                try {
                    await downloadXlsx('Saldos donantes ' + this.month + '.xlsx', 'Saldos ' + this.month, sheet, {
                        widths: [42, 30].concat(COLUMNS.map(() => 16)),
                        freeze: 4
                    });
                } catch (error) {
                    toastr.error('No se pudo generar el archivo de Excel.');
                }
            },
            print(){
                // Browsers name the saved PDF after the page title
                let title = document.title;
                document.title = 'Saldos de donantes ' + this.month;
                window.print();
                document.title = title;
            }
        },
        created(){
            let month = this.$route.query.month;
            if (/^\d{4}-(0[1-9]|1[0-2])$/.test(month || '')) {
                this.month = month;
            }
            this.load();
        }
    }
</script>

<style>
    .donors-balance .db-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }
    .donors-balance .db-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .donors-balance .db-actions .btn i,
    .donors-balance .db-step i {
        font-size: 14px;
        vertical-align: -2px;
    }

    /* TONES */
    .donors-balance .is-negative {
        color: #d0312d !important;
    }
    .donors-balance .db-muted {
        margin: 0;
        color: rgba(90, 99, 126, .85);
        font-size: .82rem;
    }

    /* PERIOD */
    .donors-balance .db-cover {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem 2rem;
        padding: 1.25rem 1.75rem;
        border-top: 3px solid #047bf8;
    }
    .donors-balance .db-eyebrow {
        color: #047bf8;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
    }
    .donors-balance .db-cover h1 {
        margin: .15rem 0 .25rem;
        color: #1f2a37;
        font-size: 1.6rem;
        font-weight: 700;
    }
    .donors-balance .db-period {
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .donors-balance .db-step {
        padding: .45rem .6rem;
        line-height: 1;
    }
    .donors-balance .db-month.el-date-editor {
        width: 150px;
    }
    .donors-balance .db-month .el-input__inner {
        font-weight: 600;
        text-align: center;
    }
    .donors-balance .db-print-meta,
    .donors-balance .db-print-filter,
    .donors-balance .db-paper {
        display: none;
    }

    .donors-balance .is-loading {
        opacity: .45;
    }
    .donors-balance .db-section {
        padding: 1.25rem 1.5rem 1rem;
    }

    /* SEARCH */
    .donors-balance .db-toolbar {
        margin-bottom: 1rem;
    }
    .donors-balance .db-search {
        position: relative;
        max-width: 360px;
    }
    .donors-balance .db-search i {
        position: absolute;
        top: 50%;
        left: .8rem;
        color: rgba(90, 99, 126, .7);
        transform: translateY(-50%);
    }
    .donors-balance .db-search .form-control {
        padding-left: 2.2rem;
    }

    /* TABLE: scrolls inside its box so the headers and the names stay in view */
    .donors-balance .db-table-wrap {
        max-height: calc(100vh - 230px);
        min-height: 320px;
        margin: 0 -1.5rem;
        overflow: auto;
        border-top: 1px solid rgba(0, 0, 0, .06);
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        transition: opacity .15s;
    }
    .donors-balance .db-table {
        width: 100%;
        min-width: 1480px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: .8rem;
    }
    .donors-balance .db-table thead {
        position: sticky;
        top: 0;
        z-index: 3;
    }
    .donors-balance .db-table thead th {
        padding: .55rem .6rem;
        border-bottom: 1px solid rgba(0, 0, 0, .08);
        background-color: #f7f8fb;
        color: rgba(90, 99, 126, .95);
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .3px;
        line-height: 1.25;
        text-transform: uppercase;
        vertical-align: bottom;
        white-space: normal;
    }
    .donors-balance .db-table thead small {
        display: block;
        margin-top: .1rem;
        font-size: .66rem;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
    }
    .donors-balance .db-table thead .db-band {
        padding-top: .45rem;
        padding-bottom: .3rem;
        border-bottom-width: 2px;
        text-align: center;
        vertical-align: middle;
    }
    .donors-balance .db-band.is-in { border-bottom-color: #5eb314; color: #3d8a07; }
    .donors-balance .db-band.is-out { border-bottom-color: #e65252; color: #d0312d; }
    .donors-balance .db-sort {
        cursor: pointer;
    }
    .donors-balance .db-sort:hover,
    .donors-balance .db-sort:focus {
        color: #047bf8;
        outline: none;
    }
    .donors-balance .db-sort-icon {
        display: inline-block;
        width: 10px;
        font-size: 10px;
    }
    .donors-balance .db-sort-icon.is-active {
        color: #047bf8;
    }
    .donors-balance .db-table td,
    .donors-balance .db-table tbody th {
        padding: .5rem .6rem;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        background-color: #fff;
        color: #3e4b5b;
        vertical-align: top;
    }
    .donors-balance .db-table .db-amount {
        min-width: 88px;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .donors-balance .db-table td.db-amount {
        white-space: nowrap;
    }
    .donors-balance .db-table .is-zero {
        color: #c3c9d6;
    }
    .donors-balance .db-table .is-note:not(.is-zero) {
        color: #7a8399;
    }
    .donors-balance .db-table td.db-balance {
        color: #1f2a37;
        font-weight: 700;
    }
    .donors-balance .db-table .db-balance {
        border-left: 1px solid rgba(0, 0, 0, .06);
    }
    /* Names stay put while the amounts scroll sideways */
    .donors-balance .db-table .db-name {
        position: sticky;
        left: 0;
        z-index: 1;
        width: 300px;
        min-width: 240px;
        max-width: 320px;
        border-right: 1px solid rgba(0, 0, 0, .06);
        text-align: left;
        white-space: normal;
    }
    .donors-balance .db-table thead .db-name {
        z-index: 4;
    }
    .donors-balance .db-table tbody .db-name {
        font-weight: 400;
    }
    .donors-balance .db-donor .db-name a {
        color: #1f2a37;
        font-weight: 700;
        overflow-wrap: anywhere;
    }
    .donors-balance .db-donor .db-name a:hover {
        color: #047bf8;
    }
    .donors-balance .db-donor .db-name a i {
        color: #047bf8;
        font-size: 11px;
        opacity: .6;
    }
    .donors-balance .db-donor .db-name small,
    .donors-balance .db-table tfoot small {
        display: block;
        margin-top: .1rem;
        color: rgba(90, 99, 126, .85);
        font-size: .72rem;
        font-weight: 500;
    }
    .donors-balance .db-table tbody:hover td,
    .donors-balance .db-table tbody:hover th {
        background-color: #f8faff;
    }
    /* A donor with several sources: its totals on top, then each source */
    .donors-balance .is-split .db-donor td {
        font-weight: 700;
    }
    .donors-balance .is-split .db-donor td,
    .donors-balance .is-split .db-donor th {
        border-bottom-style: dashed;
    }
    .donors-balance .db-source th.db-name {
        padding-left: 1.6rem;
        color: #5a637e;
        font-size: .76rem;
    }
    .donors-balance .db-source th.db-name::before {
        content: "";
        display: inline-block;
        width: 6px;
        height: 6px;
        margin: 0 .5rem 1px -.9rem;
        border-left: 1px solid #b9c2d3;
        border-bottom: 1px solid #b9c2d3;
    }
    .donors-balance .db-source td {
        font-size: .76rem;
    }
    .donors-balance .db-table tfoot {
        position: sticky;
        bottom: 0;
        z-index: 2;
    }
    .donors-balance .db-table tfoot th,
    .donors-balance .db-table tfoot td {
        padding: .65rem .6rem;
        border-top: 2px solid rgba(0, 0, 0, .1);
        background-color: #f7f8fb;
        color: #1f2a37;
        font-weight: 700;
    }
    .donors-balance .db-table tfoot .is-zero {
        color: #c3c9d6;
    }
    /* STATES */
    .donors-balance .db-state,
    .donors-balance .db-empty {
        padding: 2.5rem 1rem;
        text-align: center;
        color: #5a637e;
    }
    .donors-balance .db-state-icon,
    .donors-balance .db-empty > i {
        display: block;
        margin-bottom: .5rem;
        color: #b9c2d3;
        font-size: 28px;
    }
    .donors-balance .db-skeleton {
        display: grid;
        gap: .9rem;
        padding: 1rem 0 1.5rem;
    }
    .donors-balance .db-skeleton-line {
        display: block;
        height: 14px;
        border-radius: 4px;
        background: linear-gradient(90deg, #eef0f5 25%, #f6f7fa 50%, #eef0f5 75%);
        background-size: 200% 100%;
        animation: db-shimmer 1.2s infinite linear;
    }
    @keyframes db-shimmer {
        from { background-position: 200% 0; }
        to { background-position: -200% 0; }
    }

    @media screen and (max-width: 575px) {
        .donors-balance .db-action-label {
            display: none;
        }
        .donors-balance .db-cover,
        .donors-balance .db-section {
            padding: 1.1rem;
        }
        .donors-balance .db-cover h1 {
            font-size: 1.3rem;
        }
        .donors-balance .db-table-wrap {
            margin: 0 -1.1rem;
        }
        .donors-balance .db-table .db-name {
            width: 180px;
            min-width: 160px;
        }
    }

    /* PRINT: its own landscape page, whatever other screens set on the unnamed page */
    @page donors-balance {
        size: landscape;
        margin: 10mm 8mm 11mm;
    }
    @media print {
        /* The app zooms body to 90%; on paper the report sets its own sizes */
        body:has(.donors-balance) {
            zoom: 1 !important;
        }
        .donors-balance {
            page: donors-balance;
            font-size: 8pt;
        }
        .donors-balance * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .donors-balance .db-header,
        .donors-balance .db-period,
        .donors-balance .db-screen,
        .donors-balance .db-toolbar,
        .donors-balance .db-state {
            display: none !important;
        }
        .donors-balance .is-loading {
            opacity: 1;
        }
        .donors-balance .db-cover {
            padding: 3mm 4mm;
            margin-bottom: 3mm !important;
            border: 1px solid #dfe3eb;
            border-top: 3px solid #047bf8;
            border-radius: 5px;
        }
        .donors-balance .db-cover h1 {
            font-size: 14pt;
        }
        .donors-balance .db-eyebrow,
        .donors-balance .db-muted {
            font-size: 7pt;
        }
        .donors-balance .db-print-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1mm;
            color: #5a637e;
            font-size: 7pt;
        }
        .donors-balance .db-print-meta img {
            max-width: 30mm;
            max-height: 11mm;
        }
        .donors-balance .db-section {
            padding: 0;
            border: 0;
            box-shadow: none;
        }
        .donors-balance .db-paper {
            display: inline;
        }
        .donors-balance .db-print-filter {
            display: block;
            margin: 0 0 2mm;
            color: #5a637e;
            font-size: 7pt;
        }
        .donors-balance .db-table-wrap {
            max-height: none;
            min-height: 0;
            margin: 0;
            overflow: visible;
            border: 0;
        }
        .donors-balance .db-table {
            min-width: 0;
            font-size: 6.5pt;
        }
        .donors-balance .db-table thead,
        .donors-balance .db-table tfoot {
            position: static;
        }
        .donors-balance .db-table thead {
            display: table-header-group;
        }
        .donors-balance .db-table tfoot {
            display: table-row-group;
        }
        .donors-balance .db-table thead th,
        .donors-balance .db-table tbody th,
        .donors-balance .db-table tbody td,
        .donors-balance .db-table tfoot th,
        .donors-balance .db-table tfoot td {
            padding: 1mm .55mm;
            line-height: 1.3;
        }
        .donors-balance .db-table tr > :first-child {
            padding-left: 1mm;
        }
        /* Totals are the widest figures: a touch smaller so all thirteen columns fit on Letter */
        .donors-balance .db-table tfoot td {
            padding-left: 1.1mm;
            font-size: 6pt;
        }
        .donors-balance .db-table thead th {
            letter-spacing: 0;
            hyphens: auto;
        }
        .donors-balance .db-table thead th,
        .donors-balance .db-table thead small {
            font-size: 5.5pt;
        }
        .donors-balance .db-table .db-name {
            position: static;
            width: auto;
            min-width: 28mm;
            max-width: none;
        }
        .donors-balance .db-donor .db-name a {
            text-decoration: none;
        }
        .donors-balance .db-table .db-amount {
            min-width: 0;
        }
        .donors-balance .db-sort-icon {
            display: none;
        }
        .donors-balance .db-donor .db-name a i {
            display: none;
        }
        .donors-balance .db-donor .db-name small,
        .donors-balance .db-table tfoot small,
        .donors-balance .db-source th.db-name,
        .donors-balance .db-source td {
            font-size: 6pt;
        }
        .donors-balance .db-source th.db-name {
            padding-left: 3mm;
        }
        .donors-balance .db-table tbody:hover td,
        .donors-balance .db-table tbody:hover th {
            background-color: #fff;
        }
        .donors-balance .db-table tr {
            break-inside: avoid;
        }
        /* A donor's sources stay with it unless the group is long */
        .donors-balance .db-table tbody.is-split {
            break-inside: avoid;
        }
    }
</style>
