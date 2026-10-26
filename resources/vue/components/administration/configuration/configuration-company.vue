<template>
    <div class="element-box p-3">
        <form id="formCompany" @submit.prevent="formSubmit">
            <h5 class="form-header"><i class="fa fa-building-o m-r-10 mr-2"></i> <translate>Datos de la Organización</translate></h5>
            <hr>
            <div class="row">
                <div class="col-md-9">
                    <legend><translate>Datos Generales</translate></legend>

                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Razon Social</label>   
                            
                                <input type="text" name="name" v-model="form.name" class="form-control"  placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('name') }">
                                <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                            
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>RFC</label>   
                            
                                <input type="text" name="rfc" v-model="form.rfc" class="form-control"  placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('rfc') }">
                                <div class="invalid-feedback"  v-show="errors.has('rfc')" >{{ errors.first('rfc') }}</div>  
                            
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Dirección</label>   
                                <input type="text" name="address" v-model="form.address" class="form-control"  placeholder="..." value="">                            
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Ciudad</label>   
                            
                                <input type="text" name="city" v-model="form.city" class="form-control"  placeholder="..." value="" >
                            
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label v-translate>Código Postal</label>   
                            
                                <input type="number" name="post_code" v-model="form.post_code" class="form-control"  placeholder="..." value="">
                            
                            </div>
                        </div>
                    </div>
                </div>
            
                <div class="col-md-3">
                    <legend><translate>Logo</translate></legend>
                    <div class="text-center pl-5 pr-5">
                        <img  style="max-width: 90%;"  id="preview" :src="appDomain +'/'+ form.logo_main" onerror="javascript:this.src='/images/company.png'"/>
                    </div>
                    <div class="form-group box">
                        <label>Logo de la Empresa</label><br>
                        <span class="help" v-translate>El logo tiene que ser formato png</span>
                        <div class="input-with-icon right">
                            <input id="img"  @change="previewImg($event)" name="img" v-validate="'image|ext:png'" type="file" accept="image/*" :class="{'input': true, 'is-invalid': errors.has('img') }" class="form-control" style="height:45px !important" >
                            <div class="invalid-feedback"  v-show="errors.has('img')" >{{ errors.first('img') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- SUBMIT BUTTONS -->
            <div class="form-buttons-w">
                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                    <span v-show="loading"><translate>Guardando</translate>...</span>
                    <span v-show="!loading"><translate>Guardar</translate></span>
                </button>
            </div>
        </form>
    </div>
</template>
<script>
    
    import {api} from "../../../config/jwt-routes"
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                loading : false,
                form:{
                    name:null,
                    rfc:null,
                    address:null,
                    city:null,
                    post_code:null,
                    logo_main:null,
                    rewards:false,
                }
            }
        },
      
        methods:{
            data:async function() {
                let vm = this
                axios.post( api.showCompany )
                .then(function(response){
                    console.log(response.data.company)
                    vm.form = response.data.company
                })
                .catch(function(error){
                    toastr.error(response.data.msg);
                })
            },
            // Show Preview avatar in event change
            previewImg(input){
                var vm = this;
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview').attr("src" , e.target.result);
                }
                reader.readAsDataURL(input.target.files[0]);                
            },
            formSubmit:async function() {
                let vm = this
                this.$validator.validateAll().then((result) => {
                    if (result){
                        vm.loading = true
                        let formData = new FormData(document.getElementById('formCompany'));
                        axios.post(api.updateCompany, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg)
                            }else{
                                toastr.error(response.data.msg)
                            }
                            vm.loading = false
                        })
                        .catch(function (error) {
                            vm.loading = false
                            toastr.error(error);
                            console.log(error);
                        })
                    }
                })
            }
        },
        mounted:  async function() {
            let vm = this
            await vm.data()
        }
    }

</script>