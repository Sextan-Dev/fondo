<template>
<div class="row task-form">
    <div class="col-md-12">
        <h5 class="form-header"><i class="os-icon os-icon-phone-21 m-r-10 mr-2"></i>
            <translate>General</translate>
        </h5>
        <hr>
        <form id="formGeneral" @submit.prevent="formSubmit" novalidate="true">
            <div class="row">
                <div class="col-md-8">

                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Proyecto</label>
                                <select class="form-control" @change="$parent.$emit('updateHeader')" name="project_id" placeholder="..." v-model="form.project_id" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('project_id') }">
                                    <option v-for="(option, indexOption ) in optionsProjects" :value="option.value" :key="indexOption">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <!-- Error Message -->
                                <div class="invalid-feedback" v-show="errors.has('project_id')">
                                    {{ errors.first('project_id') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group ">
                                <label v-translate>Responsable de la actividad</label>
                                <select class="form-control" @change="$parent.$emit('updateHeader')" name="user_id" placeholder="..." v-model="form.user_id" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('user_id') }">
                                    <option v-for="(option, indexOption ) in optionsUsers" :value="option.value" :key="indexOption">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <!-- Error Message -->
                                <div class="invalid-feedback" v-show="errors.has('user_id')">
                                    {{ errors.first('user_id') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Nombre de la actividad</label>
                                <input type="text" name="name" class="form-control md-6" placeholder="..." value="" v-model="form.name" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('name') }">
                                <!-- Error Message -->
                                <div class="invalid-feedback" v-show="errors.has('name')">
                                    {{ errors.first('name') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Status</label>
                                <select class="form-control" name="status" placeholder="..." v-model="form.status" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('status') }">
                                    <option v-for="(option, indexOption ) in optionsStatus" :value="option.value" :key="indexOption">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <!-- Error Message -->
                                <div class="invalid-feedback" v-show="errors.has('name')">
                                    {{ errors.first('name') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 d-none">
                            <div class="form-group">
                                <label v-translate>Clave</label>
                                <input type="text" name="slug" class="form-control md-6" placeholder="..." value="" v-model="form.slug" :class="{'input': true, 'is-invalid': errors.has('slug') }">
                                <!-- Error Message -->
                                <div class="invalid-feedback" v-show="errors.has('slug')">
                                    {{ errors.first('slug') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Desde</label>
                                <div class="date-input">
                                    <input type="text" name="from" v-model="form.date_from" class="single-date from form-control" placeholder="..." value="" :class="{'input': true, 'is-invalid': errors.has('from') }">
                                    <div class="invalid-feedback" v-show="errors.has('from')">{{ errors.first('from') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class=" col-md-5 ">
                            <div class="form-group">
                                <label v-translate>Hasta</label>
                                <div class="date-input">
                                    <input type="text" name="to" v-model="form.date_to" class="single-date to form-control" placeholder="..." value="" :class="{'input': true, 'is-invalid': errors.has('to') }">
                                    <div class="invalid-feedback" v-show="errors.has('to')">{{ errors.first('to') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                  
                    <!-- <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label for="">
                                        <span v-translate>Descripción Corta</span>
                                    </label>
                                    <span clasS="help" v-translate>Agrega una descripción detalla de la actividad</span>
                                    <br>
                                    <textarea name="description" style="min-width: 100%" :class="{'input': true, 'is-invalid': errors.has('description') }" class="form-control" v-model="form.description" rows="5"></textarea>

                                    <div class="invalid-feedback" v-show="errors.has('description')" >
                                        {{ errors.first('description') }}
                                    </div> 
                                </div>
                            </div>
                        </div> -->
                    <div class="row">
                        <div class="col-md-10">
                            <div class="form-group">
                                <label v-translate>Descripción</label>

                                <editor id="description_html" v-model="form.description_html" :init="config" />
                            </div>
                        </div>
                    </div>

                    <!-- 
                        <div class="">
                            <div class="col-md-12">
                                <legend class="mb-1">Archivos externos</legend>
                            </div>
                        </div> -->

                    <!-- COMMENTS -->
                    <div class="row">
                        <div class="col-md-12">
                            <legend class="mb-1">
                                <translate>Comentarios</translate>
                            </legend>
                            <div class="support-index" v-if="form.comments.length > 0">
                                <div class="support-ticket-content-w">
                                    <div class="support-ticket-content">
                                        <div class="ticket-reply" v-for="(comment, key) in form.comments" :key="key">
                                            <div class="ticket-thread">
                                                <div class="ticket-reply-info">
                                                    <a class="author with-avatar" href="#">
                                                        <img alt="" :src="appDomain+'/'+comment.user.img" onerror="javascript:this.src='/images/user-grey.png'">
                                                        <span>{{comment.user.name}}</span>
                                                    </a>
                                                    <span class="info-data">
                                                        <span class="label">&nbsp;</span>
                                                        <span class="value">{{comment.created_at}}</span>
                                                    </span>
                                                </div>
                                                <div class="ticket-reply-content">
                                                    <p>{{comment.value}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mt-2 ml-5">
                                        <label v-translate>Agregar Comentario</label>
                                        <br>
                                        <textarea name="comment" style="min-width: 100%" class="form-control" v-model="form.comment" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-md-4">
                    <legend class="mt-0">
                        <translate>Recordatorios</translate>
                    </legend>
                    <span class="help">Se enviara un correo un dia antes de la fecha definida y otro en la fecha definida</span>
                    <div class="element-wrapper pb-0" v-if="form.reminders">
                        <div class="element-box-tp">
                            <div class="activity-boxes-w">
                                <div class="activity-box-w" :key="index" v-for="( reminder , index) in form.reminders">
                                    <div class="activity-time">{{reminder.date.substring(0,10)}}</div>
                                    <div class="activity-box">
                                        <div class="trash-simbol row-actions "><a title="Eliminar Recordatorio" @click.prevent="removeReminder(reminder.id , index )" href="#" class="tip danger"><i class="icon-feather-trash-2"></i></a></div>
                                        <!-- <div class="activity-avatar">
                                                <img alt="" src="images/avatar1.jpg">
                                            </div> -->
                                        <div class="activity-info">
                                            <div class="activity-role">{{reminder.user.name}}</div>
                                            <strong class="activity-title">{{reminder.value}}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 element-wrapper">
                            <div class="form-group mt-2">
                                <h6 class="element-header">
                                    <translate>Agregar Nuevo Recordatorio</translate><br>
                                    <span class="help" v-translate>Esta etapa será atribuida al responsable actual de la actividad</span>
                                </h6>
                                <div class="form-group ">
                                    <label v-translate>Fecha</label>
                                    <input type="text" name="milestone" v-model="form.milestone" class="form-control date">
                                </div>
                                <div class="form-group ">
                                    <label v-translate>Descripción</label>
                                    <textarea name="reminder" rows="2" class="form-control" v-model="form.reminder"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-centered">
                            <button class="btn btn-primary btn-md" type="submit" :disabled="loading">
                                <span v-show="loading">
                                    <translate>Guardando</translate>...
                                </span>
                                <span v-show="!loading">
                                    <translate>Guardar</translate>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-buttons-w">
                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                    <span v-show="loading">
                        <translate>Guardando</translate>...
                    </span>
                    <span v-show="!loading">
                        <translate>Guardar</translate>
                    </span>
                </button>
                <button class=" btn btn-light" type="button" href="" @click.prevent="$router.push({name:'TaskList'})" v-translate> Cancelar</button>
            </div>
        </form>
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
    data() {
        return {
            config: {
                menubar: false,
                branding: false,
                height: 300,
                plugins: [
                    'advlist table autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                    alignleft aligncenter alignright alignjustify | \
                    table tabledelete | tableprops tablerowprops tablecellprops | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol \
                    bullist numlist outdent indent | removeformat | help'
            },
            loading: true,
            appDomain: appDomain,
            optionsProjects: [],
            optionsStatus: [],
            optionsCategories: [],
            optionsTypes: [],
            optionsTags: [],
            optionsUsers: [],
            optionsPublic: [],
            optionsZoneImpact1: [],
            optionsZoneImpact2: [],
            optionsZoneImpact3: [],
            optionsSocial: [],
            optionsally: [],
            filesList: null,
            files: null,
            from: moment().format('YYYY-MM-DD'),
            to: moment().format('YYYY-MM-DD'),
        }
    },
    props: ['form', 'configPlugins'],
    methods: {
        onFileChange(e) {
            var files = e.target.files || e.dataTransfer.files;
            if (files.length) {
                this.addFileToList(files[0]);
            } else {
                return;
            }
        },

        addFileToList(file) {
            var reader = new FileReader();
            var self = this;
            reader.onload = (e) => {
                self.files = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        data: async function () {
            let vm = this
            await axios.post(api.dataTask)
                .then(function (response) {
                    if (response.data.success) {
                        response.data.projects_all.forEach(function (value, key) {
                            if (value.value != '') {
                                vm.optionsProjects.push({
                                    'value': value.value,
                                    'label': value.label
                                })
                            }
                        });
                        _.each(response.data.status, function (value, key) {
                            vm.optionsStatus.push({
                                'value': value.slug,
                                'label': value.name
                            })
                        })
                        response.data.users_all.forEach(function (value, key) {
                            if (value.value != '') {
                                vm.optionsUsers.push({
                                    'value': value.value,
                                    'label': value.label
                                })
                            }
                        });
                        vm.optionsTypes = response.data.types
                        vm.optionsCategories = response.data.categories
                        vm.optionsPublic = response.data.public
                        vm.optionsZoneImpact1 = response.data.zone_impact_1
                        vm.optionsZoneImpact2 = response.data.zone_impact_2
                        vm.optionsZoneImpact3 = response.data.zone_impact_3
                        vm.optionsSocial = response.data.social
                        vm.optionsally = response.data.ally

                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
        },
        removeReminder: async function (id, index) {
            swal({
                title: "<translate>Seguro ?</translate>",
                html: "<translate>Estas a punto de eliminar este elemento, deseas continuar?</translate>",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: '<translate>Confirmar</translate>',
                cancelButtonText: '<translate>Cancelar</translate>',
            }).then((confirm) => {
                if (confirm.value) {
                    var vm = this;
                    axios.post(api.deleteTaskStep, {
                            id: id
                        })
                        .then(async function (response) {
                            if (response.data.success) {
                                vm.$parent.$emit('updateReminder', index)
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
                        title: "<translate>Operación Cancelada</translate>",
                        type: "error",
                        timer: 1200
                    });
                }
            });
        },
        // Form submit for Update & Store
        formSubmit() {
            var vm = this;
            this.$validator.validateAll().then((result) => {
                if (result) {
                    vm.loading = true;

                    if (!this.$route.params.id) {
                        var apiRoute = api.storeTask;
                        var redirectToList = true
                    } else {
                        if (!this.$route.params.action) {
                            vm.form.id = this.$route.params.id

                            var apiRoute = api.updateTaskGeneral
                        } else {
                            if (this.$route.params.action == 'copy') {
                                var apiRoute = api.duplicateTask;
                            }
                        }
                    }
                    vm.form['files'] = vm.files
                    axios.post(apiRoute, vm.form)
                        .then(async function (response) {
                            if (response.data.success) {
                                toastr.success(response.data.msg);
                                if (vm.$route.params.action == 'copy') {
                                    vm.$parent.$emit('updateId', response.data.task_id)
                                } else {
                                    vm.$parent.$emit('updateForm', response.data.task)
                                }

                                vm.loading = false;
                            } else {
                                toastr.error(response.data.msg);
                                vm.loading = false;
                            }
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                            vm.loading = false;
                        })
                }
            })
        }
    },
    mounted: async function () {
        let vm = this
        await this.data()
        //DATEPICKER WITH AUTO SET
        $('.single-date').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            },
            opens: "left",
            autoApply: true,
            autoUpdateInput: false
        }, function (start, end, label) {
            var selectedStartDate = start.format('YYYY-MM-DD');
            var selectedEndDate = end.format('YYYY-MM-DD');
            var from = $('.single-date.from');
            var to = $('.single-date.to');
            from.val(selectedStartDate);
            to.val(selectedEndDate);
            var toPicker = to.data('daterangepicker');
            toPicker.setStartDate(selectedStartDate);
            toPicker.setEndDate(selectedEndDate);
            var fromPicker = from.data('daterangepicker');
            fromPicker.setStartDate(selectedStartDate);
            fromPicker.setEndDate(selectedEndDate);
            //RELOAD DATATABLE
            vm.form.date_from = selectedStartDate;
            vm.form.date_to = selectedEndDate;
        })

        function updateDatepicker(start, end) {
            vm.form.from = moment(start).format('YYYY-MM-DD HH:mm:ss');
            vm.form.to = moment(end).format('YYYY-MM-DD HH:mm:ss');
            $('input.double-datetime').data('daterangepicker').setStartDate(moment(start).format('DD/MM/YYYY HH:mm'));
            $('input.double-datetime').data('daterangepicker').setEndDate(moment(end).format('DD/MM/YYYY HH:mm'));
        };

        vm.loading = false
    }
}
</script>

<style>
.el-select {
    width: 100% !important;
}

.el-date-editor {
    width: 100% !important;
}
</style>
