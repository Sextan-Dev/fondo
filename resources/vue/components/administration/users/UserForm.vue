<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'UserList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todos los Usuarios</translate> </button>
            </h6>

            <div class="element-box">
                <!-- Form Users New & Edit-->
                <form  enctype="multipart/form-data" id="formUser" @submit.prevent="formSubmit"  novalidate="true">
                    <div class="row">   
                        <div class="col-sm-12">
                            
                            <h5 v-if="$route.params.id" class="form-header"> <i class="icon-feather-edit mr-1"></i> <translate>Modificar Usuario</translate></h5>
                            <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i> <translate>Nuevo Usuario</translate></h5>
                            <hr> 

                            <div class="row">
                                <div class="col-md-9">
                                    <legend><translate>Datos Personales</translate></legend>

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Usuario</label>   
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                    </div>
                                                    <input type="text" name="user" v-model="form.user" class="form-control"  placeholder="..." value="" v-validate="'required|min:3'" :class="{'input': true, 'is-invalid': errors.has('user') }">
                                                    <div class="invalid-feedback"  v-show="errors.has('user')" >{{ errors.first('user') }}</div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Fecha de Ingreso a Fondo Unido</label>   
                                                <el-date-picker
                                                v-model="form.date_admission"                                                
                                                data-vv-as=" "                                         
                                                format= "yyyy-MM-dd"
                                                value-format="yyyy-MM-dd">
                                                </el-date-picker>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre Completo</label>
                                                <input type="text" :class="{'input': true, 'is-invalid': errors.has('name') }" v-validate="'required|min:3'" v-model="form.name"  name="name" class="form-control md-6"  placeholder="..." value="">
                                                <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                            </div>
                                        </div>                              
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Correo</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-mail"></i></div>
                                                    </div>
                                                    <input type="email"  :class="{'input': true, 'is-invalid': errors.has('email') }" v-validate="'required|email'" v-model="form.email" name="email" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback" v-show="errors.has('email')" >{{ errors.first('email') }}</div>
                                                </div>                                            
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group ">
                                                <label v-translate>Telefono</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-phone"></i></div>
                                                    </div>
                                                    <input type="text" :class="{'input': true, 'is-invalid': errors.has('phone') }"  v-validate="'numeric|min:10|max:10'" v-model="form.phone" name="phone" class="form-control md-6" placeholder="..." value="">
                                                    <div class="invalid-feedback" v-show="errors.has('phone')" >{{ errors.first('phone') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="mb-0" v-translate>Contraseña</label><br>
                                                <span class="help" v-translate>Debe contener mínimo 6 caracteres, sin caracteres especiales ni espacios</span>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="os-icon os-icon-fingerprint"></i></div>
                                                    </div>
                                                    <input   autocomplete="new-password" type="password" :class="{'input': true, 'is-invalid': errors.has('password') }"  v-validate="'min:6|alpha_num'" v-model="form.password" name="password" class="form-control md-6" placeholder="******">
                                                    <div class="invalid-feedback" v-show="errors.has('password')" >{{ errors.first('password') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- <div class="col-md-12" v-if="!$route.params.id">
                                            <input id="sendNotification" name="notificacion" v-model="form.notificacion" type="checkbox" value="1">
                                            <label for="sendNotification"><translate> Envia un correo electronico al usuario con instrucciones para establecer sus credenciales de acceso.</translate></label>
                                        </div> -->
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <legend><translate>Foto</translate></legend>
                                    <div class="text-center pl-5 pr-5">
                                        <img  style="width: 60px;height: 60px;"  id="avatar" :src="appDomain +'/'+ form.img" onerror="javascript:this.src='/images/user-grey.png'"/>
                                    </div>
                                    <div class="form-group box">
                                        <label>Foto del usuario</label>
                                        <span class="help">La imagen tiene que ser en formato 1x1 (cuadrada)</span>
                                        <div class="input-with-icon right">
                                            <input id="img"  @change="previewImg($event)" name="img" type="file" accept="image/*" class="form-control" style="height:45px !important">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <legend><translate>Permisos</translate></legend>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><translate>Grupo</translate></label><br>
                                        <span class="help" v-translate>Define las secciones del menú autorizadas</span>
                                        <select name="group_id"  :class="{'input': true, 'is-invalid': errors.has('group_id') }"  v-validate="'required'"  v-model="form.group_id" id="group_id"  class=" md-6 form-control" >
                                            <option v-for="(group , index) in dataFormUser.groups" :key="index" :value="group.id">{{ group.name }}</option>
                                        </select>
                                        <div class="invalid-feedback"  v-show="errors.has('group_id')" >{{ errors.first('group_id') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><translate>Rango</translate></label><br>
                                        <span class="help" v-translate>Define los derechos del usuario</span>
                                        <br>
                                        <div class="form-check d-inline-block mt-1 mr-3" v-for="(role , index) in dataFormUser.roles" :key="index">
                                            <label class="form-check-label">
                                                <input class="form-check-input" name="role_id" type="radio" :value="role.id" :class="{'input': true, 'is-invalid': errors.has('role_id') }" v-model="form.role_id" v-validate="'required'" >{{role.name}}
                                            </label>
                                        </div>
                                        <div class="invalid-feedback"  v-show="errors.has('role_id')" >{{ errors.first('role_id') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="alert alert-warning" role="alert">
                                        <tr>
                                            <td>
                                                <b><i class="icon-feather-info mr-2"></i></b>
                                            </td>
                                            <td>
                                                <b v-translate>Admin</b> : <translate>Consulta, Creacíon, modificación, eliminación</translate><br>
                                                <b v-translate>Usuario</b> : <translate>Consulta, Creacíon, modificación</translate><br>
                                                <b v-translate>Externo</b> : <translate>Consulta, Creacíon</translate>
                                            </td>
                                        </tr>
                                    </div>
                                </div>
                               
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><translate>Donantes Asociados</translate></label><br>
                                        <span class="help" v-translate>Seleccione los donantes a los cual el usuario tendrá acceso, si esta opción se deja vacia tendra acceso a todos los proyectos ligados a estos donantes.</span>
                                        <el-select name="donors" v-model="form.donors" multiple filterable placeholder="...">
                                            <el-option
                                                v-for="(item, indexProj ) in donors"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id" >
                                            </el-option>
                                        </el-select>
                                        <div class="invalid-feedback"  v-show="errors.has('donors')" >{{ errors.first('donors') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <el-checkbox v-model="form.conta_data" name="show_data_cont" :true-label="1" :false-label="0">¿Mostrar información contable?</el-checkbox>
                                    </div>
                                </div>
                            </div>

                            <!-- <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <el-checkbox v-model="form.action_delete" name="action_delete" :true-label="1" :false-label="0">¿Eliminar información?</el-checkbox>
                                    </div>
                                </div>
                            </div> -->

                            <legend class="d-none"><translate>Menú Personalizado</translate></legend>
                            
                            <div class="form-group ">
                                <span class="help" v-translate>Modifica el menú del usuario</span>
                                <br>
                                <ul class="treeview" id="treeview">
                                    <li v-for="(groups, index) in dataFormUser.privilegesPanel">
                                        <template v-for="(menus, group) in groups">
                                            <span v-text="group"></span>
                                            <ul>
                                                <li v-for="(menu, id) in menus" >
                                                    <input type="checkbox" v-model="form.privilegesPanel" :value="menu.id">
                                                    <i :class="menu.icon"></i>
                                                    <span v-text="menu.name"></span>
                                                </li>
                                            </ul>
                                        </template>
                                    </li>
                                </ul>
                            </div>

                            <div class="form-buttons-w">
                                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                    <span v-show="loading"><translate>Guardando</translate>...</span>
                                    <span v-show="!loading"><translate>Guardar</translate></span>
                                </button>
                                <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'UserList'})" v-translate> Cancelar</button>
                            </div>
                        </div>
                    </div>
                </form>
              
            </div>
		</div>
	</div>
</template>

<script>
    import {api} from "../../../config/jwt-routes";
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                loading : true,
                users :[],
                dataFormUser:{
                    treeview:'',
                    privilegesPanel:[],
                    roles:[],
                    groups:[]
                },
                user:null,
                form : {
                    date_admission:null,
                    user:null,
                    name:null,
                    email:null,
                    password:'',
                    phone:'',
                    role_id:1,
                    group_id:2,
                    menu_ids:null,
                    img:null,
                    privilegesPanel:[],
                    notificacion:1,
                    conta_data:false,
                    donors:[],
                    action_delete:false
                },
                projects : [],
                donors : []
            }
        },      
        methods:{          
            // Get treeview and groups list
            data:async function(){
                var vm = this;
                await axios.get(api.dataUser)
                .then(function (response) {
                    if(response.data.success){
                        vm.dataFormUser.treeview = response.data.treeview;
                        vm.dataFormUser.privilegesPanel = response.data.treeview_;
                        vm.dataFormUser.groups = response.data.groups;
                        vm.dataFormUser.roles = response.data.roles;
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
            },
            // Show Preview avatar in event change
            previewImg(input){
                var vm = this;
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#avatar').attr("src" , e.target.result);
                }
                reader.readAsDataURL(input.target.files[0]);                
            },
            // Show User data for Edit
            showItem:async function( user ){
                var vm = this;                 
                if(user){
                    await axios.post(api.showUser, {user: user })
                    .then(function (response) {
                        if(response.data.success){
                            vm.form = response.data.user
                                vm.form.menu_ids.forEach(function (value, key) {                            
                                $( '#module_' + value ).attr('checked', 'checked');
                            });
                            vm.form.phone = (response.data.phone == null) ? '' : response.data.phone;
                            vm.form.password ='';
                            vm.form.privilegesPanel = response.data.user.menu_ids;
                        }else{
                            toastr.error(response.data.msg);
                        }
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                    })
                }
            },
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {                    
                    let formData = new FormData(document.getElementById('formUser'));
                    var redirectToList = false;
                    if (result) {
                        if(!this.$route.params.id){                           
                            var apiRoute = api.createUser;
                            var redirectToList = true;                         
                        }else{
                            vm.form.id= this.$route.params.id;
                            formData.append( 'id' , this.form.id )
                            var apiRoute = api.updateUser;
                        }
                        
                        formData.append( 'user' , this.form.user )
                        formData.append( 'name' , this.form.name )
                        formData.append( 'email' , this.form.email )
                        formData.append( 'date_admission' , this.form.date_admission )
                        formData.append( 'phone' , this.form.phone )
                        formData.append( 'group_id' , this.form.group_id )
                        formData.append( 'role_id' , this.form.role_id )
                        formData.append( 'img' , document.getElementById('img').files[0] )
                        formData.append( 'notificacion' , this.form.notificacion )
                        formData.append( 'privilegesPanel' , this.form.privilegesPanel )
                        formData.append( 'donors' , this.form.donors )
                        formData.append( 'show_data_cont' , this.form.conta_data )
                        formData.append( 'action_delete' , this.form.action_delete )
                        axios.post(apiRoute, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);                               
                                if(redirectToList){
                                    vm.$router.push({name:"UserList"})
                                }else{
                                    setTimeout(() => {
                                        window.scrollTo(0, 0);
                                    }, 100);
                                }
                            }else{
                                toastr.error(response.data.msg);
                            }                      
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                        })

                    }
                })
            },
            dataProyects:async function(){
                var vm = this;     
                await axios.post(api.dataReport)
                .then(function (response) {
                    if(response.data.success){
                      vm.projects = response.data.projects;
                    } else {                      
                      toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log('Catch : '+error);
                })
            },
            dataDonors:async function(){
                var vm = this;     
                await axios.post(api.dataReportDonors)
                .then(function (response) {
                    if(response.data.success){
                      vm.donors = response.data.optionsDonors;
                    } else {                      
                      toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log('Catch : '+error);
                })
            }
        },
        mounted: async function() { 
            await this.data();
            if(this.$route.params.id){
                await this.showItem(this.$route.params.id);                
            }
            $('.select2').select2();
            this.loading = false
            await this.dataProyects();
            await this.dataDonors();
        }
    }

</script>
