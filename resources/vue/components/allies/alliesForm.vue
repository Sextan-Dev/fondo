<template>
	<div class="content-box" >
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'AlliesList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todos los Aliados</translate> </button>
            </h6>

            <div class="element-box">
                <!-- Form Users New & Edit-->
                 <form id="form" @submit.prevent="formSubmit"  novalidate="true">
                    <div class="row">   
                        <div class="col-sm-12">
                            
                            <h5 v-if="$route.params.id" class="form-header"> <i class="icon-feather-edit mr-1"></i> <translate>Modificar datos del Aliado</translate></h5>
                            <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i> <translate>Nuevo Aliado</translate></h5>
                            <hr> 

                            <div class="row">
                                <div class="col-md-9">

                                    <legend><translate>Datos Generales</translate></legend>

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Razon Social</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                                    </div>
                                                    <input type="text" v-model="form.name" :class="{'input': true, 'is-invalid': errors.has('name') }" v-validate="'required|min:3'"  name="name" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                                </div>    
                                            </div>
                                        </div>  
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre Comercial</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                                    </div>
                                                    <input type="text" v-model="form.name_comercial" :class="{'input': true, 'is-invalid': errors.has('name_comercial') }" v-validate="'required|min:3'"  name="name_comercial" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback"  v-show="errors.has('name_comercial')" >{{ errors.first('name_comercial') }}</div>  
                                                </div>    
                                            </div>
                                        </div>  
                                    </div>  
                                    <div class="row">  
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre Completo</label>   
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >                                                        
                                                        <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                    </div>
                                                    <input v-model="form.first_name" type="text" name="company"  class="form-control"  placeholder="..." value=""  >
                                                </div>
                                            </div>
                                        </div>                                      
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre de Dirección Ejecutiva</label>   
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >                                                        
                                                        <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                    </div>
                                                    <input v-model="form.name_executive" type="text" name="name_executive"  class="form-control"  placeholder="..." value=""  >
                                                </div>
                                            </div>
                                        </div>                                      
                                        
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>RFC</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                    </div>
                                                    <input v-model="form.id_number" :class="{'input': true, 'is-invalid': errors.has('id_number') }" v-validate="''"  name="id_number" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback"  v-show="errors.has('id_number')" >{{ errors.first('id_number') }}</div>  
                                                </div>    
                                            </div>
                                        </div>                                                          
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Correo de Dirección Ejecutiva</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-mail"></i></div>
                                                    </div>
                                                    <input  type="email"  :class="{'input': true, 'is-invalid': errors.has('email') }" v-validate="'required|email'" v-model="form.email" name="email" class="form-control md-6"  placeholder="..." value="">
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
                                                    <input type="text" :class="{'input': true, 'is-invalid': errors.has('phone') }"  v-validate="''" v-model="form.phone" name="phone" class="form-control md-6" placeholder="..." value="">
                                                    <div class="invalid-feedback" v-show="errors.has('phone')" >{{ errors.first('phone') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group ">
                                        <label v-translate>Direccion</label>
                                        <div class="input-group">
                                              <div class="input-group-prepend" >
                                                <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                            </div>
                                            <input v-model="form.address" type="text" :class="{'input': true, 'is-invalid': errors.has('address') }"   name="address" class="form-control md-6" placeholder="..." value="">
                                            <div class="invalid-feedback" v-show="errors.has('address')" >{{ errors.first('address') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label v-translate>Estado de OSC</label>
                                        <el-select v-model="form.state" filterable name="state" default-first-option placeholder="..">
                                            <el-option v-for="item in optionsStates" :key="item.value" :label="item.value" :value="item.value">
                                            </el-option>
                                        </el-select>
                                        <!-- Error Message -->
                                        <div class="invalid-feedback" v-show="errors.has('zone_impact_state')">
                                            {{ errors.first('zone_impact_state') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group ">
                                        <label v-translate>Ciudad</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" >
                                                <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                            </div>
                                            <input v-model="form.country" type="text" :class="{'input': true, 'is-invalid': errors.has('country') }"   name="country" class="form-control md-6" placeholder="..." value="">
                                            <div class="invalid-feedback" v-show="errors.has('phone')" >{{ errors.first('country') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group ">
                                        <label v-translate>Codigo Postal</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" >
                                                <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                            </div>
                                            <input v-model="form.postal" type="text" :class="{'input': true, 'is-invalid': errors.has('postal') }"  v-validate="'numeric'" name="postal" class="form-control md-6" placeholder="..." value="">
                                            <div class="invalid-feedback" v-show="errors.has('postal')" >{{ errors.first('postal') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="">
                                            <span v-translate>Comentarios</span>
                                            <span class="help" v-translate>Este campo es opcional</span>
                                        </label><br>
                                        <textarea v-model="form.comment" name="comment" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-buttons-w">
                                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                    <span v-show="loading"><translate>Guardando</translate>...</span>
                                    <span v-show="!loading"><translate>Guardar</translate></span>
                                </button>
                                <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'Directory'})" v-translate> Cancelar</button>
                            </div>
                        </div>
                    </div>
                </form>
              
            </div>
		</div>
	</div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
	export default {
        props:['appDomain','siteName', 'auth','configPlugins'],
        data(){
            return {
                loading : false,
                optionsStates:[],
                optionsBrands:[],
                optionsAreas:[],
                form : {
                    type:'personal',
                    name:null,
                    category:'Aliado',
                    company:null,
                    email:null,
                    phone:'',
                    age:'',
                    gender:'',
                    address:'',
                    country:'',
                    postal:'',
                    comment:''
                }         
            }
        },  
        methods:{
            // Get treeview and groups list
            data:async function(){
                var vm = this;
                await axios.get(api.dataContact)
                .then(function (response) {
                    if(response.data.success){
                        vm.optionsStates = response.data.states
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
            },
            
            // Show User data for Edit
            showItem:async function( id ){
                var vm = this;                 
                if(id){
                    await axios.post(api.showContact, {id: id })
                    .then(function (response) {                        
                        if(response.data.success){
                            vm.form = response.data.data
                            vm.form.areas_interest = JSON.parse(vm.form.areas_interest)
                            vm.form.brands = JSON.parse(vm.form.brands)
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
                    var redirectToList = false;
                    if (result) {
                        vm.loading=true;
                        if(!this.$route.params.id){
                            axios.post(api.storeContact, this.form )
                            .then(async function (response) {
                                if(response.data.success){
                                    toastr.success(response.data.msg);  
                                    vm.$router.push({name:"AlliesList"})
                                }else{
                                    toastr.error(response.data.msg);
                                }                      
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                console.log(error);
                            })                 
                        }else{
                            vm.form.id= this.$route.params.id;
                            axios.post(api.updateContact, this.form )
                            .then(async function (response) {
                                if(response.data.success){
                                    toastr.success(response.data.msg);
                                    setTimeout(() => {
                                        window.scrollTo(0, 0);
                                    }, 100);
                                }else{
                                    toastr.error(response.data.msg);
                                }                      
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                console.log(error);
                            })
                        }
                        vm.loading=false;
                    }
                })
            }
        },
        mounted: async function() {
            await this.data();           
            if(this.$route.params.id){
                await this.showItem(this.$route.params.id);                
            }
            this.loading = false
        }
    }

</script>
<style>
    .el-select {
        width: 100%!important;
    }
    .el-date-editor {
        width: 100%!important;
    }
</style>
