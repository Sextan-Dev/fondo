<template>
    <div class="content-box project-report">
        <div class="element-wrapper">
            <!-- HEADER & BUTTONS: screen only -->
            <h6 class="element-header rp-header">
                <span class="default-title"><i class="icon-feather-file-text"></i> <span>Reportes generales</span></span>
                <div v-if="selected" class="rp-actions">
                    <button type="button" class="btn btn-sm btn-white" @click="changeProject">
                        <i class="icon-feather-repeat"></i><span class="rp-action-label"> Cambiar proyecto</span>
                    </button>
                    <button v-if="project" type="button" class="btn btn-sm btn-white" @click="$router.push({name: 'TaskList', params: {project: project.id}})">
                        <i class="icon-feather-layers"></i><span class="rp-action-label"> Actividades</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" :disabled="!project" @click="print">
                        <i class="icon-feather-printer"></i><span class="rp-action-label"> Imprimir</span>
                    </button>
                </div>
            </h6>

            <!-- PROJECT PICKER: no report opens until a project is chosen -->
            <div v-if="!selected" class="rp-picker">
                <div class="element-box rp-picker-card">
                    <div class="rp-picker-head">
                        <div class="rp-picker-icon"><i class="icon-feather-file-text"></i></div>
                        <div>
                            <h4>¿De qué proyecto quieres ver el reporte?</h4>
                            <p>Búscalo por su nombre o filtra por estatus, y pulsa <strong>Ver reporte</strong>.</p>
                        </div>
                    </div>

                    <div class="rp-search">
                        <i class="icon-feather-search"></i>
                        <input ref="search" v-model="search" type="search" class="form-control" placeholder="Escribe el nombre del proyecto"
                               autocomplete="off" aria-label="Buscar proyecto por nombre"
                               @keydown.down.prevent="focusOption(0)" @keydown.enter.prevent="searchEnter">
                    </div>

                    <div v-if="projectsReady && statusFilters.length > 2" class="rp-filters" role="group" aria-label="Filtrar por estatus">
                        <button v-for="filter in statusFilters" :key="filter.key" type="button" class="rp-filter"
                                :class="{active: status === filter.key}" :aria-pressed="status === filter.key ? 'true' : 'false'"
                                @click="status = filter.key">
                            <span v-if="filter.key" class="rp-dot" :class="'is-' + filter.tone"></span>{{filter.label}}
                            <span class="rp-filter-count">{{number(filter.count)}}</span>
                        </button>
                    </div>

                    <!-- RECENTLY OPENED: one click back to the reports this person uses -->
                    <div v-if="recents.length && !search && !status" class="rp-recents">
                        <div class="rp-list-label">Abiertos recientemente</div>
                        <div class="rp-recents-list">
                            <button v-for="pjt in recents" :key="pjt.id" type="button" class="rp-recent" :title="pjt.name" @click="open(pjt)">
                                <i class="icon-feather-clock"></i><span>{{pjt.name}}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="projectsReady && projects.length" class="rp-list-label rp-list-head">
                        <span>{{search || status ? 'Resultados' : 'Todos los proyectos'}} <span class="rp-filter-count">{{number(matches.length)}}</span></span>
                        <span class="rp-list-order">Del más reciente al más antiguo</span>
                    </div>

                    <ul v-if="projectsReady && visible.length" ref="options" class="rp-options" aria-label="Proyectos">
                        <li v-for="(pjt, index) in visible" :key="pjt.id">
                            <button type="button" class="rp-option" @click="open(pjt)"
                                    @keydown.down.prevent="focusOption(index + 1)" @keydown.up.prevent="focusOption(index - 1)">
                                <span class="rp-option-text">
                                    <span class="rp-option-name">{{pjt.name}}</span>
                                    <span class="rp-option-meta">
                                        <span class="rp-dot" :class="'is-' + statusTone(pjt.status)"></span>{{pjt.status || 'Sin estatus'}}
                                        <template v-if="pjt.period"> · {{pjt.period}}</template>
                                        <template v-if="pjt.program"> · {{pjt.program}}</template>
                                    </span>
                                </span>
                                <span class="rp-option-cta"><span class="rp-option-cta-label">Ver reporte</span> <i class="icon-feather-arrow-right"></i></span>
                            </button>
                        </li>
                    </ul>
                    <div v-else-if="projectsReady" class="rp-empty">
                        <i class="icon-feather-search"></i>
                        <p v-if="!projects.length">No tienes proyectos disponibles.</p>
                        <p v-else-if="search">No encontramos proyectos con «{{search}}»{{status ? ' en «' + statusLabel + '»' : ''}}.</p>
                        <p v-else>No hay proyectos con estatus «{{statusLabel}}».</p>
                        <button v-if="search || status" type="button" class="btn btn-white btn-sm" @click="clearFilters">Limpiar búsqueda</button>
                    </div>
                    <ul v-else class="rp-options" aria-hidden="true">
                        <li v-for="n in 6" :key="n" class="rp-option-skeleton"><span class="rp-skeleton"></span></li>
                    </ul>

                    <button v-if="matches.length > visible.length" type="button" class="btn btn-white btn-sm rp-more" @click="limit += PAGE_SIZE">
                        Mostrar {{Math.min(PAGE_SIZE, matches.length - visible.length)}} más
                        <span class="rp-more-left">de {{number(matches.length - visible.length)}} restantes</span>
                    </button>
                </div>
            </div>

            <!-- LOADING / ERROR -->
            <div v-else-if="!report" class="element-box rp-state">
                <template v-if="error">
                    <div class="rp-state-icon"><i class="icon-feather-alert-circle"></i></div>
                    <p>{{error}}</p>
                    <button type="button" class="btn btn-primary btn-sm" @click="changeProject">Elegir otro proyecto</button>
                </template>
                <div v-else class="rp-state-skeleton" aria-label="Cargando reporte">
                    <span class="rp-skeleton" style="width: 22%"></span>
                    <span class="rp-skeleton is-title" style="width: 65%"></span>
                    <span class="rp-skeleton" style="width: 90%"></span>
                    <span class="rp-skeleton" style="width: 80%"></span>
                </div>
            </div>

            <!-- REPORT -->
            <article v-else class="rp-report">
                <!-- COVER -->
                <header class="element-box rp-cover">
                    <div class="rp-cover-top">
                        <div class="rp-cover-title">
                            <div class="rp-eyebrow">{{task ? 'Reporte de actividad' : 'Reporte de proyecto'}} · #{{project.id}}</div>
                            <h1>{{project.name}}</h1>
                            <div v-if="task" class="rp-cover-task"><i class="icon-feather-layers"></i> {{task.name}}</div>
                        </div>
                        <div class="rp-cover-brand">
                            <img v-if="auth && auth.company_logo" :src="appDomain + auth.company_logo" alt="">
                            <span>Generado el {{generatedAt}}</span>
                        </div>
                    </div>
                    <dl class="rp-meta">
                        <div><dt>Estatus</dt><dd><span class="rp-status" :class="'is-' + statusTone(project.status)">{{project.status || 'Sin estatus'}}</span></dd></div>
                        <div><dt>Programa</dt><dd>{{project.program || '—'}}</dd></div>
                        <div><dt>Pilar</dt><dd>{{project.pilar || '—'}}</dd></div>
                        <div><dt>Proyecto sombrilla</dt><dd>{{project.project_umbrella || '—'}}</dd></div>
                        <div><dt>Responsable</dt><dd>{{(task ? task.respName : project.respName) || '—'}}</dd></div>
                        <div><dt>Donante</dt><dd>{{project.donorName || '—'}}</dd></div>
                        <div><dt>Aliados</dt><dd>{{number(project.countAllies)}}</dd></div>
                        <div><dt>Periodo</dt><dd>{{period}}</dd></div>
                    </dl>
                </header>

                <!-- KPIs -->
                <section class="rp-kpis">
                    <div class="rp-kpi">
                        <div class="rp-kpi-icon is-primary"><i class="icon-feather-dollar-sign"></i></div>
                        <div class="label">Monto de inversión</div>
                        <div class="value">{{money(project.amount_invertion)}}</div>
                    </div>
                    <div class="rp-kpi">
                        <div class="rp-kpi-icon is-success"><i class="icon-feather-user-check"></i></div>
                        <div class="label">Benef. directos</div>
                        <div class="value">{{number(project.beneficiary_direct)}}</div>
                    </div>
                    <div class="rp-kpi">
                        <div class="rp-kpi-icon is-info"><i class="icon-feather-users"></i></div>
                        <div class="label">Benef. indirectos</div>
                        <div class="value">{{number(project.beneficiary_indirect)}}</div>
                    </div>
                    <div class="rp-kpi">
                        <div class="rp-kpi-icon is-warning"><i class="icon-feather-check-square"></i></div>
                        <div class="label">Actividades concluidas</div>
                        <div class="value">{{number(project.countTaskDone)}} <small>de {{number(project.countTask)}}</small></div>
                    </div>
                </section>

                <!-- OBJECTIVE & PROGRESS -->
                <section class="element-box rp-section rp-grid-2">
                    <div>
                        <h2 class="rp-section-title">Objetivo</h2>
                        <div v-if="hasText(project.description)" class="rp-richtext" v-safe-html="project.description"></div>
                        <p v-else class="rp-muted">Sin objetivo registrado.</p>
                        <template v-if="hasText(project.others_features_invertion)">
                            <h3 class="rp-subtitle">Otras necesidades de inversión</h3>
                            <div class="rp-richtext" v-safe-html="project.others_features_invertion"></div>
                        </template>
                    </div>
                    <div class="rp-avoid">
                        <h2 class="rp-section-title">Avance</h2>
                        <div class="rp-progress">
                            <div class="rp-progress-head"><span>Avance del proyecto</span><strong>{{progress}}%</strong></div>
                            <div class="rp-progress-track"><div class="rp-progress-fill" :style="{width: progress + '%'}"></div></div>
                        </div>
                        <div class="rp-progress">
                            <div class="rp-progress-head">
                                <span>Actividades concluidas</span>
                                <strong>{{number(project.countTaskDone)}} de {{number(project.countTask)}}</strong>
                            </div>
                            <div class="rp-progress-track"><div class="rp-progress-fill is-success" :style="{width: percent(project.countTaskDone, project.countTask) + '%'}"></div></div>
                        </div>
                        <p class="rp-muted mb-0">Actualizado al {{generatedAt}}</p>
                    </div>
                </section>

                <!-- GENERAL DETAILS -->
                <section v-if="details.length" class="element-box rp-section rp-avoid">
                    <h2 class="rp-section-title">Detalles generales</h2>
                    <dl class="rp-details">
                        <div v-for="item in details" :key="item.label"><dt>{{item.label}}</dt><dd>{{item.value}}</dd></div>
                    </dl>
                </section>

                <!-- BENEFICIARIES -->
                <section class="element-box rp-section rp-grid-2">
                    <div>
                        <h2 class="rp-section-title">Beneficiarios</h2>
                        <h3 class="rp-subtitle">Perfil de beneficiarios</h3>
                        <div v-if="hasText(project.beneficiary_perfil)" class="rp-richtext" v-safe-html="project.beneficiary_perfil"></div>
                        <p v-else class="rp-muted">Sin perfil registrado.</p>
                    </div>
                    <div class="rp-avoid">
                        <h3 class="rp-subtitle rp-subtitle-aligned">Rangos de edad</h3>
                        <ul v-if="ageMax" class="rp-bars">
                            <li v-for="item in ageRanges" :key="item.label">
                                <span class="rp-bar-label">{{item.label}}</span>
                                <span class="rp-bar-track"><span class="rp-bar-fill" :style="{width: percent(item.value, ageMax) + '%'}"></span></span>
                                <strong>{{number(item.value)}}</strong>
                            </li>
                        </ul>
                        <p v-else class="rp-muted">Sin beneficiarios registrados por edad.</p>
                    </div>
                </section>

                <!-- VOLUNTEERING -->
                <section v-if="Number(project.volunteers_required)" class="element-box rp-section rp-grid-2 rp-avoid">
                    <div>
                        <h2 class="rp-section-title">Voluntariado</h2>
                        <ul v-if="volunteers.length" class="rp-bars">
                            <li v-for="item in volunteers" :key="item.label">
                                <span class="rp-bar-label">{{item.label}}</span>
                                <span class="rp-bar-track"><span class="rp-bar-fill is-info" :style="{width: percent(item.value, volunteerMax) + '%'}"></span></span>
                                <strong>{{number(item.value)}}</strong>
                            </li>
                        </ul>
                        <p v-if="project.volunteering_hours" class="rp-muted mt-2 mb-0">Horas de voluntariado: <strong>{{project.volunteering_hours}}</strong></p>
                    </div>
                    <div>
                        <h3 class="rp-subtitle rp-subtitle-aligned">Descripción de actividades</h3>
                        <div v-if="hasText(project.volunteering_description)" class="rp-richtext" v-safe-html="project.volunteering_description"></div>
                        <p v-else class="rp-muted">Sin descripción registrada.</p>
                    </div>
                </section>

                <!-- SDGs -->
                <section v-if="report.ods.length" class="element-box rp-section rp-avoid">
                    <h2 class="rp-section-title">Objetivos de Desarrollo Sostenible</h2>
                    <div class="rp-ods">
                        <div v-if="report.odsImg.length" class="rp-ods-images">
                            <img v-for="slug in report.odsImg" :key="slug" :src="appDomain + '/nu/' + $language.current + '/' + slug + '.jpg'" :alt="slug" @error="$event.target.style.display = 'none'">
                        </div>
                        <ul class="rp-ods-list">
                            <li v-for="(item, index) in report.ods" :key="index">{{item.label}}</li>
                        </ul>
                    </div>
                </section>

                <!-- BALANCE & OPERATIONS -->
                <section v-if="operations.length" class="element-box rp-section">
                    <h2 class="rp-section-title">Balance y operaciones</h2>
                    <div class="rp-figures">
                        <div class="rp-figure is-success"><span>Ingresos</span><strong>{{money(report.incomes)}}</strong></div>
                        <div class="rp-figure is-danger"><span>Egresos</span><strong>{{money(report.outcomes)}}</strong></div>
                        <div class="rp-figure"><span>Balance</span><strong>{{money(report.balance)}}</strong></div>
                        <div class="rp-figure is-warning"><span>Costo de operación</span><strong>{{money(report.monitoring)}}</strong></div>
                        <div class="rp-figure is-primary"><span>Saldo disponible</span><strong>{{money(report.balance - report.fee)}}</strong></div>
                    </div>

                    <h3 class="rp-subtitle">Listado de operaciones <span class="rp-count">{{number(operations.length)}}</span></h3>
                    <div class="rp-table-wrap">
                        <table class="rp-table rp-operations">
                            <thead>
                                <tr>
                                    <th>Fecha y pago</th>
                                    <th>Donante</th>
                                    <th>Destino</th>
                                    <th>Categoría</th>
                                    <th>Observaciones</th>
                                    <th class="rp-amount">Ingreso</th>
                                    <th class="rp-amount">Egreso</th>
                                    <th class="rp-amount">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(op, index) in operations" :key="index">
                                    <td class="rp-nowrap">{{op.date}}<small v-if="op.mode" class="rp-cell-note">{{op.mode}}</small></td>
                                    <td>{{op.donor || '—'}}</td>
                                    <td>{{op.client || '—'}}</td>
                                    <td>{{op.category || '—'}}<small v-if="op.subcategory" class="rp-cell-note">{{op.subcategory}}</small></td>
                                    <td class="rp-comment">{{op.comment || '—'}}</td>
                                    <td class="rp-amount is-income">{{op.type === 'income' ? money(op.amount) : ''}}</td>
                                    <td class="rp-amount is-outcome">{{op.type === 'outcome' ? money(-op.amount) : ''}}</td>
                                    <td class="rp-amount rp-strong">{{money(op.balance)}}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5">Totales del listado</th>
                                    <th class="rp-amount is-income">{{money(totals.incomes)}}</th>
                                    <th class="rp-amount is-outcome">{{money(-totals.outcomes)}}</th>
                                    <th class="rp-amount">{{money(totals.incomes - totals.outcomes)}}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <!-- OPERATING COST AMORTIZATION -->
                <section v-if="amortization.total > 0" class="element-box rp-section rp-avoid">
                    <h2 class="rp-section-title">Amortización del costo operativo</h2>
                    <div class="rp-amortization">
                        <table class="rp-table">
                            <thead><tr><th>Mes</th><th class="rp-amount">Monto</th></tr></thead>
                            <tbody>
                                <tr v-for="(row, index) in amortization.rows" :key="index">
                                    <td class="rp-capitalize">{{row.label}}</td>
                                    <td class="rp-amount">{{money(row.amount)}}</td>
                                </tr>
                            </tbody>
                            <tfoot><tr><th>Total</th><th class="rp-amount">{{money(amortization.total)}}</th></tr></tfoot>
                        </table>
                        <div class="rp-figures is-stacked">
                            <div class="rp-figure is-warning"><span>Costo de operación total</span><strong>{{money(amortization.total)}}</strong></div>
                            <div v-if="amortization.monthly !== null" class="rp-figure"><span>Costo de operación mensual</span><strong>{{money(amortization.monthly)}}</strong></div>
                        </div>
                    </div>
                </section>

                <!-- ACTIVITIES -->
                <section v-if="tasks.length" class="element-box rp-section">
                    <h2 class="rp-section-title">{{task ? 'Actividad' : 'Actividades'}} <span v-if="!task" class="rp-count">{{number(tasks.length)}}</span></h2>
                    <div class="rp-table-wrap">
                        <table class="rp-table rp-tasks">
                            <thead>
                                <tr><th>Actividad</th><th>Estatus</th><th>Responsable</th><th>Fechas</th><th>Descripción</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in tasks" :key="item.id">
                                    <td class="rp-strong">{{item.name}}</td>
                                    <td><span class="rp-status" :class="'is-' + taskStatus(item.status).tone">{{taskStatus(item.status).name}}</span></td>
                                    <td>{{item.respName || '—'}}</td>
                                    <td class="rp-nowrap">{{item.dateFrom || '—'}}<template v-if="item.dateTo && item.dateTo !== item.dateFrom"> – {{item.dateTo}}</template></td>
                                    <td>
                                        <div v-if="hasText(item.description_html)" class="rp-richtext" v-safe-html="item.description_html"></div>
                                        <span v-else>{{item.description || '—'}}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="rp-footer">{{task ? 'Reporte de actividad' : 'Reporte de proyecto'}} #{{project.id}} · Generado el {{generatedAt}}</footer>
            </article>
        </div>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";

    // The picker draws this many projects at a time; "Mostrar más" adds as many again
    const PAGE_SIZE = 30;
    // Status filter value for projects without one ('' means every status)
    const NO_STATUS = 'none';
    const RECENT_KEY = 'fondo.projectReport.recent';
    const RECENT_MAX = 4;
    const STATUS_TONE = { 'En ejecución': 'primary', 'Concluido': 'success' };
    const TASK_STATUS = {
        pending: { name: 'Pendiente', tone: 'warning' },
        approved: { name: 'Aprobada', tone: 'info' },
        working: { name: 'En curso', tone: 'primary' },
        done: { name: 'Finalizada', tone: 'success' },
        canceled: { name: 'Cancelada', tone: 'danger' }
    };
    const MONEY = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    // Search ignores case and accents: "educacion" finds "Educación".
    const normalize = value => String(value == null ? '' : value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    // Rich text fields often hold nothing but empty paragraphs and &nbsp;
    const hasText = html => String(html == null ? '' : html).replace(/<[^>]*>|&nbsp;|\s/g, '') !== '';

	export default {
        props:['auth','appDomain','siteName'],
        data(){
            return {
                projects: [],
                projectsReady: false,
                search: '',
                status: '',
                limit: PAGE_SIZE,
                recentIds: [],
                PAGE_SIZE,
                report: null,
                error: '',
                generatedAt: moment().format('DD/MM/YYYY')
            }
        },
        computed: {
            // The report to show comes from the URL, so a reload or a shared link opens the same one
            selected(){
                let query = this.$route.query;
                return query.project || query.task ? query : null;
            },
            // "Todos" plus one chip per status, the most common first and "Sin estatus" last
            statusFilters(){
                let counts = _.countBy(this.projects, pjt => pjt.status || NO_STATUS);
                let order = key => key === NO_STATUS ? -1 : counts[key];
                let filters = Object.keys(counts).sort((a, b) => order(b) - order(a)).map(key => ({
                    key,
                    label: key === NO_STATUS ? 'Sin estatus' : key,
                    tone: key === NO_STATUS ? 'secondary' : this.statusTone(key),
                    count: counts[key]
                }));
                return [{ key: '', label: 'Todos', count: this.projects.length }].concat(filters);
            },
            statusLabel(){
                let filter = this.statusFilters.find(item => item.key === this.status);
                return filter ? filter.label : '';
            },
            matches(){
                let terms = normalize(this.search).split(/\s+/).filter(Boolean);
                return this.projects.filter(pjt =>
                    (!this.status || (pjt.status || NO_STATUS) === this.status) && terms.every(term => pjt.key.includes(term)));
            },
            visible(){
                return this.matches.slice(0, this.limit);
            },
            recents(){
                let byId = new Map(this.projects.map(pjt => [pjt.id, pjt]));
                return this.recentIds.map(id => byId.get(id)).filter(Boolean).slice(0, RECENT_MAX);
            },
            project(){
                return this.report ? this.report.project : null;
            },
            tasks(){
                return this.report ? this.report.tasks : [];
            },
            task(){
                return this.selected && this.selected.task ? this.tasks[0] || null : null;
            },
            // Running balance down the list, as a bank statement reads
            operations(){
                let balance = 0;
                return (this.report ? this.report.operations : []).map(op => {
                    let amount = Number(op.amount) || 0;
                    balance += op.type === 'income' ? amount : op.type === 'outcome' ? -amount : 0;
                    return Object.assign({}, op, { amount, balance });
                });
            },
            totals(){
                return this.operations.reduce((totals, op) => {
                    if (op.type === 'income') totals.incomes += op.amount;
                    if (op.type === 'outcome') totals.outcomes += op.amount;
                    return totals;
                }, { incomes: 0, outcomes: 0 });
            },
            progress(){
                return Math.min(100, Math.max(0, Math.round(Number(this.project.progress) || 0)));
            },
            period(){
                let from = this.project.dateFrom, to = this.project.dateTo;
                if (from && to) {
                    return from === to ? from : from + ' – ' + to;
                }
                return from || to || '—';
            },
            details(){
                let project = this.project;
                return [
                    { label: 'Público objetivo', value: project.target_audiences },
                    { label: 'Grado de rezago social', value: project.social_grade },
                    { label: 'Estados', value: project.zone_impact_state },
                    { label: 'Municipios', value: project.zone_impact_municipality },
                    { label: 'Localidades', value: project.zone_impact_locality }
                ].filter(item => item.value);
            },
            ageRanges(){
                return Object.entries(this.project.beneficiary_details || {}).map(([label, value]) => ({
                    label: /años/i.test(label) ? label : label.replace(/^mas\b/i, 'Más') + ' años',
                    value: Number(value) || 0
                }));
            },
            ageMax(){
                return Math.max(0, ...this.ageRanges.map(item => item.value));
            },
            volunteers(){
                return Object.entries(this.project.volunteering || {}).map(([label, value]) => {
                    let short = label.replace(/^n[uú]mero de voluntarios\s*/i, '');
                    return { label: short ? short.charAt(0).toUpperCase() + short.slice(1) : label, value: Number(value) || 0 };
                });
            },
            volunteerMax(){
                return Math.max(0, ...this.volunteers.map(item => item.value));
            },
            // Projects ministered by month carry their own schedule; the rest spread the cost evenly over their months
            amortization(){
                let project = this.project;
                if (Number(project.temporality) === 2) {
                    let rows = [];
                    try {
                        rows = JSON.parse(project.ministering_value || '[]') || [];
                    } catch (e) {
                        rows = [];
                    }
                    rows = Object.values(rows).map(row => ({
                        label: [row.month, row.year].filter(Boolean).join(' '),
                        amount: Number(row.amount) || 0
                    }));
                    return { rows, total: rows.reduce((total, row) => total + row.amount, 0), monthly: null };
                }
                let months = [];
                let cursor = moment(project.date_from).startOf('month');
                let end = moment(project.date_to).startOf('month');
                if (cursor.isValid() && end.isValid()) {
                    for (; !cursor.isAfter(end) && months.length < 240; cursor.add(1, 'month')) {
                        months.push(cursor.format('MMMM YYYY'));
                    }
                }
                let total = Number(this.report.monitoring) || 0;
                let monthly = total / (months.length || 1);
                return { rows: months.map(label => ({ label, amount: monthly })), total, monthly };
            }
        },
        watch: {
            search(){
                this.limit = PAGE_SIZE;
            },
            status(){
                this.limit = PAGE_SIZE;
            }
        },
        methods: {
            hasText,
            number(value){
                return (Number(value) || 0).toLocaleString('es-MX');
            },
            money(value){
                // Rounded to cents first: sums of floats leave -0.0000001, which prints as "-$0.00"
                return MONEY.format(Math.round((Number(value) || 0) * 100) / 100 || 0);
            },
            percent(part, total){
                return Number(total) ? Math.min(100, Math.round(Number(part) * 100 / Number(total))) : 0;
            },
            statusTone(status){
                return STATUS_TONE[status] || 'secondary';
            },
            taskStatus(status){
                return TASK_STATUS[status] || { name: status || 'Sin estatus', tone: 'secondary' };
            },
            async loadProjects(){
                try {
                    let response = await axios.post(api.dataReport);
                    if (response.data.success) {
                        // Newest first, each with its search key and years built once rather than on every keystroke
                        this.projects = Object.freeze(response.data.projects
                            .map(pjt => Object.freeze(Object.assign({}, pjt, {
                                key: normalize(pjt.id + ' ' + pjt.name + ' ' + (pjt.program || '')),
                                period: this.years(pjt.date_from, pjt.date_to)
                            })))
                            .sort((a, b) => b.id - a.id));
                    } else {
                        toastr.error(response.data.msg);
                    }
                } catch (error) {
                    toastr.error(error);
                } finally {
                    this.projectsReady = true;
                }
            },
            async load(){
                if (!this.selected) {
                    this.focusSearch();
                    return this.loadProjects();
                }
                try {
                    let response = await axios.post(api.projectReport, {
                        projectId: this.selected.project || null,
                        taskId: this.selected.task || null
                    });
                    if (response.data.success) {
                        // Frozen: the report is read-only, so Vue need not watch hundreds of operations
                        this.report = Object.freeze(response.data);
                        this.rememberProject(response.data.project.id);
                    } else {
                        this.error = response.data.msg;
                    }
                } catch (error) {
                    this.error = 'No se pudo cargar el reporte. Intenta de nuevo.';
                }
            },
            focusSearch(){
                // Only where there is a real keyboard: on phones the keyboard would cover the list
                if (window.matchMedia && window.matchMedia('(hover: hover)').matches) {
                    this.$nextTick(() => this.$refs.search && this.$refs.search.focus());
                }
            },
            // Arrow keys walk the list; going up from the first project returns to the search box
            focusOption(index){
                let options = this.$refs.options ? this.$refs.options.querySelectorAll('.rp-option') : [];
                if (index < 0) {
                    this.$refs.search.focus();
                } else if (options[index]) {
                    options[index].focus();
                }
            },
            // Enter opens the project when the search leaves only one; otherwise it moves into the list
            searchEnter(){
                if (this.matches.length === 1) {
                    this.open(this.matches[0]);
                } else {
                    this.focusOption(0);
                }
            },
            clearFilters(){
                this.search = '';
                this.status = '';
                this.$refs.search.focus();
            },
            years(from, to){
                let start = from ? String(from).slice(0, 4) : '';
                let end = to ? String(to).slice(0, 4) : '';
                return start && end && start !== end ? start + '–' + end : start || end;
            },
            // Recently opened reports live in this browser only: a convenience, not shared data
            rememberProject(id){
                id = Number(id);
                this.recentIds = [id].concat(this.recentIds.filter(item => item !== id)).slice(0, RECENT_MAX);
                try {
                    localStorage.setItem(RECENT_KEY, JSON.stringify(this.recentIds));
                } catch (e) {
                    // Storage blocked (private window): the list just isn't kept
                }
            },
            open(pjt){
                if (pjt) {
                    this.$router.push({ name: 'ProjectReport', query: { project: String(pjt.id) } });
                }
            },
            changeProject(){
                this.$router.push({ name: 'ProjectReport' });
            },
            print(){
                // Browsers name the saved PDF after the page title
                let title = document.title;
                document.title = (this.task ? 'Reporte actividad ' : 'Reporte ') + this.project.name;
                window.print();
                document.title = title;
            }
        },
        // App.vue keys the router-view by fullPath, so every URL (picker, each report) gets a fresh instance
        created(){
            try {
                let stored = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
                this.recentIds = Array.isArray(stored) ? stored.map(Number).filter(Boolean) : [];
            } catch (e) {
                this.recentIds = [];
            }
            // Other pages open a report with route params, which a reload loses: carry them in the URL instead
            let params = this.$route.params;
            if (params.projectId || params.taskId) {
                this.$router.replace({
                    name: 'ProjectReport',
                    query: params.taskId ? { task: String(params.taskId) } : { project: String(params.projectId) }
                });
                return;
            }
            this.load();
        }
    }
</script>

<style>
    .project-report .rp-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }
    .project-report .rp-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .project-report .rp-actions .btn i {
        font-size: 14px;
        vertical-align: -2px;
    }

    /* TONES shared by pills, icons and figures */
    .project-report .is-primary   { --rp-tone: #047bf8; --rp-tone-bg: rgba(4, 123, 248, .1); }
    .project-report .is-success   { --rp-tone: #3d8a07; --rp-tone-bg: rgba(94, 179, 20, .14); }
    .project-report .is-warning   { --rp-tone: #a77b00; --rp-tone-bg: rgba(251, 228, 160, .55); }
    .project-report .is-danger    { --rp-tone: #d0312d; --rp-tone-bg: rgba(230, 82, 82, .12); }
    .project-report .is-info      { --rp-tone: #7c3aed; --rp-tone-bg: rgba(124, 58, 237, .1); }
    .project-report .is-secondary { --rp-tone: #5a637e; --rp-tone-bg: #f0f2f7; }
    .project-report .rp-status {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: 999px;
        background-color: var(--rp-tone-bg, #f0f2f7);
        color: var(--rp-tone, #5a637e);
        font-size: .7rem;
        font-weight: 600;
        line-height: 1.35;
        white-space: nowrap;
    }

    /* SKELETONS */
    .project-report .rp-skeleton {
        display: block;
        height: 14px;
        border-radius: 6px;
        background: linear-gradient(90deg, #eef0f5 25%, #f6f7fa 50%, #eef0f5 75%);
        background-size: 200% 100%;
        animation: rp-shimmer 1.2s ease-in-out infinite;
    }
    @keyframes rp-shimmer {
        from { background-position: 100% 0; }
        to { background-position: -100% 0; }
    }

    /* PICKER */
    .project-report .rp-picker {
        display: flex;
        justify-content: center;
        padding: .5rem 0 2rem;
    }
    .project-report .rp-picker-card {
        width: 100%;
        max-width: 780px;
        margin: 0;
        padding: 1.75rem;
    }
    .project-report .rp-picker-head {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .project-report .rp-picker-icon,
    .project-report .rp-state-icon {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background-color: rgba(4, 123, 248, .1);
        color: #047bf8;
        font-size: 22px;
    }
    .project-report .rp-picker-head h4 {
        margin: 0 0 .25rem;
        font-size: 1.25rem;
    }
    .project-report .rp-picker-head p {
        margin: 0;
        color: #5a637e;
        font-size: .88rem;
    }
    .project-report .rp-search {
        position: relative;
    }
    .project-report .rp-search i {
        position: absolute;
        top: 50%;
        left: .9rem;
        transform: translateY(-50%);
        color: rgba(90, 99, 126, .7);
        font-size: 16px;
        pointer-events: none;
    }
    .project-report .rp-search .form-control {
        height: 46px;
        padding-left: 2.6rem;
        border-radius: 8px;
        font-size: .95rem;
    }
    /* Status chips: one row that scrolls on phones */
    .project-report .rp-filters {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: .9rem;
    }
    .project-report .rp-filter {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .45rem .35rem .8rem;
        border: 1px solid rgba(0, 0, 0, .1);
        border-radius: 999px;
        background-color: #fff;
        color: #3e4b5b;
        font-size: .8rem;
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
        cursor: pointer;
        transition: border-color .15s, background-color .15s, color .15s;
    }
    .project-report .rp-filter:hover {
        border-color: rgba(4, 123, 248, .4);
        color: #047bf8;
    }
    .project-report .rp-filter.active {
        border-color: #047bf8;
        background-color: #047bf8;
        color: #fff;
    }
    .project-report .rp-filter-count {
        display: inline-block;
        min-width: 1.5rem;
        padding: .1rem .45rem;
        border-radius: 999px;
        background-color: #f0f2f7;
        color: #5a637e;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 0;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .project-report .rp-filter.active .rp-filter-count {
        background-color: rgba(255, 255, 255, .22);
        color: #fff;
    }
    .project-report .rp-filter.active .rp-dot {
        background-color: #fff;
    }
    .project-report .rp-dot {
        display: inline-block;
        flex: 0 0 auto;
        width: 8px;
        height: 8px;
        margin-right: .35rem;
        border-radius: 50%;
        background-color: var(--rp-tone, #8a93a8);
        vertical-align: 1px;
    }
    .project-report .rp-filter .rp-dot {
        margin-right: 0;
    }
    .project-report .rp-list-label {
        margin: 1.4rem 0 .5rem;
        color: rgba(90, 99, 126, .85);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    .project-report .rp-list-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .25rem 1rem;
    }
    .project-report .rp-list-order {
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
    }

    /* Recently opened */
    .project-report .rp-recents-list {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .project-report .rp-recent {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        max-width: 100%;
        padding: .5rem .8rem;
        border: 1px solid rgba(4, 123, 248, .2);
        border-radius: 8px;
        background-color: rgba(4, 123, 248, .05);
        color: #1f2a37;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: border-color .15s, background-color .15s;
    }
    .project-report .rp-recent:hover {
        border-color: #047bf8;
        background-color: rgba(4, 123, 248, .1);
    }
    .project-report .rp-recent i {
        flex: 0 0 auto;
        color: #047bf8;
    }
    .project-report .rp-recent span {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        max-width: 260px;
    }

    /* Project list: every row is a button that opens its report */
    .project-report .rp-options {
        margin: 0;
        padding: 0;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 8px;
        list-style: none;
        overflow: hidden;
    }
    .project-report .rp-options li + li {
        border-top: 1px solid rgba(0, 0, 0, .06);
    }
    .project-report .rp-option {
        display: flex;
        align-items: center;
        gap: 1rem;
        width: 100%;
        padding: .8rem 1rem;
        border: 0;
        background-color: #fff;
        color: inherit;
        text-align: left;
        cursor: pointer;
        transition: background-color .15s;
    }
    .project-report .rp-option:hover,
    .project-report .rp-option:focus {
        outline: none;
        background-color: rgba(4, 123, 248, .06);
    }
    .project-report .rp-option:focus-visible {
        box-shadow: inset 3px 0 0 #047bf8;
    }
    .project-report .rp-option-text {
        display: block;
        flex: 1;
        min-width: 0;
    }
    .project-report .rp-option-name {
        display: block;
        color: #1f2a37;
        font-size: .88rem;
        font-weight: 600;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }
    .project-report .rp-option-meta {
        display: block;
        margin-top: .25rem;
        color: rgba(90, 99, 126, .9);
        font-size: .76rem;
    }
    .project-report .rp-option-cta {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        gap: .35rem;
        padding: .4rem .75rem;
        border: 1px solid rgba(4, 123, 248, .35);
        border-radius: 6px;
        color: #047bf8;
        font-size: .78rem;
        font-weight: 600;
        white-space: nowrap;
        transition: background-color .15s, color .15s, border-color .15s;
    }
    .project-report .rp-option:hover .rp-option-cta,
    .project-report .rp-option:focus .rp-option-cta {
        border-color: #047bf8;
        background-color: #047bf8;
        color: #fff;
    }
    .project-report .rp-options .rp-option-skeleton {
        padding: .9rem 1rem;
    }
    .project-report .rp-option-skeleton .rp-skeleton {
        max-width: 420px;
        height: 16px;
    }
    .project-report .rp-more {
        display: block;
        width: 100%;
        margin-top: .75rem;
        padding: .55rem;
        font-weight: 600;
    }
    .project-report .rp-more-left {
        color: rgba(90, 99, 126, .85);
        font-weight: 400;
    }
    .project-report .rp-empty {
        padding: 2.25rem 1rem;
        border: 1px dashed rgba(0, 0, 0, .12);
        border-radius: 8px;
        color: rgba(90, 99, 126, .85);
        text-align: center;
    }
    .project-report .rp-empty i {
        font-size: 24px;
    }
    .project-report .rp-empty p {
        margin: .5rem 0 .75rem;
        overflow-wrap: anywhere;
    }

    /* LOADING / ERROR */
    .project-report .rp-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        padding: 2.5rem 1.5rem;
        text-align: center;
    }
    .project-report .rp-state p {
        margin: 0;
        color: #5a637e;
    }
    .project-report .rp-state .rp-state-icon {
        background-color: rgba(230, 82, 82, .12);
        color: #d0312d;
    }
    .project-report .rp-state-skeleton {
        display: grid;
        gap: .9rem;
        width: 100%;
    }
    .project-report .rp-skeleton.is-title {
        height: 28px;
    }

    /* REPORT */
    .project-report .rp-report {
        display: grid;
        /* minmax(0, …): the wide operations table scrolls inside its box instead of widening the page */
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
    }
    .project-report .rp-report .element-box {
        margin: 0;
    }
    .project-report .rp-muted {
        color: rgba(90, 99, 126, .85);
        font-size: .82rem;
    }
    .project-report .rp-strong {
        color: #334152;
        font-weight: 600;
    }
    .project-report .rp-nowrap {
        white-space: nowrap;
    }
    .project-report .rp-capitalize {
        text-transform: capitalize;
    }
    .project-report .rp-eyebrow {
        color: rgba(90, 99, 126, .8);
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .6px;
        text-transform: uppercase;
    }

    /* COVER */
    .project-report .rp-cover {
        padding: 1.5rem 1.75rem;
        border-top: 4px solid #047bf8;
    }
    .project-report .rp-cover-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
    }
    .project-report .rp-cover-title {
        min-width: 0;
    }
    .project-report .rp-cover h1 {
        margin: .4rem 0 0;
        color: #1f2a37;
        font-size: 1.6rem;
        font-weight: 600;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }
    .project-report .rp-cover-task {
        margin-top: .5rem;
        color: #047bf8;
        font-weight: 600;
    }
    .project-report .rp-cover-brand {
        flex: 0 0 auto;
        color: rgba(90, 99, 126, .85);
        font-size: .75rem;
        text-align: right;
    }
    .project-report .rp-cover-brand img {
        display: block;
        max-width: 140px;
        max-height: 56px;
        margin: 0 0 .4rem auto;
    }
    .project-report .rp-meta,
    .project-report .rp-details {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 1rem 1.5rem;
        margin: 0;
    }
    .project-report .rp-meta {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(0, 0, 0, .07);
    }
    .project-report .rp-details {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
    .project-report .rp-meta dt,
    .project-report .rp-details dt {
        margin-bottom: .25rem;
        color: rgba(90, 99, 126, .8);
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .4px;
        text-transform: uppercase;
    }
    .project-report .rp-meta dd,
    .project-report .rp-details dd {
        margin: 0;
        color: #334152;
        font-size: .86rem;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    /* KPIs */
    .project-report .rp-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: .75rem;
    }
    /* The figure gets a row of its own: amounts in the millions need the card's full width */
    .project-report .rp-kpi {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        grid-template-areas: "icon label" "value value";
        align-items: center;
        gap: .6rem .6rem;
        padding: .9rem 1rem;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(126, 142, 177, .12);
    }
    .project-report .rp-kpi-icon {
        display: flex;
        grid-area: icon;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background-color: var(--rp-tone-bg);
        color: var(--rp-tone);
        font-size: 15px;
    }
    .project-report .rp-kpi .label {
        grid-area: label;
        color: rgba(90, 99, 126, .8);
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .4px;
        line-height: 1.3;
        text-transform: uppercase;
    }
    .project-report .rp-kpi .value {
        grid-area: value;
        color: #1f2a37;
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.3;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }
    .project-report .rp-kpi .value small {
        color: rgba(90, 99, 126, .8);
        font-size: .8rem;
        font-weight: 500;
    }

    /* SECTIONS */
    .project-report .rp-section {
        padding: 1.5rem 1.75rem;
    }
    .project-report .rp-grid-2 {
        display: grid;
        grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
        gap: 1.5rem 2.5rem;
    }
    .project-report .rp-section-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 0 0 1rem;
        color: #334152;
        font-size: .85rem;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    .project-report .rp-section-title::before {
        content: "";
        width: 4px;
        height: 1.1em;
        border-radius: 2px;
        background-color: #047bf8;
    }
    .project-report .rp-subtitle {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 1.25rem 0 .6rem;
        color: #334152;
        font-size: .8rem;
        font-weight: 700;
    }
    .project-report .rp-section-title + .rp-subtitle {
        margin-top: 0;
    }
    /* Lines the right column's first subtitle up with the left column's under its section title */
    .project-report .rp-subtitle-aligned {
        margin-top: 0;
        padding-top: 2.15rem;
    }
    .project-report .rp-count {
        padding: .1rem .5rem;
        border-radius: 999px;
        background-color: #f0f2f7;
        color: #5a637e;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 0;
    }
    .project-report .rp-richtext {
        color: #3e4b5b;
        font-size: .86rem;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }
    /* The theme fixes p at 14px: rich text follows its box instead (smaller in tables and on paper) */
    .project-report .rp-richtext p,
    .project-report .rp-richtext li {
        font-size: inherit;
    }
    .project-report .rp-richtext p:last-child,
    .project-report .rp-richtext ul:last-child,
    .project-report .rp-richtext ol:last-child {
        margin-bottom: 0;
    }
    .project-report .rp-richtext img {
        max-width: 100%;
        height: auto;
    }
    .project-report .rp-richtext table {
        max-width: 100%;
    }
    .project-report .rp-table .rp-richtext {
        font-size: inherit;
        line-height: 1.45;
    }
    .project-report .rp-table .rp-richtext p {
        margin-bottom: .35rem;
    }

    /* PROGRESS & BARS */
    .project-report .rp-progress {
        margin-bottom: 1.1rem;
    }
    .project-report .rp-progress-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .4rem;
        color: #5a637e;
        font-size: .82rem;
    }
    .project-report .rp-progress-head strong {
        color: #1f2a37;
        font-size: .95rem;
        font-variant-numeric: tabular-nums;
    }
    .project-report .rp-progress-track,
    .project-report .rp-bar-track {
        display: block;
        overflow: hidden;
        border-radius: 999px;
        background-color: #eef0f5;
    }
    .project-report .rp-progress-track {
        height: 10px;
    }
    .project-report .rp-progress-fill,
    .project-report .rp-bar-fill {
        display: block;
        height: 100%;
        border-radius: 999px;
        background-color: #047bf8;
    }
    .project-report .rp-progress-fill.is-success {
        background-color: #5eb314;
    }
    .project-report .rp-bar-fill.is-info {
        background-color: #7c3aed;
    }
    .project-report .rp-bars {
        display: grid;
        gap: .55rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .project-report .rp-bars li {
        display: grid;
        grid-template-columns: minmax(90px, 9rem) minmax(0, 1fr) 3.5rem;
        align-items: center;
        gap: .75rem;
        font-size: .82rem;
    }
    .project-report .rp-bar-label {
        color: #5a637e;
    }
    .project-report .rp-bar-track {
        height: 8px;
    }
    .project-report .rp-bars strong {
        color: #1f2a37;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    /* SDGs */
    .project-report .rp-ods {
        display: flex;
        align-items: flex-start;
        gap: 1.5rem;
    }
    .project-report .rp-ods-images {
        display: flex;
        flex: 0 0 auto;
        flex-wrap: wrap;
        gap: .5rem;
        max-width: 50%;
    }
    .project-report .rp-ods-images img {
        width: 88px;
        height: 88px;
        border-radius: 6px;
        object-fit: cover;
    }
    .project-report .rp-ods-list {
        margin: 0;
        padding-left: 1.1rem;
        color: #3e4b5b;
        font-size: .84rem;
        line-height: 1.55;
    }
    .project-report .rp-ods-list li + li {
        margin-top: .4rem;
    }

    /* FIGURES */
    .project-report .rp-figures {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: .75rem;
    }
    .project-report .rp-figures.is-stacked {
        grid-template-columns: minmax(0, 1fr);
        align-content: start;
    }
    .project-report .rp-figure {
        padding: .85rem 1rem;
        border-left: 3px solid var(--rp-tone, #334152);
        border-radius: 6px;
        background-color: #f7f8fb;
    }
    .project-report .rp-figure span {
        display: block;
        color: rgba(90, 99, 126, .85);
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .4px;
        text-transform: uppercase;
    }
    .project-report .rp-figure strong {
        display: block;
        margin-top: .2rem;
        color: var(--rp-tone, #1f2a37);
        font-size: 1.2rem;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    /* TABLES */
    .project-report .rp-table-wrap {
        margin: 0 -1.75rem;
        padding: 0 1.75rem;
        overflow-x: auto;
    }
    .project-report .rp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .8rem;
    }
    .project-report .rp-table th {
        padding: .6rem .65rem;
        border-bottom: 2px solid rgba(0, 0, 0, .08);
        color: rgba(90, 99, 126, .9);
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .project-report .rp-table td {
        padding: .6rem .65rem;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        color: #3e4b5b;
        vertical-align: top;
    }
    .project-report .rp-table tbody tr:nth-child(even) td {
        background-color: #fafbfc;
    }
    .project-report .rp-table tfoot th {
        border-top: 2px solid rgba(0, 0, 0, .1);
        border-bottom: 0;
        color: #1f2a37;
        font-size: .8rem;
        letter-spacing: 0;
        text-transform: none;
    }
    .project-report .rp-table .rp-amount {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .project-report .rp-table .is-income {
        color: #3d8a07;
    }
    .project-report .rp-table .is-outcome {
        color: #d0312d;
    }
    .project-report .rp-cell-note {
        display: block;
        color: rgba(90, 99, 126, .8);
        font-size: .72rem;
    }
    .project-report .rp-operations {
        min-width: 820px;
    }
    .project-report .rp-tasks {
        min-width: 720px;
    }
    .project-report .rp-comment {
        min-width: 200px;
        max-width: 320px;
    }
    .project-report .rp-amortization {
        display: grid;
        grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
        align-items: start;
        gap: 1.5rem 2.5rem;
    }
    .project-report .rp-footer {
        display: none;
    }

    /* Narrow screens only: printed pages keep the desktop columns */
    @media screen and (max-width: 991px) {
        .project-report .rp-grid-2,
        .project-report .rp-amortization {
            grid-template-columns: minmax(0, 1fr);
        }
        .project-report .rp-subtitle-aligned {
            padding-top: 0;
        }
        .project-report .rp-ods {
            flex-direction: column;
        }
        .project-report .rp-ods-images {
            max-width: none;
        }
    }
    @media screen and (max-width: 575px) {
        .project-report .rp-action-label,
        .project-report .rp-option-cta-label {
            display: none;
        }
        .project-report .rp-picker-card,
        .project-report .rp-cover,
        .project-report .rp-section {
            padding: 1.1rem;
        }
        /* Chips stay on one line and scroll sideways */
        .project-report .rp-filters {
            flex-wrap: nowrap;
            margin-right: -1.1rem;
            margin-left: -1.1rem;
            padding: 0 1.1rem .2rem;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .project-report .rp-filters::-webkit-scrollbar {
            display: none;
        }
        .project-report .rp-option {
            gap: .6rem;
            padding: .75rem .85rem;
        }
        .project-report .rp-option-cta {
            padding: .4rem .5rem;
        }
        .project-report .rp-recent span {
            max-width: 220px;
        }
        .project-report .rp-table-wrap {
            margin: 0 -1.1rem;
            padding: 0 1.1rem;
        }
        .project-report .rp-cover-top {
            flex-direction: column-reverse;
            gap: .75rem;
        }
        .project-report .rp-cover-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            text-align: left;
        }
        .project-report .rp-cover-brand img {
            max-height: 36px;
            margin: 0;
        }
        .project-report .rp-cover h1 {
            font-size: 1.3rem;
        }
        .project-report .rp-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .project-report .rp-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .project-report .rp-kpi {
            padding: .75rem;
        }
        .project-report .rp-kpi .value {
            font-size: 1.05rem;
        }
    }

    /* PRINT: its own page, portrait, whatever other screens set on the unnamed page */
    @page project-report {
        size: portrait;
        margin: 12mm 11mm 13mm;
    }
    @media print {
        /* The app zooms body to 90%; on paper the report sets its own sizes */
        body:has(.project-report) {
            zoom: 1 !important;
        }
        .project-report {
            page: project-report;
            font-size: 9pt;
        }
        .project-report * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .project-report .rp-picker,
        .project-report .rp-state {
            display: none !important;
        }
        .project-report .rp-report {
            display: block;
        }
        .project-report .rp-report > * + * {
            margin-top: 4mm !important;
        }
        .project-report .rp-report .element-box,
        .project-report .rp-kpi {
            padding: 4mm 5mm;
            border: 1px solid #dfe3eb;
            border-radius: 5px;
        }
        .project-report .rp-cover {
            border-top: 3px solid #047bf8;
        }
        .project-report .rp-cover h1 {
            font-size: 16pt;
        }
        .project-report .rp-cover-brand img {
            max-width: 32mm;
            max-height: 14mm;
        }
        .project-report .rp-meta {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 3mm 5mm;
            margin-top: 4mm;
            padding-top: 4mm;
        }
        .project-report .rp-meta dd,
        .project-report .rp-details dd {
            font-size: 8.5pt;
        }
        .project-report .rp-details {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 3mm 5mm;
        }
        .project-report .rp-meta dt,
        .project-report .rp-details dt,
        .project-report .rp-kpi .label,
        .project-report .rp-figure span,
        .project-report .rp-eyebrow {
            font-size: 6.5pt;
        }
        .project-report .rp-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 3mm;
        }
        .project-report .rp-kpi {
            grid-template-columns: minmax(0, 1fr);
            grid-template-areas: "label" "value";
            padding: 3mm;
        }
        .project-report .rp-kpi-icon {
            display: none;
        }
        .project-report .rp-kpi .value {
            font-size: 11pt;
        }
        .project-report .rp-section-title {
            margin-bottom: 3mm;
            font-size: 9.5pt;
        }
        .project-report .rp-subtitle {
            margin: 4mm 0 2mm;
            font-size: 8.5pt;
        }
        .project-report .rp-subtitle-aligned {
            margin-top: 0;
            padding-top: 6.5mm;
        }
        .project-report .rp-richtext,
        .project-report .rp-ods-list {
            font-size: 8.5pt;
            line-height: 1.45;
        }
        .project-report .rp-grid-2 {
            gap: 4mm 8mm;
        }
        .project-report .rp-progress {
            margin-bottom: 3mm;
        }
        .project-report .rp-progress-head,
        .project-report .rp-bars li,
        .project-report .rp-muted {
            font-size: 8pt;
        }
        .project-report .rp-ods-images img {
            width: 20mm;
            height: 20mm;
        }
        .project-report .rp-figures {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 3mm;
        }
        .project-report .rp-figures.is-stacked {
            grid-template-columns: minmax(0, 1fr);
        }
        .project-report .rp-figure {
            padding: 2.5mm 3mm;
        }
        .project-report .rp-figure strong {
            font-size: 10pt;
        }
        .project-report .rp-table-wrap {
            margin: 0;
            padding: 0;
            overflow: visible;
        }
        .project-report .rp-table {
            font-size: 7.5pt;
        }
        .project-report .rp-table th {
            padding: 1.5mm 1.2mm;
            font-size: 6.5pt;
            white-space: normal;
        }
        .project-report .rp-table td {
            padding: 1.2mm 1.2mm;
            line-height: 1.35;
        }
        /* A long word must not push the last columns off the page */
        .project-report .rp-table td:not(.rp-amount):not(.rp-nowrap) {
            overflow-wrap: anywhere;
        }
        .project-report .rp-table tfoot {
            display: table-row-group;
        }
        /* Fixed widths: left to itself the table starves the name columns to feed Observaciones */
        .project-report .rp-operations {
            table-layout: fixed;
        }
        .project-report .rp-operations th:nth-child(1) { width: 10%; }
        .project-report .rp-operations th:nth-child(2) { width: 14%; }
        .project-report .rp-operations th:nth-child(3) { width: 15%; }
        .project-report .rp-operations th:nth-child(4) { width: 10%; }
        .project-report .rp-operations th:nth-child(5) { width: 20%; }
        .project-report .rp-operations th:nth-child(n+6) { width: 10.33%; }
        .project-report .rp-operations .rp-amount {
            font-size: 7pt;
        }
        .project-report .rp-table tfoot th {
            font-size: 7.5pt;
            white-space: nowrap;
        }
        .project-report .rp-cell-note {
            font-size: 6.5pt;
        }
        .project-report .rp-operations,
        .project-report .rp-tasks,
        .project-report .rp-comment {
            min-width: 0;
            max-width: none;
        }
        .project-report .rp-table thead {
            display: table-header-group;
        }
        .project-report .rp-table tr,
        .project-report .rp-avoid,
        .project-report .rp-cover,
        .project-report .rp-kpis,
        .project-report .rp-figure,
        .project-report .rp-progress,
        .project-report .rp-bars li {
            break-inside: avoid;
        }
        .project-report .rp-section-title,
        .project-report .rp-subtitle {
            break-after: avoid;
        }
        .project-report .rp-status {
            font-size: 6.5pt;
        }
        .project-report .rp-footer {
            display: block;
            margin-top: 4mm;
            color: #8a93a8;
            font-size: 7pt;
            text-align: center;
        }
    }
</style>
