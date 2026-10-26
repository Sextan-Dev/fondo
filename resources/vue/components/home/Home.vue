<template>

	<div class="content-i dashboard">
		<!-- MAIN CONTENT -->
		<div class="content-box">
			<div class="element-wrapper dash-intro">
				<!-- FILTERS -->
				<div class="element-actions dash-filters">
					<select v-model="filterUser" @change="updateDashboard" class="form-control form-control-sm rounded">
						<option value="TODO" v-translate>
							Todo
						</option>
						<option :value="auth.id" v-translate>
							Mis Actividades
						</option>
					</select>
					<select v-model="filterTime" @change="updateDashboard" class="form-control form-control-sm rounded">
						<option value="monthCurrent" v-translate>
							Este Mes
						</option>
						<option value="monthPrevious" v-translate>
							Mes Anterior
						</option>
						<option value="yearCurrent" v-translate>
							Este Año
						</option>
						<option value="yearPrevious" v-translate>
							Año Anterior
						</option>
					</select>
				</div>
				<h6 class="element-header">
					<span class="default-title">
						<i class="icon-feather-activity"></i>
						<translate>Dashboard</translate>
					</span>
				</h6>
				<!-- KPIs -->
				<div class="row dash-kpis">
					<div class="col-6 col-xl-3">
						<div class="element-box el-tablo dash-kpi">
							<div class="dash-kpi-icon is-success"><i class="icon-feather-trending-up"></i></div>
							<div class="dash-kpi-body">
								<div class="label" v-translate>Ingreso Periodo</div>
								<div class="value text-success">{{money(sumIncomes)}}</div>
							</div>
						</div>
					</div>
					<div class="col-6 col-xl-3">
						<div class="element-box el-tablo dash-kpi">
							<div class="dash-kpi-icon is-danger"><i class="icon-feather-trending-down"></i></div>
							<div class="dash-kpi-body">
								<div class="label" v-translate>Egreso Periodo</div>
								<div class="value text-danger">{{money(sumOutcomes)}}</div>
							</div>
						</div>
					</div>
					<div class="col-6 col-xl-3">
						<div class="element-box el-tablo dash-kpi">
							<div class="dash-kpi-icon is-primary"><i class="icon-feather-dollar-sign"></i></div>
							<div class="dash-kpi-body">
								<div class="label" v-translate>Balance Periodo</div>
								<div class="value" :class="balance < 0 ? 'text-danger' : 'text-primary'">{{money(balance)}}</div>
							</div>
						</div>
					</div>
					<div class="col-6 col-xl-3">
						<div class="element-box el-tablo dash-kpi">
							<div class="dash-kpi-icon is-warning"><i class="icon-feather-folder"></i></div>
							<div class="dash-kpi-body">
								<div class="label" v-translate>Proyectos Activos</div>
								<div class="value">{{countProjects.toLocaleString('en')}}</div>
								<div class="dash-kpi-foot"><strong>{{countTask}}</strong> <translate>Actividades pendientes</translate></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- PENDING TASK -->
			<div class="element-wrapper">
				<h6 class="element-header" v-translate>Actividades Pendientes</h6>
				<!-- clearfix: the floated pagination otherwise narrows the next section on phones -->
				<div class="element-box-tp clearfix">
					<table class="table table-padded dash-table" ref="tasksTable" width="100%"></table>
				</div>
			</div>
			<!-- PROJECTS TABLE & CHART-->
			<div class="row">
				<div class="col-xxl-8">
					<!-- PROJECTS TABLE -->
					<div class="element-wrapper">
						<h6 class="element-header">
							<translate>Seguimiento Proyectos</translate>
							<span class="badge badge-primary-inverted ml-1">{{countProjects.toLocaleString('en')}}</span>
						</h6>
						<div class="element-box-tp clearfix">
							<table class="table table-padded dash-table" ref="projectsTable" width="100%"></table>
						</div>
					</div>
				</div>
				<!-- CONTACTS DONUT CHART -->
                <div class="col-xxl-4">
					<div class="element-wrapper">
						<h6 class="element-header" v-translate>Beneficiarios Periodo</h6>
						<div class="element-box dash-benef">
							<div class="dash-benef-chart">
								<canvas ref="donutChart"></canvas>
								<div class="dash-benef-total">
									<strong>{{sumBenef.toLocaleString('en')}}</strong>
									<span v-translate>Beneficiarios</span>
								</div>
							</div>
							<ul class="dash-benef-legend">
								<li>
									<span class="dash-benef-dot" :style="{backgroundColor: levelColors[0]}"></span>
									<span class="dash-benef-name" v-translate>1er Nivel</span>
									<span class="dash-benef-num">{{beneficiaries[0].toLocaleString('en')}}</span>
									<span class="dash-benef-pct">{{percent(beneficiaries[0])}}%</span>
								</li>
								<li>
									<span class="dash-benef-dot" :style="{backgroundColor: levelColors[1]}"></span>
									<span class="dash-benef-name" v-translate>2do Nivel</span>
									<span class="dash-benef-num">{{beneficiaries[1].toLocaleString('en')}}</span>
									<span class="dash-benef-pct">{{percent(beneficiaries[1])}}%</span>
								</li>
								<li>
									<span class="dash-benef-dot" :style="{backgroundColor: levelColors[2]}"></span>
									<span class="dash-benef-name" v-translate>3er Nivel</span>
									<span class="dash-benef-num">{{beneficiaries[2].toLocaleString('en')}}</span>
									<span class="dash-benef-pct">{{percent(beneficiaries[2])}}%</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- SIDEBAR -->
		<div class="content-panel">
			<div class="content-panel-close">
				<i class="os-icon os-icon-close"></i>
			</div>

			<!-- Shortcuts -->
			<div class="element-wrapper">
				<h6 class="element-header" v-translate>
					Acceso Rápido
				</h6>
				<div class="element-box-tp">
					<div class="el-buttons-list full-width">
						<a @click.prevent="$router.push({name:'TaskForm'})" class="btn btn-primary btn-sm" href="#">
							<i class="os-icon os-icon-ui-22"></i>&nbsp;
							<translate>Nueva actividad</translate>
						</a>

						<a @click.prevent="$router.push({name:'TaskList'})" class=" btn btn-white btn-sm" href="#">
							<i class="icon-feather-layers"></i>&nbsp;
							<span v-translate>Dashboard Actividades</span>
						</a>
						<a @click.prevent="$router.push({name:'ProjectList'})" class=" btn btn-white btn-sm" href="#">
							<i class="icon-feather-menu"></i>&nbsp;
							<span v-translate>Listado de Proyectos</span>
						</a>
					</div>
				</div>
			</div>
			<!-- Events -->
			<div class="element-wrapper" v-if="events.length>0">
				<h6 class="element-header" v-translate>Próximos Eventos</h6>
				<div class="support-index">
					<div class="support-tickets" style="flex: auto;">
						<div class="support-ticket" v-show=" index > 2 ? showAllEvents : true" :key="index" v-for="( event , index) in events">
							<div class="st-body">
								<div class="ticket-content">
									<a href="#" class="" @click.prevent="$router.push({name:'Calendar',params: {id: event.id } })" >
										<h6 class="ticket-title">
											<i class="icon-feather-calendar bigger mr-1"></i>
											<span>{{event.name}}</span>
										</h6>
										<div class="ticket-description bolder">{{new Date(event.start).toLocaleDateString("es-MX",dateOptions)}}</div>
										<div class="ticket-description">{{event.description}}</div>
									</a>
									<span v-if="event.location" class="ticket-description"><a target="_blank" v-bind:href="'https://www.google.com/maps/dir/?api=1&query='+event.location">{{event.location}}</a></span><br>
								</div>
							</div>
							<div class="st-foot">
								<span class="btn btn-warning btn-sm pull-right tip bolder"  title="Descargar Evento" @click="ics(event.name,event.description,event.location,event.start,event.end)"><i class="icon-feather-download"></i></span>
								<span class="label" v-translate>Autor:</span>
								<span class="value with-avatar">
									<img style="max-width: 25px" :src="appDomain +'/'+ event.user.img" onerror="javascript:this.src='/images/user-grey.png'" />
									<span>{{event.user.name.split(' ')[0]}}</span>
								</span><br>
							</div>
						</div>
						<div class="load-more-tickets"><a href="#" @click.prevent="showAllEvents = true" v-if="events.length>3 && showAllEvents==false"> <span>Ver más eventos...</span></a></div>
					</div>
				</div>

			</div>
			<!-- Timeline -->
			<div class="element-wrapper" v-if="reminders.length>0">
				<h6 class="element-header" v-translate>
					Próximas etapas
				</h6>
				<div class="activity-boxes-w">
					<div class="activity-box-w" :key="index" v-for="( reminder , index) in reminders">
						<div class="activity-time">{{reminder.date.substring(0,10)}}</div>
						<div class="activity-box">
							<div class="activity-info">
								<div class="activity-role">{{reminder.task_name}}</div>
								<strong class="activity-title">{{reminder.value}}</strong>
								<div class="value badge badge-warning"><a class="option clickable" @click.prevent="$router.push({name:'TaskForm',params: {id: reminder.task_id } })" > {{reminder.task_name}} </a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
        </div>
	</div>

