<template>
	<div class="content-box">
		<div class="element-wrapper">

            <h6 class="element-header">
                <default-header></default-header>
            </h6>

            <div class="row">
                <div class="col-md-8">
                    <!-- Calendar -->		        
                    <div class="element-box">
                        <div id="fullCalendar"></div>
                    </div>
                </div>
                <div class="col-md-4">

                    <!-- Form -->
                    <div class="element-box"> 
                        <form id="formEvent" @submit.prevent="formSubmit" novalidate="true">
                            <div class="row">   
                                <div class="col-sm-12">

                                    <h5 v-if="formEdit" class="form-header"> <i class="icon-feather-edit mr-1"></i> <translate>Editar Evento</translate></h5>
                                    <h5 v-else-if="formView" class="form-header"> <i class="icon-feather-eye mr-1"></i> <translate>Ver Evento</translate></h5>
                                    <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i> <translate>Nuevo Evento</translate></h5>

                                    <hr>

                                    <span v-if="form.id" class="btn btn-warning btn-sm pull-right tip bolder"  title="Descargar Evento" @click="ics(form.name,form.description,form.location,form.start,form.end)"><i class="icon-feather-download"></i></span>

                                    <small v-if="form.username" class="mb-3 d-block"><translate>Modificado por </translate> {{form.username}} - {{form.updated_at}}</small>

                                    <div class="form-group">
                                        <label v-translate>Título</label>                                                            
                                        <input type="text" name="name" v-model="form.name" class="form-control"  placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('name') }" >
                                        <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                    </div>
                                    
                                    <div class="form-group">
                                        <label v-translate>Fecha & Duracíon</label>
                                        <div class="date-input">
                                            <input type="text" name="range" class="double-datetime form-control" placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('range') }" >
                                            <div class="invalid-feedback" v-show="errors.has('range')" >{{ errors.first('range') }}</div>  
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label v-translate>Categoría</label>
                                        <select name="category" class="form-control" v-model="form.category" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('category') }">
                                            <option value="private" v-translate>Personal</option>
                                            <option value="public" v-translate>Común</option>
                                        </select>
                                        <div class="invalid-feedback" v-show="errors.has('category')" >{{ errors.first('category') }}</div>  
                                    </div>

                                    <div class="form-group">
                                        <label v-translate>Ubicación</label>
                                        <div class="input-group mb-1">
                                            <div class="input-group-prepend"><div class="input-group-text"><i class="icon-feather-map-pin"></i></div></div>
                                            <input name="location" v-model="form.location" placeholder="..." class="form-control" />
                                        </div>
                                        <a v-show="form.location" target="_blank" v-bind:href="'https://www.google.com/maps/dir/?api=1&query='+form.location"><span class="btn btn-sm btn-primary btn-block"><b><i class="icon-feather-navigation mr-2"></i> <translate>Direcciones</translate></b></span></a>
                                    </div>

                                    <div class="form-group">
                                        <label v-translate>Descripción</label>
                                        <textarea name="description" v-model="form.description" placeholder="..." class="form-control" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label v-translate>Comentario</label> <small>Opcional</small>
                                        <textarea name="comment" v-model="form.comment" placeholder="..." class="form-control" rows="2"></textarea>
                                    </div>

                                    <div class="form-buttons-w">

                                        <button v-show="!formView" class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                            <span v-show="loading"><translate>Guardando</translate>...</span>
                                            <span v-show="!loading"><translate>Guardar</translate></span>
                                        </button>
                                        
                                        <button v-if="formEdit" class="btn btn-lg btn-danger" type="button" @click.prevent="deleteItem"><i class="icon-feather-trash-2"></i></button>

                                        <button class="btn btn-light" type="cancel" @click="resetForm" v-translate>Cancelar</button>
                                    </div>

                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
                
		</div>
	</div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {ics} from "../../core/helpers.js";
	import {saveAs} from "../../core/helpers.js";
    import 'fullcalendar';
    import 'fullcalendar/dist/locale/es.js';
    import 'fullcalendar/dist/locale/fr.js';

	export default {
        props:['appDomain','siteName', 'auth'],
		data(){
			return {
                events : [],     
                eventEdit : null,     
                loading: false,
                formEdit: false,
                formView: false,
                calendar: null,
                form: {
                    name: null,
                    category: 'private',
                    start: moment().add(1,'days').format('YYYY-MM-DD ')+'09:00',
                    end: moment().add(1,'days').format('YYYY-MM-DD ')+'11:00',
                    allday:0,
                    description: null,
                    location: null,
                    comment: null,
                    username: null,
                }
            }
        },
        methods:{
			ics(name,description,location,start,end) {
				var cal = ics();
				cal.addEvent(name, description, location, start, end);
				cal.download(name);
			},
            resetForm() {
                var vm = this;
                $('#formEvent')[0].reset();
                _.map(vm.form, function(val,key){ vm.form[key] = null; });
                vm.calendar.fullCalendar('unselect');
                vm.formEdit = false;
                vm.formView = false;
                setTimeout(function () {
                    vm.errors.clear();
                },30);
            },
            indexEvent: async function(){
                var vm = this;
                vm.loading = true;
                await axios.get(api.indexEvent)
                .then(function (response) {
                    var eventsFormatted = [];
                    response.data.data.map(function(value, key) {
                        vm.formatEvent(value);
                        eventsFormatted.push(value);
                    });
                    vm.events = eventsFormatted; 
                    vm.loading = false;                               
                })
                .catch(function (error) {
                    vm.events = [];
                    console.log(error);
                    vm.loading = false;
                });
            },
            showEvent: function(id){
                var vm = this;                 
                vm.loading = true; 
                if(id){
                    axios.post(api.showEvent, {id:id})
                    .then(function (response) {
                        vm.form = response.data.data;
                        vm.formEdit = (vm.auth.id==vm.form.user_id) ? true : false;
                        vm.formView = (vm.auth.id!=vm.form.user_id) ? true : false;
                        vm.loading = false; 
                        vm.calendar.fullCalendar('unselect');
                    })
                    .catch(function (error) {
                        console.log(error);
                        vm.loading = false;
                        vm.calendar.fullCalendar('unselect');
                    });
                }
            },
            updateEvent: function(request){
                var vm = this;                 
                vm.loading = true; 
                if(request.id){
                    axios.put(api.updateEvent, request)
                    .then(function (response) {
                        if(response.data.success){
                            vm.events = _.without(vm.events, _.findWhere(vm.events, {id: request.id}));
                            vm.formatEvent(response.data.data);
                            vm.events.push(response.data.data);
                            toastr.success(response.data.msg);
                        } else {
                            toastr.error(response.data.msg);
                        }
                        vm.loading = false;
                    })
                    .catch(function (error) {
                        console.log(error);
                        vm.loading = false;
                    });
                }
            },
            formatEvent: function(event){ 
                var vm = this;  
                event.title = event.name;
                event.color = ((event.category=='private') ? '#5BB2AA' : ((event.category=='public') ? '#4FA2DA' : '#5A697C'));
                event.allDay = (moment(event.start).format('HH:mm')=='00:00' && moment(event.end).format('HH:mm')=='00:00') ? true : false;
                event.editable = (vm.auth.id == event.user_id) ? true : false;
            },
            deleteItem: function(id){ 
                var vm = this;  
                id = vm.form.id;  
                if(id){
                    swal({
                        title: "<translate>Seguro ?</translate>",
                        html: "<translate>Esta seguro de querer eliminar este elemento ?</translate>",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: '<translate>Confirmar</translate>',
                        cancelButtonText: '<translate>Cancelar</translate>',
                    }).then((confirm) => {
                        if (confirm.value) {
                            axios.post(api.deleteEvent, {id:id})
                            .then(function (response) {
                                if(response.data.success){
                                    swal({title : "<translate>Operación Exítosa</translate>", type : "success", timer:1500});
                                    vm.events = _.without(vm.events, _.findWhere(vm.events, {id: id}));
                                    vm.calendar.fullCalendar("removeEvents",id);
                                } else {
                                    toastr.error(response.data.msg);
                                }
                            })
                            .catch(function (error) {
                                console.log(error);
                            });
                        } else {
                            swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                        }
                    });
                }
            },
            formSubmit(){
                var vm = this;  
                vm.$validator.validateAll().then((result) => {
                    if (result) {  
                        vm.loading = true;
                        vm.calendar.fullCalendar('unselect');
                        if(!vm.form.id && !vm.formEdit){
                            //STORE NEW EVENT
                            axios.post(api.storeEvent, vm.form)
                            .then(function (response) {
                                if(response.data.success){
                                    toastr.success(response.data.msg);
                                    var newEvent = response.data.data;
                                    vm.formatEvent(newEvent);
                                    vm.events.push(newEvent);
                                    vm.calendar.fullCalendar('renderEvent',newEvent, true);
                                } else {
                                    toastr.error(response.data.msg);
                                }
                                vm.loading = false;
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                vm.loading = false;
                            });
                        } else { 
                            //UPDATE EVENT
                            vm.updateEvent(vm.form);
                            vm.formatEvent(vm.form);
                            vm.calendar.fullCalendar('removeEvents',vm.form.id);
                            vm.calendar.fullCalendar('renderEvent',vm.form, true);
                        }
                    }
                });
            }     
        },
        mounted:  async function() { 

            //GET DATA
            var vm = this;
            await vm.indexEvent();
            
            //SET CALENDAR
            vm.calendar = $("#fullCalendar").fullCalendar({
                header: {
                    left: "prev,next today",
                    center: "title",
                    right: "month,agendaWeek,agendaDay"
                },
                locale: this.$language.current.startsWith('fr') ? 'fr' : 'es',
                defaultView: 'agendaWeek',
                selectable: true,
                selectHelper: true,
                unselectAuto: false,
                select: function(start, end) {
                    updateDatepicker(start,end);
                    $('input[name=name]').focus();
                },
                eventClick: function(event) {
                    vm.showEvent(event.id);
                    updateDatepicker(event.start,event.end);
                },
                eventDrop: function(event, delta, revertFunc) {
                    swal({
                        title: "<translate>Confirmar ?</translate>",
                        html: "<translate>Esta seguro de querer modificar este elemento ?</translate>",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: '<translate>Confirmar</translate>',
                        cancelButtonText: '<translate>Cancelar</translate>',
                    }).then((confirm) => {
                        if (confirm.value) { 
                            if (!event.end && moment(event.start).format('HH:mm')=='00:00') { event.end = moment(event.start).add(24,'hours');} 
                            else if (!event.end) { event.end = moment(event.start).add(2,'hours');} 
                            vm.updateEvent({
                                id : event.id, 
                                start : moment(event.start).format("YYYY-MM-DD HH:mm:ss"), 
                                end : moment(event.end).format("YYYY-MM-DD HH:mm:ss")
                            });	
                            swal({title : "<translate>Operación Exitosa</translate>", type : "success", timer:1500});
                        } else {
                            swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                            revertFunc();
                        }
                    });
                },
                eventResize: function(event, delta, revertFunc) {
                    swal({
                        title: "<translate>Confirmar ?</translate>",
                        html: "<translate>Esta seguro de querer modificar este elemento ?</translate>",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: '<translate>Confirmar</translate>',
                        cancelButtonText: '<translate>Cancelar</translate>',
                    }).then((confirm) => {
                        if (confirm.value) { 
                            vm.updateEvent({
                                id : event.id, 
                                start : moment(event.start).format("YYYY-MM-DD HH:mm:ss"), 
                                end : moment(event.end).format("YYYY-MM-DD HH:mm:ss")
                            });	
                            swal({title : "<translate>Operación Exitosa</translate>", type : "success", timer:1500});
                        } else {
                            swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                            revertFunc();
                        }
                    });
                },
                editable: true,
                events: vm.events
            });  

            //DATEPICKER
            $('input.double-datetime').daterangepicker({
                autoApply: true,
                startDate: moment(vm.form.start).format('DD/MM/YYYY HH:mm'),
                endDate: moment(vm.form.end).format('DD/MM/YYYY HH:mm'),
                applyClass: "btn-primary",
                applyLabel: "OK",
                cancelLabel: "Cancel",
                opens: "left",
                timePicker: true,
                timePickerIncrement: 30,
                timePicker24Hour: true,
                locale: {
                    "format": "DD/MM/YYYY HH:mm"
                }
            });
            $('input.double-datetime').on('apply.daterangepicker', function(ev, picker) {
                updateDatepicker(picker.startDate,picker.endDate);
            });
            function updateDatepicker(start,end) {
                vm.form.start = moment(start).format('YYYY-MM-DD HH:mm:ss');
                vm.form.end = moment(end).format('YYYY-MM-DD HH:mm:ss');
                $('input.double-datetime').data('daterangepicker').setStartDate(moment(start).format('DD/MM/YYYY HH:mm'));
                $('input.double-datetime').data('daterangepicker').setEndDate(moment(end).format('DD/MM/YYYY HH:mm'));
                vm.allday = (moment(start).format('HH:mm')=='00:00' && moment(end).format('HH:mm')=='00:00') ? 1 : 0;
            };

            if(this.$route.params.id){
                await this.showEvent(this.$route.params.id);                
            }
        }
    }
</script>

<style>
@import '~fullcalendar/dist/fullcalendar.css';
</style>