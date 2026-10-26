<template>
    <div class="content-box program-list">
        <div class="element-wrapper">
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header pg-header">
                <default-header></default-header>
                <button @click.prevent="openFormProgram('new')" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus mr-2"></i>Nuevo Programa
                </button>
            </h6>

            <!-- PROGRAMS: one row that scrolls on phones, wraps from tablets up -->
            <div class="pg-tabs" role="tablist" aria-label="Programas">
                <template v-if="ready">
                    <button v-for="tab in tabs" :key="tab.key" type="button" role="tab" class="pg-tab"
                            :class="{active: tab.key === selected, 'is-muted': tab.key === NONE}"
                            :aria-selected="tab.key === selected ? 'true' : 'false'"
                            @click="selectTab(tab.key, $event)">
                        <span class="pg-tab-name">{{tab.name}}</span>
                        <span class="pg-tab-count">{{number(tab.count)}}</span>
                    </button>
                </template>
                <span v-else v-for="n in 5" :key="n" class="pg-tab pg-skeleton" aria-hidden="true"></span>
            </div>

            <div class="row">
                <!-- SUMMARY: above the list until there is room beside it -->
                <div class="col-xxl-4 order-xxl-2">
                    <div class="element-box pg-summary" :class="{'pg-loading': !ready}">
                        <div class="pg-summary-head">
                            <div class="pg-summary-title">
                                <span v-if="program && program.pilar" class="badge badge-primary-inverted pg-pilar">{{program.pilar}}</span>
                                <span v-else class="pg-eyebrow">{{selected === NONE ? 'Proyectos' : 'Resumen'}}</span>
                                <h5>{{currentTab.name}}</h5>
                            </div>
                            <div class="pg-summary-actions" v-if="program">
                                <button type="button" class="btn btn-white btn-sm" @click="openFormProgram('edit')" title="Editar" aria-label="Editar programa">
                                    <i class="icon-feather-edit"></i><span class="pg-action-label"> Editar</span>
                                </button>
                                <button type="button" class="btn btn-white btn-sm text-danger" @click="remove(program.id)" title="Eliminar" aria-label="Eliminar programa">
                                    <i class="icon-feather-trash-2"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="program && program.description" class="pg-description" :class="{expanded: showDescription}">
                            <p ref="description">{{program.description}}</p>
                            <button v-if="descriptionClamped || showDescription" type="button" class="btn btn-link btn-sm p-0" @click="showDescription = !showDescription">
                                {{showDescription ? 'Ver menos' : 'Ver más'}}
                            </button>
                        </div>
                        <p v-else-if="selected === NONE" class="pg-description pg-muted">Proyectos activos que no pertenecen a ningún programa vigente.</p>

                        <div class="pg-summary-body">
                            <div class="pg-kpis">
                                <div class="pg-kpi">
                                    <div class="pg-kpi-icon is-warning"><i class="icon-feather-folder"></i></div>
                                    <div class="label">Proyectos</div>
                                    <div class="value">{{number(stats.total)}}</div>
                                </div>
                                <div class="pg-kpi">
                                    <div class="pg-kpi-icon is-primary"><i class="icon-feather-layers"></i></div>
                                    <div class="label">Actividades</div>
                                    <div class="value">{{number(stats.countTask)}}</div>
                                </div>
                                <div class="pg-kpi">
                                    <div class="pg-kpi-icon is-success"><i class="icon-feather-user-check"></i></div>
                                    <div class="label">Benef. directos</div>
                                    <div class="value">{{number(stats.benefDirect)}}</div>
                                </div>
                                <div class="pg-kpi">
                                    <div class="pg-kpi-icon is-info"><i class="icon-feather-users"></i></div>
                                    <div class="label">Benef. indirectos</div>
                                    <div class="value">{{number(stats.benefIndirect)}}</div>
                                </div>
                            </div>

                            <!-- Phones only show the KPIs until asked: the projects stay close to the top -->
                            <button type="button" class="btn btn-link btn-sm pg-details-toggle" :aria-expanded="showDetails ? 'true' : 'false'" @click="showDetails = !showDetails">
                                {{showDetails ? 'Ocultar detalle' : 'Ver avance y estatus'}}
                                <i :class="showDetails ? 'icon-feather-chevron-up' : 'icon-feather-chevron-down'"></i>
                            </button>
                            <div class="pg-sections" :class="{open: showDetails}">
                                <div class="pg-section">
                                    <div class="pg-section-title">Avance de actividades</div>
                                    <template v-if="stats.countTask">
                                        <div class="pg-progress-head">
                                            <strong>{{percent(stats.countTaskDone, stats.countTask)}}%</strong>
                                            <span>{{number(stats.countTaskDone)}} de {{number(stats.countTask)}} concluidas</span>
                                        </div>
                                        <div class="pg-progress-track is-lg">
                                            <div class="pg-progress-fill" :style="{width: percent(stats.countTaskDone, stats.countTask) + '%'}"></div>
                                        </div>
                                    </template>
                                    <p v-else class="pg-muted mb-0">Aún no hay actividades registradas.</p>
                                </div>

                                <div class="pg-section" v-if="stats.statuses.length">
                                    <div class="pg-section-title">Estatus de proyectos</div>
                                    <div class="pg-stack" role="img" :aria-label="stats.statuses.map(s => s.name + ': ' + s.count).join(', ')">
                                        <span v-for="item in stats.statuses" :key="item.name" :class="'is-' + item.tone"
                                              :style="{width: percent(item.count, stats.total, 1) + '%'}"></span>
                                    </div>
                                    <ul class="pg-legend">
                                        <li v-for="item in stats.statuses" :key="item.name">
                                            <button type="button" :class="{active: status === item.name}" @click="status = status === item.name ? '' : item.name">
                                                <span class="pg-dot" :class="'is-' + item.tone"></span>
                                                <span class="pg-legend-name">{{item.label}}</span>
                                                <strong>{{number(item.count)}}</strong>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <div class="pg-section" v-if="program && program.others.length">
                                    <div class="pg-section-title">Proyectos sombrilla</div>
                                    <ul class="pg-umbrella">
                                        <li v-for="(item, index) in program.others" :key="index">{{item}}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PROJECTS -->
                <div class="col-xxl-8 order-xxl-1">
                    <div class="element-box pg-projects" ref="projects">
                        <div class="pg-toolbar">
                            <div class="pg-search">
                                <i class="icon-feather-search"></i>
                                <input v-model.trim="search" type="search" class="form-control form-control-sm" placeholder="Buscar proyecto, responsable o ID" aria-label="Buscar proyectos">
                            </div>
                            <select v-model="status" class="form-control form-control-sm" aria-label="Estatus">
                                <option value="">Todos los estatus</option>
                                <option v-for="item in stats.statuses" :key="item.name" :value="item.name">{{item.label}} ({{item.count}})</option>
                            </select>
                            <select v-model="sort" class="form-control form-control-sm" aria-label="Ordenar">
                                <option value="recent">Más recientes</option>
                                <option value="name">Nombre (A-Z)</option>
                                <option value="progress">Mayor avance</option>
                                <option value="benef">Más beneficiarios</option>
                            </select>
                        </div>

                        <div class="pg-count" v-if="ready">
                            <span v-if="filtered.length">
                                Mostrando <strong>{{number(pageStart + 1)}}–{{number(pageStart + paged.length)}}</strong> de <strong>{{number(filtered.length)}}</strong> proyectos
                            </span>
                            <a v-if="search || status" href="#" @click.prevent="clearFilters">Limpiar filtros</a>
                        </div>

                        <!-- LOADING -->
                        <ul v-if="!ready" class="pg-rows" aria-busy="true">
                            <li v-for="n in 6" :key="n" class="pg-row pg-loading">
                                <div class="pg-row-main"><span class="pg-avatar pg-skeleton"></span><span class="pg-skeleton pg-skeleton-line"></span></div>
                            </li>
                        </ul>

                        <!-- ROWS -->
                        <ul v-else-if="paged.length" class="pg-rows">
                            <li v-for="pjt in paged" :key="pjt.id" class="pg-row">
                                <div class="pg-row-main">
                                    <span class="pg-avatar" :title="pjt.respName || 'Sin responsable'">
                                        <img v-if="pjt.respImg" :src="appDomain + '/' + pjt.respImg" alt="" loading="lazy" @error="pjt.respImg = null">
                                        <template v-else>{{initials(pjt.respName)}}</template>
                                    </span>
                                    <div class="pg-row-text">
                                        <router-link :to="{name: 'ProjectForm', params: {id: pjt.id}}" class="pg-name" :title="pjt.name">{{pjt.name}}</router-link>
                                        <div class="pg-meta">
                                            <span>#{{pjt.id}}</span>
                                            <span v-if="selected === ALL && pjt.program" class="pg-meta-program"><i class="icon-feather-folder"></i>{{pjt.program}}</span>
                                            <span v-if="pjt.respName"><i class="icon-feather-user"></i>{{pjt.respName}}</span>
                                            <span v-if="pjt.date_from || pjt.date_to"><i class="icon-feather-calendar"></i>{{dates(pjt)}}</span>
                                            <span v-if="pjt.benef" :title="'Beneficiarios directos: ' + number(pjt.benefDirect) + ' · indirectos: ' + number(pjt.benefIndirect)">
                                                <i class="icon-feather-users"></i>{{number(pjt.benef)}}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="pg-row-status">
                                    <span class="badge" :class="'badge-' + statusTone(pjt.status) + '-inverted'">{{pjt.status || 'Sin estatus'}}</span>
                                </div>
                                <div class="pg-row-progress">
                                    <div v-if="pjt.countTask" class="pg-progress" :title="pjt.countTaskDone + ' de ' + pjt.countTask + ' actividades concluidas'">
                                        <div class="pg-progress-head"><strong>{{pjt.progress}}%</strong><span>{{pjt.countTaskDone}}/{{pjt.countTask}}</span></div>
                                        <div class="pg-progress-track"><div class="pg-progress-fill" :style="{width: pjt.progress + '%'}"></div></div>
                                    </div>
                                    <a v-else href="#" class="pg-add-task" @click.prevent="$router.push({name: 'TaskForm', params: {project: pjt.id}})">
                                        <i class="icon-feather-plus"></i> Crear actividad
                                    </a>
                                </div>
                                <div class="pg-row-actions">
                                    <button type="button" class="btn btn-primary btn-sm" title="Actividades" @click="$router.push({name: 'TaskList', params: {project: pjt.id}})">
                                        <i class="icon-feather-layers"></i><span class="pg-action-label"> Actividades</span>
                                    </button>
                                    <button type="button" class="btn btn-white btn-sm" title="Reporte" aria-label="Reporte" @click="$router.push({name: 'ProjectReport', params: {projectId: pjt.id}})">
                                        <i class="icon-feather-file-text"></i>
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <!-- EMPTY -->
                        <div v-else class="pg-empty">
                            <i class="icon-feather-folder"></i>
                            <template v-if="search || status">
                                <strong>Sin resultados</strong>
                                <p>No hay proyectos que coincidan con los filtros.</p>
                                <button type="button" class="btn btn-white btn-sm" @click="clearFilters">Limpiar filtros</button>
                            </template>
                            <template v-else>
                                <strong>Sin proyectos</strong>
                                <p>Aún no tienes proyectos creados en este programa.</p>
                                <button type="button" class="btn btn-primary btn-sm" @click="$router.push({name: 'ProjectForm'})"><i class="fa fa-plus mr-2"></i>Nuevo Proyecto</button>
                            </template>
                        </div>

                        <!-- PAGINATION -->
                        <nav v-if="pageCount > 1" class="pg-pagination" aria-label="Paginación">
                            <button type="button" class="btn btn-white btn-sm" :disabled="page === 1" @click="goTo(page - 1)" aria-label="Anterior"><i class="icon-feather-chevron-left"></i></button>
                            <span class="pg-page-info">Página {{page}} de {{pageCount}}</span>
                            <button v-for="(item, index) in pageItems" :key="index" type="button" class="btn btn-sm pg-page"
                                    :class="item === page ? 'btn-primary' : 'btn-white'" :disabled="item === '…'"
                                    :aria-current="item === page ? 'page' : null" @click="goTo(item)">{{item}}</button>
                            <button type="button" class="btn btn-white btn-sm" :disabled="page === pageCount" @click="goTo(page + 1)" aria-label="Siguiente"><i class="icon-feather-chevron-right"></i></button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROGRAM FORM -->
        <div class="modal fade pg-modal" id="formProgram" ref="modal" tabindex="-1" role="dialog" aria-labelledby="formProgramTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                <form class="modal-content" @submit.prevent="formSubmit" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="formProgramTitle">{{form.id ? 'Editar Programa' : 'Nuevo Programa'}}</h5>
                        <button type="button" class="close" aria-label="Cerrar" @click="closeForm()"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="required">Pilar</label>
                            <el-select v-model="form.pilar" filterable name="pilar" v-validate="'required'" :class="{'is-invalid': errors.has('pilar')}" placeholder="Selecciona un pilar">
                                <el-option v-for="item in pilars" :key="item.id" :label="item.name" :value="item.name"></el-option>
                            </el-select>
                            <div class="invalid-feedback" v-show="errors.has('pilar')">{{ errors.first('pilar') }}</div>
                        </div>
                        <div class="form-group">
                            <label class="required">Nombre</label>
                            <input v-model="form.name" name="name" v-validate="'required'" :class="{'is-invalid': errors.has('name')}" class="form-control" placeholder="Nombre del programa" type="text">
                            <div class="invalid-feedback" v-show="errors.has('name')">{{ errors.first('name') }}</div>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" v-model="form.description" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>Proyectos Sombrilla</label>
                            <el-select v-model="form.others" filterable allow-create multiple default-first-option name="others" placeholder="Escribe y presiona Enter para agregar"></el-select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-white" type="button" @click="closeForm()">Cancelar</button>
                        <button class="btn btn-primary" type="submit" :disabled="loading">{{loading ? 'Guardando...' : 'Guardar'}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    import {api} from "../../config/jwt-routes";

    const ALL = 'all';
    const NONE = 'none';
    // Status filter value for projects without one ('' means every status).
    const NO_STATUS = 'none';
    const PAGE_SIZE = 20;
    const STATUS_TONE = { 'En ejecución': 'primary', 'Concluido': 'success' };
    // Search ignores case and accents: "educacion" finds "Educación".
    const normalize = value => String(value == null ? '' : value).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const emptyForm = () => ({ id: null, name: null, pilar: null, description: '', others: [] });

	export default {
        props:['auth','appDomain','siteName'],
        data(){
            return {
                ALL,
                NONE,
                ready: false,
                loading: false,
                form: emptyForm(),
                programs: [],
                projects: [],
                pilars: [],
                selected: ALL,
                search: '',
                status: '',
                sort: 'recent',
                page: 1,
                showDescription: false,
                descriptionClamped: false,
                showDetails: false
            }
        },
        computed: {
            programNames(){
                return new Set(this.programs.map(program => program.name));
            },
            tabs(){
                let counts = _.countBy(this.projects, 'program');
                let programs = this.programs
                    .map(program => ({ key: program.id, name: program.name, count: counts[program.name] || 0 }))
                    .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));
                let orphans = this.projects.filter(pjt => !this.programNames.has(pjt.program)).length;
                return [{ key: ALL, name: 'Todos los proyectos', count: this.projects.length }]
                    .concat(programs, orphans ? [{ key: NONE, name: 'Sin programa', count: orphans }] : []);
            },
            currentTab(){
                return this.tabs.find(tab => tab.key === this.selected) || this.tabs[0];
            },
            program(){
                return this.programs.find(program => program.id === this.selected) || null;
            },
            scoped(){
                if (this.selected === ALL) {
                    return this.projects;
                }
                if (this.selected === NONE) {
                    return this.projects.filter(pjt => !this.programNames.has(pjt.program));
                }
                return this.program ? this.projects.filter(pjt => pjt.program === this.program.name) : [];
            },
            stats(){
                let statuses = _.countBy(this.scoped, pjt => pjt.status || NO_STATUS);
                return {
                    total: this.scoped.length,
                    countTask: this.sum('countTask'),
                    countTaskDone: this.sum('countTaskDone'),
                    benefDirect: this.sum('benefDirect'),
                    benefIndirect: this.sum('benefIndirect'),
                    statuses: Object.keys(statuses)
                        .map(name => name === NO_STATUS
                            ? { name, label: 'Sin estatus', count: statuses[name], tone: 'muted' }
                            : { name, label: name, count: statuses[name], tone: this.statusTone(name) })
                        .sort((a, b) => b.count - a.count)
                };
            },
            filtered(){
                let term = normalize(this.search);
                let list = this.scoped.filter(pjt =>
                    (!this.status || (pjt.status || NO_STATUS) === this.status) &&
                    (!term || pjt.haystack.includes(term))
                );
                switch (this.sort) {
                    case 'name':
                        return list.slice().sort((a, b) => a.name.localeCompare(b.name));
                    case 'progress':
                        // Projects without activities go after 0%.
                        return list.slice().sort((a, b) => (b.countTask ? b.progress : -1) - (a.countTask ? a.progress : -1));
                    case 'benef':
                        return list.slice().sort((a, b) => b.benef - a.benef);
                }
                return list;
            },
            pageCount(){
                return Math.ceil(this.filtered.length / PAGE_SIZE);
            },
            pageStart(){
                return (this.page - 1) * PAGE_SIZE;
            },
            paged(){
                return this.filtered.slice(this.pageStart, this.pageStart + PAGE_SIZE);
            },
            // 1 … 4 5 6 … 49
            pageItems(){
                let items = [];
                for (let n = 1; n <= this.pageCount; n++) {
                    if (n === 1 || n === this.pageCount || Math.abs(n - this.page) <= 1) {
                        items.push(n);
                    } else if (items[items.length - 1] !== '…') {
                        items.push('…');
                    }
                }
                return items;
            }
        },
        watch: {
            selected(){
                this.status = '';
                this.page = 1;
                this.showDescription = false;
            },
            program(){
                this.measureDescription();
            },
            search(){ this.page = 1; },
            status(){ this.page = 1; },
            sort(){ this.page = 1; }
        },
        methods: {
            number(value){
                return (Number(value) || 0).toLocaleString('es-MX');
            },
            percent(part, total, decimals){
                let factor = Math.pow(10, decimals || 0);
                return total ? Math.round(part * 100 * factor / total) / factor : 0;
            },
            sum(key){
                return this.scoped.reduce((total, pjt) => total + pjt[key], 0);
            },
            statusTone(status){
                return STATUS_TONE[status] || 'secondary';
            },
            initials(name){
                return String(name || '?').trim().split(/\s+/).slice(0, 2).map(word => word.charAt(0)).join('').toUpperCase();
            },
            dates(pjt){
                let format = date => date ? moment(date).format('DD/MM/YY') : '…';
                return format(pjt.date_from) + ' – ' + format(pjt.date_to);
            },
            selectTab(key, event){
                this.selected = key;
                // On phones the tabs scroll sideways: keep the chosen one in view.
                event.currentTarget.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            },
            // "Ver más" only when the three-line clamp actually hides text.
            measureDescription(){
                this.$nextTick(() => {
                    let text = this.$refs.description;
                    if (!this.showDescription) {
                        this.descriptionClamped = !!text && text.scrollHeight > text.clientHeight + 1;
                    }
                });
            },
            clearFilters(){
                this.search = '';
                this.status = '';
            },
            goTo(page){
                if (typeof page !== 'number') {
                    return;
                }
                this.page = page;
                let top = this.$refs.projects.getBoundingClientRect().top;
                if (top < 0) {
                    window.scrollBy({ top: top - 80, behavior: 'smooth' });
                }
            },

            closeForm(){
                $(this.$refs.modal).modal('hide');
                this.$nextTick(() => this.errors.clear());
            },

            openFormProgram(action){
                this.form = action === 'edit' && this.program ? {
                    id: this.program.id,
                    pilar: this.program.pilar,
                    name: this.program.name,
                    description: this.program.description || '',
                    others: this.program.others.slice()
                } : emptyForm();
                this.$nextTick(() => this.errors.clear());
                $(this.$refs.modal).modal('show');
            },

            formSubmit() {
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (!result) {
                        return;
                    }
                    vm.loading = true;
                    axios.post(vm.form.id ? api.updateProgram : api.storeProgram, vm.form)
                    .then(async function (response) {
                        if (!response.data.success) {
                            toastr.error(response.data.msg);
                            return;
                        }
                        toastr.success(response.data.msg);
                        await vm.programsData();
                        let saved = vm.programs.find(program => program.id === (vm.form.id || response.data.id));
                        if (saved) {
                            vm.selected = saved.id;
                        }
                        vm.closeForm();
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                    })
                    .finally(function () {
                        vm.loading = false;
                    });
                });
            },

            remove(id){
                swal({
                    title: "Seguro ?",
                    html: "Estas a punto de eliminar este programa, deseas continuar?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                }).then((confirm) => {
                    if (!confirm.value) {
                        swal({title : "Operación Cancelada", type : "error", timer:1200});
                        return;
                    }
                    var vm = this;
                    axios.post(api.deleteParameter, {id:id})
                    .then(async function (response) {
                        if(response.data.success){
                            vm.selected = ALL;
                            await vm.programsData();
                            swal({ html : response.data.msg, type: "success", timer: 1500 });
                        }else{
                            swal.close();
                            toastr.error(response.data.msg);
                        }
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                    });
                });
            },

            // PROGRAMS, PILARS & PROJECTS: everything the page shows comes from this one request
            programsData: async function(){
                var vm = this;
                await axios.get(api.listingProgram)
                .then(function (response) {
                    if(!response.data.success){
                        toastr.error(response.data.msg);
                        return;
                    }
                    vm.programs = response.data.programs;
                    vm.pilars = response.data.pilars;
                    // Derived once here so filtering and sorting stay cheap on every keystroke.
                    vm.projects = response.data.projects.map(pjt => {
                        let benefDirect = Number(pjt.beneficiary_direct) || 0;
                        let benefIndirect = Number(pjt.beneficiary_indirect) || 0;
                        return Object.assign(pjt, {
                            benefDirect,
                            benefIndirect,
                            benef: benefDirect + benefIndirect,
                            progress: pjt.countTask ? Math.round(pjt.countTaskDone * 100 / pjt.countTask) : 0,
                            haystack: normalize([pjt.id, pjt.name, pjt.respName, pjt.program].join(' '))
                        });
                    });
                    if (vm.selected !== ALL && !vm.tabs.some(tab => tab.key === vm.selected)) {
                        vm.selected = ALL;
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                });
            }
        },

        mounted: async function(){
            this.onResize = _.debounce(this.measureDescription, 150);
            window.addEventListener('resize', this.onResize);
            await this.programsData();
            this.ready = true;
        },
        beforeDestroy(){
            window.removeEventListener('resize', this.onResize);
            $(this.$refs.modal).modal('hide');
        }
    }
