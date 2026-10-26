<template>
	<div class="content-box" >
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'Directory'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todos los Contactos</translate> </button>
            </h6>

            <div class="element-box">
                <!-- Form Users New & Edit-->
                <form enctype="multipart/form-data" id="formDir"   @submit.prevent="formSubmit"  novalidate="true">
                    <div class="row">   
                        <div class="col-sm-12">
                            
                            <h5 v-if="$route.params.id" class="form-header"> <i class="icon-feather-edit mr-1"></i> <translate>Modificar Contacto</translate></h5>
                            <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i> <translate>Nuevo Contacto</translate></h5>
                            <hr> 

                            <div class="row">
                                <div class="col-md-9">

                                    <legend><translate>Datos Generales</translate></legend>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Categoria</label><br>
                                                <span class="help">Define la categoria a la que pertencera el contacto</span>
                                                <v-select  v-model="form.category" :class="{'input': true, 'is-invalid': errors.has('category') }"  v-validate="'required'"  :options="optionsCategory" placeholder="..." name="category" taggable></v-select>
                                                <div class="invalid-feedback" v-show="errors.has('category')" >{{ errors.first('category') }}</div>
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
                                                    <input type="text" v-model="form.name" :class="{'input': true, 'is-invalid': errors.has('name') }" v-validate="'required|min:3'"  name="name" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                                </div>    
                                            </div>
                                        </div>  
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Empresa</label>   
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                                    </div>
                                                    <input v-model="form.company" type="text" name="company"  class="form-control"  placeholder="..." value=""  >
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>RFC</label>   
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                                    </div>
                                                    <input v-model="form.id_number" type="text" name="id_number"  class="form-control"  placeholder="..." value=""  >
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Edad</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                    </div>
                                                    <input type="number" v-model="form.age" :class="{'input': true, 'is-invalid': errors.has('age') }" v-validate="'numeric'"  name="age" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback"  v-show="errors.has('age')" >{{ errors.first('age') }}</div>  
                                                </div>    
                                            </div>
                                        </div>  
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Genero</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                    </div>
                                                    <select  v-model="form.gender"  name="gender" class="form-control md-6">
                                                        <option value="m"><translate>M</translate></option>
                                                        <option value="f"><translate>F</translate></option>
                                                    </select>
                                                </div>    
                                            </div>
                                        </div>                           
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Correo</label>
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
                                                    <input type="text" :class="{'input': true, 'is-invalid': errors.has('phone') }"  v-validate="'numeric|min:10|max:10'" v-model="form.phone" name="phone" class="form-control md-6" placeholder="..." value="">
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
                                <div class="col-md-5">
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
                                <div class="col-md-5">
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

                            <div class="row">
                                <legend><translate>Datos Contables</translate></legend>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">Banco</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" >
                                                <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                            </div>
                                            <input type="text" v-model="form.bank" :class="{'input': true, 'is-invalid': errors.has('bank') }" v-validate="''"  name="bank" class="form-control md-6"  placeholder="..." value="">
                                            <div class="invalid-feedback"  v-show="errors.has('bank')" >{{ errors.first('bank') }}</div>  
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">Numero de Cuenta</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" >
                                                <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                            </div>
                                            <input type="number" v-model="form.bank_account" :class="{'input': true, 'is-invalid': errors.has('bank_account') }" v-validate="''"  name="bank_account" class="form-control md-6"  placeholder="..." value="">
                                            <div class="invalid-feedback"  v-show="errors.has('bank_account')" >{{ errors.first('bank_account') }}</div>  
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">Clabe Interbancaria</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" >
                                                <div class="input-group-text"><i class="fa fa-building-o "></i></div>
                                            </div>
                                            <input type="number" v-model="form.bank_clabe" :class="{'input': true, 'is-invalid': errors.has('bank_clabe') }" v-validate="'numeric'"  name="bank_clabe" class="form-control md-6"  placeholder="..." value="">
                                            <div class="invalid-feedback"  v-show="errors.has('bank_clabe')" >{{ errors.first('bank_clabe') }}</div>  
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <legend><translate>Información Complementaria</translate></legend>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">
                                            <span v-translate>Adjuntos(s)</span>
                                            <span class="help" v-translate>Seleccione el o los documentos que crea necesarios.</span>
                                        </label><br>
                                        <div class="" v-if="filesList">
                                            <table class="table table-lightborder">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" v-translate>Adjuntos</th>
                                                        <th ></th>
                                                        <th ></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for=" (item, index ) in filesList" :key="index">
                                                        <td class="text-left"><div class="status-pill green" data-title="Complete" data-toggle="tooltip" data-original-title="" title=""></div>&nbsp;{{ item.file }}</td>
                                                        <td class="text-left">
                                                            <a target="_blank" :href="appDomain+'/uploads/contacts/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                                        </td>
                                                        <td class="text-left row-actions"><a class=" option  danger" href="#" @click.prevent="removeFile(index) "><i class="icon-feather-trash-2"></i></a> </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            </div>
                                        <input name="files[]" type="file" multiple="" class="form-control" >
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
    import {api} from "../../../config/jwt-routes";
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                loading : true,
                optionsCategory:[],
                filesList : null,
                form : {
                    name:null,
                    company:null,
                    email:null,
                    phone:'',
                    age:'',
                    gender:'',
                    address:'',
                    country:'',
                    postal:'',
                    category:null,
                    comment:'',
                    files:[]
                }         
            }
        },
        methods:{
            
            // Show User data for Edit
            showItem:async function( id ){
                var vm = this;                 
                if(id){
                    await axios.post(api.showContact, {id: id })
                    .then(function (response) {
                        console.log(response.data);
                        if(response.data.success){
                            vm.form = response.data.data
                            let files = JSON.parse(vm.form.files)
                            if(_.isObject(files)){
                                vm.filesList = files
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
            },
            removeFile:function(index){
                this.filesList.splice(index, 1)
                //this.deleteFile( '/uploads/operations/'+ this.filesList[index]['file'] , index, this.filesList)
            },
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    var redirectToList = false;
                    if (result) {
                        vm.loading=true;
                         
                        let formData = new FormData(document.getElementById('formDir'));
                          // Append vue data in Form data
                        for ( var key in this.form ) {
                            // check if is a select and extract the value
                            if(_.isObject( this.form[key] ) && _.has( this.form[key], 'value' )  ){
                                formData.append( key , this.form[key].value )
                            }else{
                                formData.append(key, this.form[key])
                            }
                        }
                       
                        if(!this.$route.params.id){
                            axios.post(api.storeContact, formData)
                            .then(async function (response) {
                                if(response.data.success){
                                    toastr.success(response.data.msg);  
                                    vm.$router.push({name:"Directory"})
                                    vm.loading=false;
                                }else{
                                    toastr.error(response.data.msg);
                                    vm.loading=false;
                                }                      
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                console.log(error);
                                vm.loading=false;
                            })                 
                        }else{
                            formData.append('filesOld',JSON.stringify(this.filesList))
                            vm.form.id= this.$route.params.id;
                            axios.post(api.updateContact, formData)
                            .then(async function (response) {
                                if(response.data.success){
                                    let files = JSON.parse(response.data.data.files)
                                    if(_.isObject(files)){
                                        vm.filesList = files
                                    }
                                    toastr.success(response.data.msg);
                                    setTimeout(() => {
                                        window.scrollTo(0, 0);
                                    }, 100);
                                    vm.loading=false;
                                }else{
                                    toastr.error(response.data.msg);
                                    vm.loading=false;
                                } 
                                $('input[type=file]').val('')                      
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                console.log(error);
                                vm.loading=false;
                            })
                        }
                        
                    }
                })
            },
            getCategories:async function( ){
                var vm = this;    
                await axios.post(api.listCategory)
                .then(function (response) {
                    if(response.data.success){
                        vm.optionsCategory = response.data.listCategory
                    }else{
                        toastr.error("Error, no se pudieron obtener las categorias");
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
            },
        },
        mounted: async function() {                   
            if(this.$route.params.id){
                await this.showItem(this.$route.params.id);                
            }
            console.info("vamos a llamar antes")
            await this.getCategories();
            console.info("despues")
            this.loading = false
        }
    }

</script>