</template>

<script>

    import {api} from "../../config/jwt-routes";
    import {ics, escapeHtml} from "../../core/helpers.js";

	// Shared by the donut and its legend.
	const LEVEL_COLORS = ["#4ecc48", "#2463BF", "#f37070"];
	const PROJECT_STATUS = { 'En ejecución': 'primary', 'Concluido': 'success' };

	export default {
		props:['auth','appDomain','siteName'],
		data() {
			return {
				filterTime:'monthCurrent',
				filterUser:'TODO',
				dateOptions: { day: 'numeric', month: 'short', hour:'numeric', minute:'2-digit' },
				listProjects : [],
				events:[],
				countProjects : 0,
				countTask : 0,
				sumIncomes:0,
				sumOutcomes:0,
				showAllEvents:false,
				beneficiaries:[0, 0, 0],
				levelColors: LEVEL_COLORS,
				reminders:[],
				status:{
					'pending' :{name : 'Pendiente', class: 'warning'},
					'approved':{name : 'Aprobada',  class: 'info'},
					'working':{name : 'En curso',  class: 'primary'},
					'done':{name : 'Finalizada',  class: 'success'},
					'canceled': {name : 'Cancelada', class: 'danger'}
				}
			}
		},
		computed:{
			sumBenef(){
				return this.beneficiaries.reduce((total, value) => total + value, 0)
			},
			balance(){
				return this.sumIncomes - this.sumOutcomes
			}
		},
		methods:{
			// CHANGE FILTER
			updateDashboard:async function(){
				let vm = this
				vm.tasksTable.ajax.reload();
				await Promise.all([vm.dataContent(), vm.dataDashboard()])
				vm.refreshProjects()
				vm.renderChart()
			},
			//DOWNLOAD ICS
			ics(name,description,location,start,end) {
				var cal = ics();
				cal.addEvent(name, description, location, start, end);
				cal.download(name);
			},
			money(value){
				return (value < 0 ? '-$' : '$') + Math.abs(value).toLocaleString('en')
			},
			percent(value){
				return this.sumBenef ? Math.round(value * 100 / this.sumBenef) : 0
			},
			// Show Project
			dataDashboard:async function(){
                let vm = this;
				await axios.post(api.reportDashboard, {filterTime : vm.filterTime, filterUser : vm.filterUser})
				.then(function (response) {
					// CONTACTS
					let levels = [0, 0, 0]
					response.data.contacts.forEach(function(value, key){
						levels[0] += parseInt(value.sumBenef1) || 0
						levels[1] += parseInt(value.sumBenef2) || 0
						levels[2] += parseInt(value.sumBenef3) || 0
					});
					vm.beneficiaries = levels

					// PROJECTS
					vm.countProjects =  response.data.projects.length
					vm.listProjects = response.data.projects

					vm.countTask = 0
					vm.sumIncomes = 0
					vm.sumOutcomes = 0

					response.data.projects.forEach(function(value, key){
						vm.countTask = parseInt(vm.countTask ) + parseInt( value.countTaskPending)
					});
					response.data.operations.forEach(function(value, key){
						vm.sumIncomes = parseInt(vm.sumIncomes ) + parseInt( value.sumIncome)
						vm.sumOutcomes = parseInt(vm.sumOutcomes ) + parseInt( value.sumOutcome)
					});
				})
				.catch(function (error) {
					toastr.error(error);
					console.log(error);
				})
			},

			// Show Reminders
			dataContent:async function(){
                let vm = this;
				await axios.post(api.selectTaskReminder, {filterTime  : vm.filterTime, filterUser : vm.filterUser})
				.then(function (response) {
					vm.reminders = response.data.data
				})
				.catch(function (error) {
					toastr.error(error);
					console.log(error);
				})
			},
			// DONUT CHART: one instance, updated on every filter change
			renderChart(){
				let empty = this.sumBenef === 0
				let colors = empty ? ["#e9ecef"] : LEVEL_COLORS
				let dataset = {
					data: empty ? [1] : this.beneficiaries,
					backgroundColor: colors,
					hoverBackgroundColor: colors,
					borderWidth: 0
				}
				if(this.chart){
					this.chart.data.datasets = [dataset]
					this.chart.options.tooltips.enabled = !empty
					this.chart.update()
					return
				}
				this.chart = new Chart(this.$refs.donutChart, {
					type: 'doughnut',
					data: {
						labels: ["1er Nivel", "2do Nivel", "3er Nivel"],
						datasets: [dataset]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						legend: {
							display: false
						},
						tooltips: {
							enabled: !empty
						},
						animation:{
							animateScale:true
						},
						cutoutPercentage: 80
					}
				});
			},
			// EVENTS USER AND PUBLICS
			eventsList: async function(){
                var vm = this;
                vm.loading = true;
                await axios.post(api.indexEvent, {home:true})
                .then(function (response) {
					vm.events = response.data.data
                })
                .catch(function (error) {
                    //vm.events = [];
                    console.log(error);
                    vm.loading = false;
                });
            },
			// TABLE CELLS
			noteCell(text, date, type){
				if(type !== 'display'){
					return text || ''
				}
				if(!text){
					return '<span class="text-muted">—</span>'
				}
				return `<div class="dash-note">
							<span class="dash-note-date">${escapeHtml(date)}</span>
							<span class="dash-note-text" title="${escapeHtml(text)}">${escapeHtml(text)}</span>
						</div>`
			},
			projectCell(row){
				let img = row.img ? this.appDomain + '/' + row.img : '/images/project.png'
				return `<div class="dash-project">
							<img class="dash-project-img" src="${escapeHtml(img)}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/images/project.png'">
							<div class="dash-project-text">
								<a href="#" class="dash-link" data-action="project-tasks" data-id="${escapeHtml(row.id)}">${escapeHtml(row.name)}</a>
								${row.program ? `<span class="dash-muted">${escapeHtml(row.program)}</span>` : ''}
							</div>
						</div>`
			},
			progressCell(row, type){
				let total = parseInt(row.countTask) || 0
				let done = parseInt(row.countTaskDone) || 0
				let progress = total ? Math.round(done * 100 / total) : 0
				if(type !== 'display'){
					// Projects without activities sort after 0%.
					return total ? progress : -1
				}
				if(!total){
					return '<span class="dash-muted">Sin actividades</span>'
				}
				return `<div class="dash-progress" title="${done} / ${total}">
							<div class="dash-progress-head"><strong>${progress}%</strong><span>${done}/${total}</span></div>
							<div class="dash-progress-track"><div class="dash-progress-fill" style="width: ${progress}%"></div></div>
						</div>`
			},
			personCell(name, img){
				if(!name){
					return '<span class="text-muted">—</span>'
				}
				let src = img ? this.appDomain + '/' + img : '/images/user-grey.png'
				return `<span class="dash-person">
							<img src="${escapeHtml(src)}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/images/user-grey.png'">
							<span>${escapeHtml(name)}</span>
						</span>`
			},
			projectActions(id){
				return '<div class="dash-actions">' + [
					['project-tasks', 'icon-feather-layers', 'Taskboard'],
					['report', 'icon-feather-file-text', 'Reporte'],
					['edit', 'icon-feather-edit', 'Editar']
				].map(([action, icon, title]) =>
					`<a href="#" class="btn btn-white btn-sm tip" title="${title}" aria-label="${title}" data-action="${action}" data-id="${escapeHtml(id)}"><i class="${icon}"></i></a>`
				).join('') + '</div>'
			},
			// OPTIONS EVENTS IN BOTH TABLES
			onTableAction(event){
				event.preventDefault()
				let target = $(event.currentTarget)
				let id = target.attr('data-id')
				let route = {
					'task': {name:'TaskForm', params: {id: id}},
					'project-tasks': {name:'TaskList', params: {project: id}},
					'report': {name:'ProjectReport', params: {projectId: id}},
					'edit': {name:'ProjectForm', params: {id: id}}
				}[target.attr('data-action')]
				if(route){
					$(event.currentTarget).tooltip('hide')
					this.$router.push(route)
				}
			},
			initTasksTable(){
				let vm = this
				vm.tasksTable = $(vm.$refs.tasksTable).DataTable({
					columns: [
						{
							data: 'name',
							title: '<translate>Actividad</translate>',
							responsivePriority: 1,
							render: function(data, type, row) {
								return type !== 'display' ? data :
									`<a href="#" class="dash-link" data-action="task" data-id="${escapeHtml(row.id)}">${escapeHtml(data)}</a>`
							}
						},
						{
							data: 'user',
							title: '<translate>Responsable</translate>',
							className: 'smaller',
							responsivePriority: 5,
							render: $.fn.dataTable.render.text()
						},
						{
							data: 'project',
							title: '<translate>Proyecto</translate>',
							responsivePriority: 3,
							render: function(data, type, row) {
								return type !== 'display' ? data :
									`<a href="#" class="badge badge-primary-inverted dash-chip" data-action="project-tasks" data-id="${escapeHtml(row.project_id)}">${escapeHtml(data)}</a>`
							}
						},
						{
							data: 'lastReminder',
							title: '<translate>Próxima etapa</translate>',
							responsivePriority: 6,
							render: function(data, type, row) {
								return vm.noteCell(data, row.lastReminderDate, type)
							}
						},
						{
							data: 'lastComment',
							title: '<translate>Último comentario</translate>',
							responsivePriority: 7,
							render: function(data, type, row) {
								return vm.noteCell(data, row.lastCommentDate, type)
							}
						},
						{
							data: 'status',
							title: '<translate>Estatus</translate>',
							responsivePriority: 2,
							render: function(data, type) {
								let status = vm.status[data] || {name: data, class: 'default'}
								return type !== 'display' ? status.name :
									`<span class="badge badge-${status.class}-inverted">${escapeHtml(status.name)}</span>`
							}
						}
					],
					ajax: {
						url: api.selectTask,
						type: "POST",
						data: function(){ return { datable: true,filterTime  : vm.filterTime, filterUser : vm.filterUser}; }
					},
					order: [[ 0, 'asc' ]],
				});
				$(vm.$refs.tasksTable).on('click', '[data-action]', vm.onTableAction)
			},
			initProjectsTable(){
				let vm = this
				vm.projectsTable = $(vm.$refs.projectsTable).DataTable({
					data: vm.listProjects,
					// The global defaults define ajax: without this the table requests the page itself.
					ajax: null,
					columns: [
						{
							data: 'name',
							title: '<translate>Proyecto</translate>',
							responsivePriority: 1,
							render: function(data, type, row) {
								if(type === 'display'){
									return vm.projectCell(row)
								}
								return type === 'filter' ? data + ' ' + (row.program || '') : data
							}
						},
						{
							data: null,
							title: '<translate>Avance</translate>',
							className: 'dash-progress-cell',
							responsivePriority: 3,
							render: function(data, type, row) {
								return vm.progressCell(row, type)
							}
						},
						{
							data: 'countTaskPending',
							title: '<translate>Pendientes</translate>',
							responsivePriority: 4,
							render: function(data, type) {
								let pending = parseInt(data) || 0
								if(type !== 'display'){
									return pending
								}
								return pending ? `<span class="dash-pending">${pending}</span>` : '<span class="text-muted">0</span>'
							}
						},
						{
							data: 'respName',
							title: '<translate>Responsable</translate>',
							responsivePriority: 6,
							render: function(data, type, row) {
								return type !== 'display' ? (data || '') : vm.personCell(data, row.respImg)
							}
						},
						{
							data: 'status',
							title: '<translate>Estatus</translate>',
							responsivePriority: 5,
							render: function(data, type) {
								if(type !== 'display'){
									return data || ''
								}
								return data ? `<span class="badge badge-${PROJECT_STATUS[data] || 'default'}-inverted">${escapeHtml(data)}</span>` : '<span class="text-muted">—</span>'
							}
						},
						{
							data: 'id',
							title: '',
							className: 'text-right dash-actions-cell',
							orderable: false,
							searchable: false,
							responsivePriority: 2,
							render: function(data, type) {
								return type !== 'display' ? '' : vm.projectActions(data)
							}
						}
					],
					// Projects with pending activities first: they are what needs follow-up.
					order: [[ 2, 'desc' ], [ 0, 'asc' ]],
					drawCallback: function() {
						$(vm.$refs.projectsTable).find('.tip').tooltip();
					}
				});
				$(vm.$refs.projectsTable).on('click', '[data-action]', vm.onTableAction)
			},
			refreshProjects(){
				if(!this.projectsTable){
					return this.initProjectsTable()
				}
				this.projectsTable.clear().rows.add(this.listProjects).draw()
			}
		},
		mounted:async function() {
			let vm = this

			vm.initTasksTable()
			await Promise.all([vm.dataDashboard(), vm.dataContent(), vm.eventsList()])
			if(vm.destroyed){
				return
			}
			vm.refreshProjects()
			vm.renderChart()
		},
		beforeDestroy() {
			this.destroyed = true
			$(this.$el).find('.tip').tooltip('dispose')
			if(this.tasksTable){
				$(this.$refs.tasksTable).off('click')
				this.tasksTable.destroy()
			}
			if(this.projectsTable){
				$(this.$refs.projectsTable).off('click')
				this.projectsTable.destroy()
			}
			if(this.chart){
				this.chart.destroy()
			}
		}
	}
