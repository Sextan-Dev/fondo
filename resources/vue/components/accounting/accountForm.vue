<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">           
            
            <h6 class="element-header"> 
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'AccountList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Todas las Cuentas</translate> </button>
            </h6>
            <div class="element-box">
                <!-- Form Users New & Edit-->
                <form  enctype="multipart/form-data" id="form" @submit.prevent="formSubmit"  novalidate="true">
                    <div class="row">   
                        <div class="col-sm-12">                                                       
                            <h5 v-if="$route.params.id" class="form-header"> <i class="icon-feather-edit mr-1"></i> <translate>Editar Movimiento</translate></h5>
                            <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i> <translate>Nueva Cuenta</translate></h5>
                            <hr>            
                             <div class="row">
                                <div class="col-md-9">

                                    <legend><translate>Datos de la Cuenta</translate></legend>

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Nombre</label>   
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="os-icon os-icon-wallet-loaded"></i></div>
                                                    </div>
                                                    <input type="text" name="name" v-model="form.name" class="form-control"  placeholder="..." value="" v-validate="'required|min:3'" :class="{'input': true, 'is-invalid': errors.has('name') }">
                                                    <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Clave</label>
                                                <input type="text" :disabled="($route.params.id ? true : false )" :class="{'input': true, 'is-invalid': errors.has('slug') }" v-validate="'required|min:3'" v-model="form.slug"  name="slug" class="form-control md-6"  placeholder="..." value="">
                                                <div class="invalid-feedback"  v-show="errors.has('slug')" >{{ errors.first('slug') }}</div>  
                                            </div>
                                        </div>                              
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label v-translate>Banco</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-university"></i></div>
                                                    </div>
                                                    <input type="text"  :class="{'input': true, 'is-invalid': errors.has('bank') }" v-validate="'required|min:3'" v-model="form.bank" name="bank" class="form-control md-6"  placeholder="..." value="">
                                                    <div class="invalid-feedback" v-show="errors.has('bank')" >{{ errors.first('bank') }}</div>
                                                </div>                                            
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="mb-0" v-translate>Numero de cuenta</label><br>                                            
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="os-icon os-icon-fingerprint"></i></div>
                                                    </div>
                                                    <input type="text" :class="{'input': true, 'is-invalid': errors.has('account') }"  v-validate="'required|numeric|numeric'" v-model="form.account" name="account" class="form-control md-6" placeholder="...">
                                                    <div class="invalid-feedback" v-show="errors.has('account')" >{{ errors.first('account') }}</div>
                                                </div>
                                            </div>
                                        </div>  
                                        <div class="col-md-5">
                                            <div class="form-group ">
                                                <label v-translate>SWIFT/IBAN</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-university"></i></div>
                                                    </div>
                                                    <input type="text" :class="{'input': true, 'is-invalid': errors.has('swift') }"  v-validate="'numeric|min:10|max:10'" v-model="form.swift" name="swift" class="form-control md-6" placeholder="..." value="">
                                                    <div class="invalid-feedback" v-show="errors.has('swift')" >{{ errors.first('swift') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-5">
                                            <div class="form-group ">
                                                <label v-translate>CLABE/IBAN</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" >
                                                        <div class="input-group-text"><i class="fa fa-university"></i></div>
                                                    </div>
                                                    <input type="text" :class="{'input': true, 'is-invalid': errors.has('iban') }"  v-validate="'numeric'" v-model="form.iban" name="iban" class="form-control md-6" placeholder="..." value="">
                                                    <div class="invalid-feedback" v-show="errors.has('iban')" >{{ errors.first('iban') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                                      
                                    </div>
                                </div>
                                <div class="col-md-3">

                                    <legend><translate>Imagen</translate></legend>
                                    <div class="text-center pl-5 pr-5">
                                        <img  style="width: 80%"  id="avatar" :src="appDomain +'/'+ form.img" onerror="javascript:this.src='/images/bank.png'"/>
                                    </div>
                                    <div class="form-group box">
                                        <label>Imagen del Banco</label>
                                        <div class="input-with-icon right">
                                            <input id="img"  @change="previewImg($event)" name="img" type="file" accept="image/*" class="form-control" style="height:45px !important">
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="form-buttons-w">
                                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                    <span v-show="loading"><translate>Guardando</translate>...</span>
                                    <span v-show="!loading"><translate>Guardar</translate></span>
                                </button>
                                <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'AccountList'})" v-translate> Cancelar</button>
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
                form:{
                    name:'',
                    slug:'',
                    bank:'',
                    swift:'',
                    account:'',
                    iban:'',
                    img:''
                }           
            }
        },      
        methods:{
            // Show User data for Edit
            showItem:async function( item ){
                var vm = this;
                if(item){
                    await axios.post(api.showAccount, {item: item })
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
                            var apiRoute = api.createAccount;
                            var redirectToList = true;                         
                        }else{
                            vm.form.id= this.$route.params.id;
                            formData.append( 'id' , this.form.id )
                            var apiRoute = api.updateAccount;
                        }
                        
                        formData.append( 'name' , this.form.name )
                        formData.append( 'slug' , this.form.slug )
                        formData.append( 'bank' , this.form.bank )
                        formData.append( 'swift' , this.form.swift )
                        formData.append( 'account' , this.form.account )
                        formData.append( 'iban' , this.form.iban )
                        formData.append( 'img' , document.getElementById('img').files[0] )
                    
                        axios.post(apiRoute, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);                               
                                if(redirectToList){
                                    vm.$router.push({name:"AccountList"})
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
            if(this.$route.params.id){
                await this.showItem(this.$route.params.id);                
            }
            this.loading = false
        }
    }

</script>
