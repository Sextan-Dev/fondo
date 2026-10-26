<template>
	<div class="content-box">
		<div class="control-header">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <form id="filtersTask" class="form-inline" @change="select">
                       
                       
                        <div class="form-group mr-4">
                            <label class="mr-2" for="">Proyecto</label>

                            <el-select name="project" v-model="project" filterable placeholder="..." @change="select">
                                <el-option :value="''" :label="'Todos'"></el-option>
                                <el-option
                                    v-for="(item, indexProj ) in projects"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value" >
                                </el-option>
                            </el-select>
                        </div>                                        
                                           
                        <div class="form-group" style="right: 0;position: absolute;">
                            <div class="element-search"><input autofocus v-model="find" placeholder="Buscar..." type="text"></div>
                        </div>
             
                    </form>
                </div>
            
            </div>
        </div>
        
        <div class="pipelines-w">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mr-4" >
                        <a @click.prevent="$router.push({name:'TaskForm',params: {project: project } })" class="btn btn-sm btn-primary btn-upper" href="#"><i class="os-icon os-icon-ui-22"></i><translate>Nueva actividad</translate></a>                        
                    </div>    
                </div>
            </div>
            <div class="row">
                <!--COLUMNS STATUS-->
                <div class="col-lg-4 col-xxl-3" v-for="(column, indexColumn ) in columns" :key="indexColumn">
                    <div v-if="status[column.status]" :class="'pipeline white lined-'+status[column.status].comment">
                        <div class="pipeline-header">
                            <h5 class="pipeline-name">{{status[column.status].name}}</h5>
                            <div class="pipeline-settings" @click="column.open=!column.open">
                                <i :class="{ 'icon-feather-chevron-up' : column.open, 'icon-feather-chevron-down' : !column.open }"></i>
                            </div>
                        </div>
                        <div class="pipeline-body"  :statusSlug="status[column.status].slug">
                            <div v-if="!column.open" @click="column.open=!column.open" class="seeMore"><i class="icon-options"></i></div>
                            <div class="pipeline-item" v-show="column.open" v-for="(task, indexTask ) in tasks" v-if="column.status==task.status" :key="indexTask" :item-id="task.id" :item-status="task.status">
                                <div class="pi-controls">
                                    <div class="pi-settings os-dropdown-trigger">
                                        <i class="os-icon os-icon-ui-46"></i>
                                        <div class="os-dropdown">
                                            <div class="icon-w">
                                                <i class="os-icon os-icon-ui-46"></i>
                                            </div>
                                            <ul>
                                                <li>
                                                    <a href="#" class="editItem" @click.prevent="$router.push({name:'TaskForm',params: {id: task.id } })"  ><i class="icon-feather-edit"></i> <translate>Editar</translate></a>
                                                </li>
                                                <li>
                                                    <a href="#" class="copyItem" @click.prevent="$router.push({name:'TaskForm',params: {id: task.id, action:'copy' } })"><i class="icon-feather-copy"></i> <translate>Copiar</translate></a>
                                                </li>
                                                <li>
                                                    <a href="#" @click.prevent="$router.push({name:'ProjectReport',params: {taskId: task.id } })"><i class="icon-feather-file-text"></i> <translate>Reporte</translate></a>
                                                </li>
                                                <li>
                                                    <a href="#" class="deleteItem" @click.prevent="deleteItem(task.id)" ><i class="icon-feather-trash-2"></i> <translate>Suprimir</translate></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="pi-body">
                                    <div class="pi-info">
                                        <div class="h6 pi-name pr-3">
                                            <a href="#" class="editItem text-black" @click.prevent="$router.push({name:'TaskForm',params: {id: task.id } })" >
                                                {{task.name}} 
                                            </a>
                                            <div :class="status[task.status].comment" class="status-pill tip smaller" :title="status[column.status].name"></div>
                                        </div>
                                        <div class="pi-sub">{{task.user}}</div>
                                        <a @click.prevent="$router.push({name:'ProjectForm', params: {id: task.project_id }})" class="badge badge-primary" href="#">{{task.project}}</a>
                                        <div class="hidden d-none" >
                                            <div v-if="task.countComment>0" class="last-comment">
                                                <b><translate>Último comentario</translate> ({{task.lastUser}} - {{task.lastCommentDate}}) :</b>
                                                <br>
                                                {{task.lastComment}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pi-foot hidden d-none">
                                    <div class="tags">
                                        <span class="badge badge-warning-inverted">{{task.program}}</span>
                                    </div>
                                    <a v-if="task.countComment>0" class="extra-info"><i class="os-icon os-icon-mail-12"></i><span>{{task.countComment}}</span> <translate v-if="task.countComment>1">comentarios</translate><translate v-else>comentario</translate></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</template>
<script>
        
    import {EventBus} from './../../core/eventBus.js';
    import {api} from "../../config/jwt-routes";
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                columns: [
                    {
                        open: true,
                        status:"pending"
                    },
                    {
                        open: true,
                        status:"approved"
                    },
                    {
                        open: true,
                        status:"working",
                    },
                    {
                        open: true,
                        status:"done"
                    },
                    {
                        open: false,
                        status:"canceled"
                    },
                ],
                project:'',
                projects:[],
                user:'',
                users:[],
                status:[],
                loading : true,
                find:'',  
                tasksAll:[]
            }
        },
        computed: {
            tasks: function () {
                let vm = this
                if(this.find== ''){
                    return this.tasksAll
                }else{
                    let records = _.filter(this.tasksAll, function(d){ return (d['name'].toLowerCase() ).startsWith(vm.find.toLowerCase() ); })                    
                    return  records
                }                
            }
        },
        methods: {            
            // GET FILTERS & COUNTS
            data:async function(){
                var vm = this;
                await axios.post(api.dataTask)
                .then(function (response) {
                    if(response.data.success){
                        vm.status = response.data.status;
                        vm.projects = response.data.projects;
                        vm.users = response.data.users;
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
            },
            // SELECT
            select:async function(){
                var vm = this;
                await axios.post(api.selectTask,{project:vm.project, user:vm.user,keyBy:'id'})
                .then(function (response) {
                    if(response.data.success){
                        vm.tasksAll = response.data.data;
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
            },
            // UPDATE
            updateTask: function(request){
                var vm = this;                 
                vm.loading = true; 
                if(request.id){
                    axios.post(api.updateTask, request)
                    .then(function (response) {
                        if(response.data.success){
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
            // UPDATE ACTIVE 1 -> 0
            deleteItem(id){
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
                        axios.post(api.deleteTask, {id:id})
                        .then(async function (response) {
                            if(response.data.success){
                                vm.select();
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
                        swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                    }
                });
            }
        },
        mounted: async function(){

            var vm = this;

            //GET PARAMs
            if(vm.$route.params.project){
                vm.project = vm.$route.params.project;                
            }
            if(vm.$route.params.user) {
                vm.user = vm.$route.params.user;                
            }
            
            //SET DATA
            await vm.data();
            await vm.select();        

            //DRAGGABLE
            var drake = dragula($('.pipeline-body').toArray(), {
                //revertOnSpill : true
            }).on('drag', function () {
                //ON DRAG
            }).on('drop', function (el, container) {
                var elId = $(el).attr('item-id');
                var elStatus = $(el).attr('item-status');
                var columnStatus = $(container).attr('statusSlug');
                if(confirm("Esta seguro de querer modificar este elemento ?")) {
                    vm.updateTask({
                        id : elId,
                        status : columnStatus
                    });
                    //UPDATE ARRAY
                    vm.tasks[elId].status = columnStatus;
                    EventBus.$emit("updateUserTask");
                    return true;
                } else {
                    drake.cancel(true);
                    return false;
                }
                swal({
                    title: "<translate>Confirmar ?</translate>",
                    html: "<translate>Esta seguro de querer modificar este elemento ?</translate>",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: '<translate>Confirmar</translate>',
                    cancelButtonText: '<translate>Cancelar</translate>',
                }).then((confirm) => {
                    if (confirm.value) { 
                        vm.updateTask({
                            id : elId,
                            status : columnStatus
                        });
                        //UPDATE ARRAY
                        vm.tasks[elId].status = columnStatus;
                        EventBus.$emit("updateUserTask");
                    } else {
                        drake.cancel(true);
                        return false;
                    }
                });
            }).on('over', function (el, container) {
                //ON OVER
                $(container).closest('.pipeline-body').addClass('over');
            }).on('out', function (el, container, source) {
                //ON OUT
                var new_pipeline_body = $(container).closest('.pipeline-body');
                new_pipeline_body.removeClass('over');
                var old_pipeline_body = $(source).closest('.pipeline-body');
            });

            vm.loading = false;
        }
    }
</script>
<style>
    .pipeline-item:hover .hidden {
        display: flex!important;
    }
    .seeMore{
        border: none;
        text-align: center;
    }
    .element-search input {
        border: none;
        -webkit-box-shadow: none;
        box-shadow: none;
        background-color: #fff;
        border-radius: 30px;
        padding: 8px 15px 8px 40px;
        display: block;
        width: 100%!important;
        outline: none;
        border: none;
        box-shadow: none;
        
    }
    .element-search {
        position: relative;
        margin: 0px 1rem;
    }
    .element-search:before {
        font-family: 'osfont' !important;
        speak: none;
        font-style: normal;
        font-weight: normal;
        font-variant: normal;
        text-transform: none;
        line-height: 1;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        position: absolute;
        left: 15px;
        top: 50%;
        -webkit-transform: translateY(-50%);
        transform: translateY(-50%);
        font-size: 16px;
        content: "\e92c";
        color: rgba(0,0,0,0.4);
    }
</style>

