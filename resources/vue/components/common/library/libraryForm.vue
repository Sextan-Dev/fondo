<template>
	<div class="content-box" >
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'Library'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todos los Documentos</translate> </button>
            </h6>

            <div class="element-box">
                <!-- Form Users New & Edit-->
                <form id="form" enctype="multipart/form-data" @submit.prevent="formSubmit"  novalidate="true">
                    <div class="row">   
                        <div class="col-sm-12">
                            <h5 class="form-header"><i class="icon-feather-plus mr-1"></i><translate>Nuevo Documento</translate></h5>
                            <hr> 

                            <div class="row">
                                <div class="col-md-9">                                    

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre</label>
                                                <span class="help" v-translate>ej. carta del municipio del</span>
                                                <div class="input-group">
                                                    <input type="text" v-model="form.name" :class="{'input': true, 'is-invalid': errors.has('name') }" v-validate="'required|min:3'"  name="name" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                                </div>    
                                            </div>
                                        </div>  
                                       <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate >Categoria</label>
                                                <v-select  v-model="form.category" :class="{'input': true, 'is-invalid': errors.has('category') }"  v-validate="'required'"  :options="optionsCategory" placeholder="..." name="category" taggable></v-select>
                                                <div class="invalid-feedback" v-show="errors.has('category')" >{{ errors.first('category') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-5">                                         
                                            <div class="form-group">
                                                <label v-translate>Documento</label>
                                                <span class="help" v-translate>Seleccione el documento que desea cargar</span>
                                                <input id="files"  name="files" type="file" class="form-control"  :class="{'input': true, 'is-invalid': errors.has('files') }" v-validate="'required'" >
                                                <div class="invalid-feedback" v-show="errors.has('files')" >{{ errors.first('files') }}</div>                                              
                                            </div>                        
                                        </div>                                
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label v-translate>Compartir con algunos Usuarios</label><br>
                                        <span class="help" v-translate>Seleccione los usuarios o deje el campo en blanco para permitir el acceso a todos los usuarios.</span>
                                        <v-select  v-model="form.share_user_ids"  multiple :options="optionsShareUsers" placeholder="..." name="share_user_ids" ></v-select>
                                    </div>
                                </div>
                            </div>
                            <div class="row" >
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
                                <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'Library'})" v-translate> Cancelar</button>
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
                optionsShareUsers:[],
                form : {
                    name:null,
                    share_user_ids:null,
                    category:null,
                    comment:''
                }         
            }
        },
        methods:{
            // Get treeview and groups list
            data:async function(){
                var vm = this;
                await axios.post(api.dataLibrary , {type:'file'})
                .then(function (response) {
                    if(response.data.success){                       
                        vm.optionsShareUsers = response.data.users
                        response.data.categories.forEach(function(value, key){
                            if(value.name!='TODO'){
                                vm.optionsCategory.push({'value': value.value , 'label' : value.name})
                            }
                        })
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
            },
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading=true;
                        let formData = new FormData(document.getElementById('form'));

                        
                        // Append vue data in Form data
                        for ( var key in this.form ) {
                            // check if is a select and extract the value
                            if(_.isObject( this.form[key] ) ){
                                if(_.has( this.form[key], 'value' ) ){
                                    formData.append( key , this.form[key].value )
                                }else{
                                    formData.append( key , this.form[key].label )                                   
                                }
                            }else{
                                formData.append(key, this.form[key])
                            }

                        }
                        let share_user_ids = ''
                        if(_.isArray( this.form.share_user_ids ) ){ 
                            this.form.share_user_ids.forEach(function(item, key){
                                share_user_ids =  item.value + ',' + share_user_ids
                            })
                        }
                        formData.append('share_user_ids', share_user_ids)
                        axios.post(api.storeLibrary, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);  
                                vm.$router.push({name:"Library"})
                            }else{
                                toastr.error(response.data.msg);
                                vm.loading=false; 
                            }                     
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                        })                 
                        
                    }
                })
            }
        },
        mounted: async function(){
            await this.data();
            this.loading = false
        }
    }

</script>
