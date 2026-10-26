<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">            
            <h6 class="element-header"> 
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'GoodsAndServices'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todas los Bienes y Servicios</translate> </button>
            </h6>
            <div class="element-box">
                <!-- Form Users New & Edit-->
                <form  enctype="multipart/form-data" id="form" @submit.prevent="formSubmit"  novalidate="true">
                    <div class="row">   
                        <div class="col-sm-12">                                                       
                            <h5 v-if="$route.params.id" class="form-header"> <i class="icon-feather-edit mr-1"></i> <translate>Editar Bien y/o Servicio</translate></h5>
                            <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i> <translate>Nuevo Bien y/o Servicio</translate></h5>
                            <hr>            
                             <div class="row">
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Tipo</label><br>

                                                <div class="form-check d-inline-block mt-1 mr-3">
                                                    <label class="form-check-label bigger mt-0">
                                                        <input name="type" type="radio"  v-model="form.type" class="form-check-input" value="good"  checked="checked" ><translate>Bien</translate>
                                                    </label>
                                                </div>
                                                <div class="form-check d-inline-block mt-1 mr-3">
                                                    <label class="form-check-label bigger mt-0">
                                                        <input name="type" type="radio" v-model="form.type" class="form-check-input " value="service" ><translate>Servicio</translate>
                                                    </label>
                                                </div>
                                               
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre del Bien y/o Servicio</label>   
                                                
                                                <input type="text" name="article" v-model="form.article" class="form-control"  placeholder="..." value="" v-validate="'required|min:3'" :class="{'input': true, 'is-invalid': errors.has('article') }">
                                                <div class="invalid-feedback"  v-show="errors.has('article')" >{{ errors.first('article') }}</div>  
                                            
                                            </div>
                                        </div>
                                         <div class="col-md-5">
                                            <div class="form-group ">
                                                <label v-translate>Categoria</label>
                                                <v-select   name="category" placeholder="..." 
                                                            v-model="form.category"
                                                            :options="optionsCategories"
                                                            taggable
                                                            :class="{'input': true, 'is-invalid': errors.has('category') }"> 
                                                </v-select>
                                                <!-- Error Message -->
                                                <div class="invalid-feedback" v-show="errors.has('category')" >
                                                    {{ errors.first('category') }}
                                                </div> 
                                            </div>
                                        </div>
                                         <!-- <div class="col-md-4">
                                            <div class="form-group">
                                                <label v-translate>Clave</label>
                                                <input type="text" v-validate="''" v-model="form.slug"  name="slug" class="form-control md-6"  placeholder="..." value="">
                                                <div class="invalid-feedback"  v-show="errors.has('slug')" >{{ errors.first('slug') }}</div>  
                                            </div>
                                        </div>     -->
                                        
                                    </div>
                                    <div class="row">
                                        <div class="col-md-9">
                                            <div class="form-group">
                                                <label v-translate>Descripción</label><br>
                                                <textarea :class="{'input': true, 'is-invalid': errors.has('description') }"  placeholder="..." name="description" class="form-control mb-0" v-model="form.description" rows="3"></textarea>
                                                <div class="invalid-feedback" v-show="errors.has('description')" >{{ errors.first('description') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group ">
                                                <label v-translate>Precio</label>
                                                
                                                <input type="number" :class="{'input': true, 'is-invalid': errors.has('price') }"  v-validate="'required|numeric'" v-model="form.price" name="price" class="form-control md-6" placeholder="..." value="">
                                                <div class="invalid-feedback" v-show="errors.has('price')" >{{ errors.first('price') }}</div>
                                             
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group ">
                                                <label v-translate>Costo</label>
                                                
                                                <input type="number" :class="{'input': true, 'is-invalid': errors.has('cost') }"  v-validate="'numeric'" v-model="form.cost" name="cost" class="form-control md-6" placeholder="..." value="">
                                                <div class="invalid-feedback" v-show="errors.has('cost')" >{{ errors.first('cost') }}</div>
                                             
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group">
                                                <label v-translate>Comentario</label><br>
                                                <textarea  placeholder="..." name="description" class="form-control mb-0" v-model="form.comment" rows="3"></textarea>                                                
                                            </div>
                                        </div>
                                                      
                                    </div>
                                </div>
                                
                            </div>
                            <div class="form-buttons-w">
                                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                    <span v-show="loading"><translate>Guardando</translate>...</span>
                                    <span v-show="!loading"><translate>Guardar</translate></span>
                                </button>
                                <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'GoodsAndServices'})" v-translate> Cancelar</button>
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
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                loading : true,
                optionsCategories:[],
                form:{
                    article:'',
                    slug:'',
                    description:'',
                    type:'good',
                    category:'',
                    price:'',
                    comment:'',
                    cost:''
                }           
            }
        },      
        methods:{
            // GET FILTERS & COUNTS
            data:async function(){
                var vm = this;
                await axios.post(api.dataGoodAndServices)
                .then(function (response) {
                    if(response.data.success){
                        vm.optionsCategories = response.data.categories
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                     toastr.error(error);
                    console.log(error);
                })
            },
            // Show User data for Edit
            showItem:async function( item ){
                var vm = this;
                if(item){
                    await axios.post(api.showGoodAndServices, {item: item })
                    .then(function (response) {                        
                        if(response.data.success){
                            vm.form = response.data.item                                           
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
             // Show Preview avatar in event change
            previewImg(input){
                var vm = this;
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#avatar').attr("src" , e.target.result);
                }
                reader.readAsDataURL(input.target.files[0]);                
            },
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {                    
                    let formData = new FormData(document.getElementById('form'));
                    var redirectToList = false;
                    if (result){
                        if(!this.$route.params.id){                           
                            var apiRoute = api.storeGoodAndServices;
                            var redirectToList = true;                         
                        }else{
                            vm.form.id= this.$route.params.id;
                            formData.append( 'id' , this.form.id )
                            var apiRoute = api.updateGoodAndServices;
                        }
                        
                     // Append vue data in Form data
                        for ( var key in this.form ) {
                            // check if is a select and extract the value
                            if(_.isObject( this.form[key] ) ){
                                if(_.has( this.form[key], 'id' )){
                                        formData.append( key , this.form[key].id )
                                }else{
                                    if(_.has( this.form[key], 'value' )){
                                        formData.append( key , this.form[key].value )
                                    }else{
                                        formData.append( key , this.form[key].label )
                                    }
                                }
                            }else{
                                formData.append(key, this.form[key])
                            }
                        }
                    
                        axios.post(apiRoute, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);                               
                                if(redirectToList){
                                    vm.$router.push({name:"GoodsAndServices"})
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
            }
        },
        mounted: async function() {
            this.data()
            if(this.$route.params.id){
                await this.showItem(this.$route.params.id);                
            }
            this.loading = false
        }
    }

</script>