</script>
<style>
	.all-wrapper table.dataTable{
		border-collapse: inherit!important;
	}

	/* HEADER: filters sit beside the title, and below it on phones */
	.dashboard .dash-intro {
		display: flex;
		flex-direction: column;
	}
	.dashboard .dash-intro .element-header {
		order: 1;
		margin-bottom: 1rem;
	}
	.dashboard .dash-intro .dash-filters {
		order: 2;
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: .5rem;
		margin-bottom: 1rem;
	}
	.dashboard .dash-intro .dash-kpis {
		order: 3;
	}

	/* KPIs */
	.dashboard .dash-kpis {
		margin-left: -.375rem;
		margin-right: -.375rem;
	}
	.dashboard .dash-kpis > [class*="col-"] {
		padding-left: .375rem;
		padding-right: .375rem;
	}
	.dashboard .element-box.dash-kpi {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: .6rem;
		height: calc(100% - .75rem);
		margin-bottom: .75rem;
		padding: 1rem;
		text-align: left;
	}
	.dashboard .dash-kpi-icon {
		flex: 0 0 auto;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 36px;
		height: 36px;
		border-radius: 10px;
		font-size: 18px;
	}
	.dashboard .dash-kpi-icon.is-success { color: #5eb314; background-color: rgba(94, 179, 20, .12); }
	.dashboard .dash-kpi-icon.is-danger  { color: #e65252; background-color: rgba(230, 82, 82, .12); }
	.dashboard .dash-kpi-icon.is-primary { color: #047bf8; background-color: rgba(4, 123, 248, .1); }
	.dashboard .dash-kpi-icon.is-warning { color: #d39e00; background-color: rgba(251, 228, 160, .45); }
	.dashboard .dash-kpi-body {
		min-width: 0;
		width: 100%;
	}
	.dashboard .dash-kpi .label {
		font-size: .62rem;
		line-height: 1.4;
	}
	.dashboard .dash-kpi .value {
		display: block;
		font-size: 1.35rem;
		letter-spacing: .5px;
		overflow-wrap: anywhere;
	}
	.dashboard .dash-kpi-foot {
		font-size: .75rem;
		color: rgba(90, 99, 126, .8);
		line-height: 1.3;
	}
	.dashboard .dash-kpi-foot strong {
		color: #e65252;
	}

	/* TABLES */
	.dashboard .dash-table {
		margin-top: 0 !important;
	}
	.dashboard .dash-table td {
		vertical-align: middle;
	}
	/* A global `.table thead th { padding: 4px !important }` takes away the room for the sort arrows */
	.dashboard table.dataTable.dash-table thead > tr > th.sorting,
	.dashboard table.dataTable.dash-table thead > tr > th.sorting_asc,
	.dashboard table.dataTable.dash-table thead > tr > th.sorting_desc {
		padding-right: 1.75rem !important;
	}
	/* leaves room for the responsive ▶ control beside multi-line names */
	.dashboard table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control .dash-project {
		max-width: calc(100% - 1.5rem);
	}
	.dashboard .dash-link {
		color: #334152;
		font-weight: 600;
	}
	.dashboard .dash-link:hover {
		color: #047bf8;
		text-decoration: none;
	}
	.dashboard .dash-muted {
		font-size: .78rem;
		color: rgba(90, 99, 126, .75);
	}
	.dashboard .dash-chip {
		max-width: 240px;
		white-space: normal;
		text-align: left;
		line-height: 1.3;
	}
	.dashboard .dash-note {
		display: flex;
		flex-direction: column;
		max-width: 280px;
	}
	.dashboard .dash-note-date {
		font-size: .72rem;
		color: rgba(90, 99, 126, .75);
	}
	.dashboard .dash-note-text,
	.dashboard .dash-project-text .dash-link {
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
		white-space: normal;
	}
	.dashboard .dash-note-text {
		font-size: .8rem;
	}
	/* inline: keeps the responsive ▶ control on the same line */
	.dashboard .dash-project {
		display: inline-flex;
		align-items: center;
		gap: .75rem;
		min-width: 0;
		vertical-align: middle;
	}
	.dashboard .dash-pending {
		display: inline-block;
		min-width: 1.6rem;
		padding: .15rem .45rem;
		border-radius: 10px;
		background-color: rgba(251, 228, 160, .6);
		color: #8a6100;
		font-size: .8rem;
		font-weight: 700;
		text-align: center;
	}
	.dashboard .dash-project-img {
		flex: 0 0 36px;
		width: 36px;
		height: 36px;
		border-radius: 8px;
		object-fit: cover;
		background-color: #f2f4f8;
	}
	.dashboard .dash-project-text {
		display: flex;
		flex-direction: column;
		min-width: 0;
		line-height: 1.3;
	}
	.dashboard .dash-progress {
		min-width: 110px;
	}
	.dashboard .dash-progress-head {
		display: flex;
		justify-content: space-between;
		gap: .5rem;
		margin-bottom: 4px;
		font-size: .78rem;
	}
	.dashboard .dash-progress-head span {
		color: rgba(90, 99, 126, .75);
	}
	.dashboard .dash-progress-track {
		height: 6px;
		border-radius: 3px;
		background-color: #e9ecef;
		overflow: hidden;
	}
	.dashboard .dash-progress-fill {
		height: 100%;
		border-radius: 3px;
		background-color: #047bf8;
	}
	.dashboard .dash-person {
		display: inline-flex;
		align-items: center;
		gap: .5rem;
		font-size: .85rem;
	}
	.dashboard .dash-person img {
		flex: 0 0 26px;
		width: 26px;
		height: 26px;
		border-radius: 50%;
		object-fit: cover;
	}
	.dashboard .dash-actions {
		display: inline-flex;
		gap: .3rem;
	}
	.dashboard .dash-actions .btn {
		padding: .3rem .45rem;
		line-height: 1;
	}
	.dashboard .dash-actions .btn i {
		font-size: 15px;
	}
	.dashboard .dataTables_wrapper .dataTables_filter input {
		max-width: 100%;
	}

	/* BENEFICIARIES */
	.dashboard .dash-benef {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 1.25rem;
		padding: 1.25rem;
	}
	.dashboard .dash-benef-chart {
		position: relative;
		flex: 0 0 auto;
		width: 160px;
		height: 160px;
	}
	.dashboard .dash-benef-total {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		pointer-events: none;
	}
	.dashboard .dash-benef-total strong {
		font-size: 1.6rem;
		line-height: 1.1;
	}
	.dashboard .dash-benef-total span {
		font-size: .65rem;
		letter-spacing: 1px;
		text-transform: uppercase;
		color: rgba(90, 99, 126, .75);
	}
	.dashboard .dash-benef-legend {
		width: 100%;
		margin: 0;
		padding: 0;
		list-style: none;
	}
	.dashboard .dash-benef-legend li {
		display: flex;
		align-items: center;
		gap: .6rem;
		padding: .55rem 0;
		border-top: 1px solid rgba(0, 0, 0, .05);
	}
	.dashboard .dash-benef-legend li:first-child {
		border-top: 0;
	}
	.dashboard .dash-benef-dot {
		flex: 0 0 10px;
		width: 10px;
		height: 10px;
		border-radius: 50%;
	}
	.dashboard .dash-benef-name {
		flex: 1;
	}
	.dashboard .dash-benef-num {
		font-weight: 600;
	}
	.dashboard .dash-benef-pct {
		width: 3rem;
		text-align: right;
		font-size: .8rem;
		color: rgba(90, 99, 126, .75);
	}

	@media (max-width: 767px) {
		.dashboard .element-wrapper {
			padding-bottom: 1.5rem;
		}
		.dashboard .element-wrapper .element-header {
			margin-bottom: 1.25rem;
		}
		.dashboard .dataTables_wrapper .dataTables_filter {
			float: none;
			text-align: left;
		}
	}
	@media (min-width: 576px) {
		/* Responsive measures this: it hides lower-priority columns before squeezing the name */
		.dashboard .dash-project {
			min-width: 220px;
		}
		.dashboard .element-box.dash-kpi {
			flex-direction: row;
			align-items: center;
			gap: 1rem;
			padding: 1.25rem;
		}
		.dashboard .dash-kpi-icon {
			width: 46px;
			height: 46px;
			border-radius: 12px;
			font-size: 22px;
		}
		.dashboard .dash-kpi .label {
			font-size: .68rem;
		}
		.dashboard .dash-kpi .value {
			font-size: 1.6rem;
		}
	}
	/* Full-width donut card: chart and legend side by side */
	@media (min-width: 576px) and (max-width: 1449px) {
		.dashboard .dash-benef {
			flex-direction: row;
			justify-content: center;
			gap: 2rem;
		}
		.dashboard .dash-benef-legend {
			flex: 1;
			max-width: 420px;
		}
	}
	@media (min-width: 768px) {
		.dashboard .dash-intro {
			display: block;
			padding-bottom: 1.5rem;
		}
		.dashboard .dash-intro .element-header {
			margin-bottom: 2rem;
		}
		.dashboard .dash-intro .dash-filters {
			display: flex;
			float: right;
			margin-bottom: 0;
		}
		.dashboard .dash-intro .dash-filters select {
			width: 170px;
		}
		.dashboard .dash-kpis {
			margin-left: -.5rem;
			margin-right: -.5rem;
		}
		.dashboard .dash-kpis > [class*="col-"] {
			padding-left: .5rem;
			padding-right: .5rem;
		}
		.dashboard .element-box.dash-kpi {
			height: calc(100% - 1rem);
			margin-bottom: 1rem;
		}
	}
	@media (min-width: 1200px) {
		.dashboard .dash-kpi .value {
			font-size: 1.75rem;
		}
	}
	@media (min-width: 1450px) {
		.dashboard .dash-benef-chart {
			width: 180px;
			height: 180px;
		}
	}
</style>