</script>

<style>
    .program-list .el-select {
        width: 100%;
    }
    .program-list .pg-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    /* PROGRAM TABS */
    .program-list .pg-tabs {
        display: flex;
        gap: .5rem;
        margin: 0 -1rem 1.25rem;
        padding: 0 1rem .25rem;
        overflow-x: auto;
        scroll-snap-type: x proximity;
        scroll-padding: 0 1rem;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .program-list .pg-tabs::-webkit-scrollbar {
        display: none;
    }
    .program-list .pg-tab {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        max-width: 280px;
        padding: .45rem .5rem .45rem .9rem;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 999px;
        background-color: #fff;
        color: #3e4b5b;
        font-size: .82rem;
        font-weight: 500;
        line-height: 1.2;
        scroll-snap-align: start;
        cursor: pointer;
        transition: border-color .15s, background-color .15s, color .15s, box-shadow .15s;
    }
    .program-list .pg-tab:hover {
        border-color: rgba(4, 123, 248, .4);
        color: #047bf8;
    }
    .program-list .pg-tab:focus {
        outline: none;
    }
    .program-list .pg-tab:focus-visible {
        box-shadow: 0 0 0 3px rgba(4, 123, 248, .25);
    }
    .program-list .pg-tab.active {
        border-color: #047bf8;
        background-color: #047bf8;
        color: #fff;
        box-shadow: 0 4px 12px rgba(4, 123, 248, .25);
    }
    .program-list .pg-tab.is-muted:not(.active) {
        border-style: dashed;
    }
    .program-list .pg-tab-name {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .program-list .pg-tab-count {
        flex: 0 0 auto;
        min-width: 1.6rem;
        padding: .15rem .45rem;
        border-radius: 999px;
        background-color: #f0f2f7;
        color: #5a637e;
        font-size: .72rem;
        font-weight: 700;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .program-list .pg-tab.active .pg-tab-count {
        background-color: rgba(255, 255, 255, .22);
        color: #fff;
    }

    /* SKELETONS */
    .program-list .pg-skeleton {
        display: inline-block;
        border-radius: 6px;
        background: linear-gradient(90deg, #eef0f5 25%, #f6f7fa 50%, #eef0f5 75%);
        background-size: 200% 100%;
        animation: pg-shimmer 1.2s ease-in-out infinite;
    }
    .program-list .pg-tab.pg-skeleton {
        width: 140px;
        height: 34px;
        border: 0;
        border-radius: 999px;
    }
    .program-list .pg-skeleton-line {
        flex: 1;
        max-width: 320px;
        height: 14px;
    }
    .program-list .pg-summary.pg-loading .value {
        color: transparent;
    }
    @keyframes pg-shimmer {
        from { background-position: 100% 0; }
        to { background-position: -100% 0; }
    }

    /* SUMMARY */
    .program-list .pg-summary {
        container-type: inline-size;
        padding: 1.25rem;
    }
    .program-list .pg-summary-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }
    .program-list .pg-summary-title {
        min-width: 0;
    }
    .program-list .pg-summary-title h5 {
        margin: .35rem 0 0;
        font-size: 1.15rem;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }
    .program-list .pg-eyebrow {
        color: rgba(90, 99, 126, .75);
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .6px;
        text-transform: uppercase;
    }
    .program-list .pg-pilar {
        max-width: 100%;
        white-space: normal;
        text-align: left;
    }
    .program-list .pg-summary-actions {
        display: inline-flex;
        flex: 0 0 auto;
        gap: .3rem;
    }
    .program-list .pg-summary-actions .btn,
    .program-list .pg-row-actions .btn {
        padding: .3rem .5rem;
        line-height: 1;
    }
    .program-list .pg-summary-actions .btn i,
    .program-list .pg-row-actions .btn i {
        font-size: 15px;
    }
    .program-list .pg-description {
        margin-top: .75rem;
        font-size: .85rem;
        color: #5a637e;
    }
    .program-list .pg-description p {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        margin: 0;
        overflow: hidden;
        white-space: pre-line;
    }
    .program-list .pg-description.expanded p {
        display: block;
    }
    .program-list .pg-muted {
        color: rgba(90, 99, 126, .75);
        font-size: .82rem;
    }
    .program-list .pg-summary-body {
        display: grid;
        gap: 1.25rem;
        margin-top: 1.25rem;
    }
    .program-list .pg-kpis {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .6rem;
    }
    .program-list .pg-kpi {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        grid-template-areas: "icon label" "icon value";
        align-items: center;
        column-gap: .6rem;
        padding: .65rem .75rem;
        border-radius: 8px;
        background-color: #f7f8fb;
    }
    .program-list .pg-kpi-icon {
        grid-area: icon;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        font-size: 15px;
    }
    .program-list .pg-kpi-icon.is-success { color: #5eb314; background-color: rgba(94, 179, 20, .12); }
    .program-list .pg-kpi-icon.is-primary { color: #047bf8; background-color: rgba(4, 123, 248, .1); }
    .program-list .pg-kpi-icon.is-warning { color: #d39e00; background-color: rgba(251, 228, 160, .45); }
    .program-list .pg-kpi-icon.is-info    { color: #7c3aed; background-color: rgba(124, 58, 237, .1); }
    .program-list .pg-kpi .label {
        grid-area: label;
        align-self: end;
        color: rgba(90, 99, 126, .8);
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .4px;
        text-transform: uppercase;
        line-height: 1.3;
    }
    .program-list .pg-kpi .value {
        grid-area: value;
        align-self: start;
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1.3;
        color: #334152;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .program-list .pg-sections {
        display: grid;
        gap: 1.25rem;
    }
    .program-list .pg-section-title {
        margin-bottom: .6rem;
        color: #334152;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
    }
    /* Beside the list the summary is narrow; full width it spreads into columns */
    @container (min-width: 640px) {
        .program-list .pg-kpis {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        /* four across leaves no room for the icon beside the figure */
        .program-list .pg-kpi {
            grid-template-columns: minmax(0, 1fr);
            grid-template-areas: "icon" "label" "value";
            row-gap: .4rem;
            padding: .85rem;
        }
        .program-list .pg-sections {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem 2rem;
        }
    }

    /* PROGRESS & STATUS */
    .program-list .pg-progress-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: .5rem;
        margin-bottom: 4px;
        font-size: .78rem;
    }
    .program-list .pg-progress-head span {
        color: rgba(90, 99, 126, .75);
    }
    .program-list .pg-progress-track {
        height: 6px;
        border-radius: 3px;
        background-color: #e9ecef;
        overflow: hidden;
    }
    .program-list .pg-progress-track.is-lg {
        height: 10px;
        border-radius: 5px;
    }
    .program-list .pg-progress-fill {
        height: 100%;
        border-radius: inherit;
        background-color: #047bf8;
        transition: width .3s ease;
    }
    .program-list .pg-stack {
        display: flex;
        gap: 2px;
        height: 10px;
        border-radius: 5px;
        overflow: hidden;
        background-color: #e9ecef;
    }
    .program-list .pg-stack span {
        min-width: 4px;
        transition: width .3s ease;
    }
    .program-list .is-primary.pg-dot,   .program-list .pg-stack .is-primary   { background-color: #047bf8; }
    .program-list .is-success.pg-dot,   .program-list .pg-stack .is-success   { background-color: #5eb314; }
    .program-list .is-secondary.pg-dot, .program-list .pg-stack .is-secondary { background-color: #f0a03c; }
    .program-list .is-muted.pg-dot,     .program-list .pg-stack .is-muted     { background-color: #b9c0cf; }
    .program-list .pg-legend {
        margin: .6rem 0 0;
        padding: 0;
        list-style: none;
    }
    .program-list .pg-legend button {
        display: flex;
        align-items: center;
        gap: .5rem;
        width: 100%;
        padding: .3rem .4rem;
        border: 0;
        border-radius: 6px;
        background: none;
        color: #3e4b5b;
        font-size: .82rem;
        text-align: left;
        cursor: pointer;
    }
    .program-list .pg-legend button:hover,
    .program-list .pg-legend button.active {
        background-color: #f0f4fb;
    }
    .program-list .pg-legend-name {
        flex: 1;
    }
    .program-list .pg-dot {
        flex: 0 0 auto;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .program-list .pg-umbrella {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .program-list .pg-umbrella li {
        position: relative;
        padding: .35rem 0 .35rem 1.1rem;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        font-size: .82rem;
        line-height: 1.35;
    }
    .program-list .pg-umbrella li:last-child {
        border-bottom: 0;
    }
    .program-list .pg-umbrella li::before {
        content: '';
        position: absolute;
        top: .8rem;
        left: .2rem;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #047bf8;
        opacity: .5;
    }

    /* PROJECTS */
    .program-list .pg-projects {
        container-type: inline-size;
        padding: 1rem;
    }
    .program-list .pg-toolbar {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .5rem;
    }
    .program-list .pg-search {
        position: relative;
        grid-column: 1 / -1;
    }
    .program-list .pg-search i {
        position: absolute;
        top: 50%;
        left: .7rem;
        transform: translateY(-50%);
        color: rgba(90, 99, 126, .6);
        pointer-events: none;
    }
    .program-list .pg-search .form-control {
        padding-left: 2.1rem;
    }
    .program-list .pg-count {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: .25rem 1rem;
        margin: .85rem 0 .25rem;
        color: rgba(90, 99, 126, .85);
        font-size: .8rem;
    }
    .program-list .pg-rows {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .program-list .pg-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        grid-template-areas:
            "main main main"
            "status progress actions";
        align-items: center;
        gap: .6rem 1rem;
        padding: .9rem .25rem;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
    }
    .program-list .pg-row:last-child {
        border-bottom: 0;
    }
    .program-list .pg-row-main     { grid-area: main; }
    .program-list .pg-row-status   { grid-area: status; }
    .program-list .pg-row-progress { grid-area: progress; }
    .program-list .pg-row-actions  { grid-area: actions; }
    .program-list .pg-row-main {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }
    .program-list .pg-avatar {
        flex: 0 0 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: rgba(4, 123, 248, .1);
        color: #047bf8;
        font-size: .75rem;
        font-weight: 700;
        overflow: hidden;
    }
    .program-list .pg-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .program-list .pg-row-text {
        min-width: 0;
    }
    .program-list .pg-name {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        color: #334152;
        font-weight: 600;
        line-height: 1.3;
    }
    .program-list .pg-name:hover {
        color: #047bf8;
        text-decoration: none;
    }
    .program-list .pg-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0 .75rem;
        margin-top: 2px;
        color: rgba(90, 99, 126, .75);
        font-size: .75rem;
    }
    .program-list .pg-meta span {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        white-space: nowrap;
    }
    .program-list .pg-meta i {
        font-size: 12px;
        opacity: .8;
    }
    .program-list .pg-meta .pg-meta-program {
        color: #047bf8;
        white-space: normal;
    }
    .program-list .pg-row-status .badge {
        white-space: nowrap;
    }
    .program-list .pg-add-task {
        font-size: .8rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .program-list .pg-row-actions {
        display: inline-flex;
        justify-content: flex-end;
        gap: .3rem;
    }
    .program-list .pg-action-label {
        display: none;
    }
    .program-list .pg-row.pg-loading {
        display: block;
    }
    .program-list .pg-avatar.pg-skeleton {
        background-color: transparent;
    }
    /* From ~tablet widths each project fits on one line */
    @container (min-width: 620px) {
        .program-list .pg-toolbar {
            grid-template-columns: minmax(0, 1fr) 180px 170px;
        }
        .program-list .pg-search {
            grid-column: auto;
        }
        .program-list .pg-row {
            grid-template-columns: minmax(0, 1fr) 110px 150px auto;
            grid-template-areas: "main status progress actions";
            padding: .8rem .5rem;
        }
        .program-list .pg-row:hover {
            background-color: #fafbfd;
        }
    }
    @container (min-width: 860px) {
        .program-list .pg-row {
            grid-template-columns: minmax(0, 1fr) 120px 170px auto;
        }
        .program-list .pg-row-actions .pg-action-label {
            display: inline;
        }
    }

    /* EMPTY & PAGINATION */
    .program-list .pg-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 2.5rem 1rem;
        text-align: center;
    }
    .program-list .pg-empty > i {
        margin-bottom: .75rem;
        font-size: 32px;
        color: rgba(90, 99, 126, .4);
    }
    .program-list .pg-empty p {
        margin: .25rem 0 1rem;
        color: rgba(90, 99, 126, .8);
        font-size: .85rem;
    }
    .program-list .pg-pagination {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: .3rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, .06);
    }
    .program-list .pg-pagination .btn {
        min-width: 34px;
        padding: .3rem .5rem;
        line-height: 1.2;
    }
    .program-list .pg-pagination .btn:disabled {
        opacity: .5;
    }
    .program-list .pg-page[disabled] {
        border-color: transparent;
        background: none;
    }
    .program-list .pg-page-info {
        padding: 0 .5rem;
        color: rgba(90, 99, 126, .85);
        font-size: .8rem;
    }
    .program-list .pg-page {
        display: none;
    }
    @container (min-width: 520px) {
        .program-list .pg-page {
            display: inline-block;
        }
        .program-list .pg-page-info {
            display: none;
        }
    }

    /* MODAL */
    .program-list .pg-modal .modal-header {
        align-items: center;
    }
    .program-list .pg-modal .modal-footer {
        gap: .5rem;
    }
    .program-list .pg-modal .modal-footer > * {
        margin: 0;
    }

    .program-list .pg-details-toggle {
        display: none;
    }
    @media (max-width: 767px) {
        .program-list .pg-details-toggle {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            justify-self: start;
            margin-top: -.5rem;
            padding: 0;
            font-weight: 600;
        }
        .program-list .pg-sections:not(.open) {
            display: none;
        }
    }
    @media (min-width: 768px) {
        .program-list .pg-tabs {
            flex-wrap: wrap;
            margin: 0 0 1.25rem;
            padding: 0;
            overflow: visible;
        }
        .program-list .pg-summary-actions .pg-action-label {
            display: inline;
        }
    }
    @media (min-width: 1450px) {
        .program-list .pg-summary {
            position: sticky;
            top: 1rem;
        }
    }
</style>
