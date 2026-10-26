<template>
    <div class="auth-box-w">
        <div class="logo-w">
            <img alt="Fondo Unido México" :src="appDomain+'/images/logo_main.png'">
        </div>
        <h4 class="auth-header">
          <translate>Recupera tu Contraseña</translate>
        </h4>
        <!-- Login Form -->
        <form  @submit.prevent="sendLinkForResetPassword">

            <p class="auth-desc" v-translate>Se enviará un email con las instrucciones necesarias para reestablecer tu contraseña</p>

            <div class="form-group">
                <label for="email" v-translate>Email</label>
                <div class="auth-input">
                    <i class="os-icon os-icon-email-2-at2"></i>
                    <input
                        type="email"
                        name="email"
                        placeholder="tu@correo.com"
                        class="form-control"
                        :class="{'input': true, 'is-invalid': errors.has('email') }"
                        id="email"
                        v-model="form.email"
                        autocomplete="email"
                        v-validate="'required|email'"
                    />
                </div>
                <div class="invalid-feedback"  v-show="errors.has('email')" >{{ errors.first('email') }}</div>
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                    <span v-translate v-show="loading">Enviando...</span>
                    <span v-translate v-show="!loading">Enviar</span>
                </button>
                <router-link :to="{name: 'login'}" class="auth-back"><translate>Volver a iniciar sesión</translate></router-link>
            </div>
        </form>
        <!-- End Login Form -->

        <span class="copyright" v-translate>Powered by <img :src="appDomain+'/images/logo_sextan_grey.png'" /></span>

    </div>
</template>
<script>
    // Import Resources
    import {api} from "../../config/jwt-routes";
    import {siteName,appDomain} from '../../config/app';
    // Export Data & Methods 
    export default {
        // Set data
        data() {
            return {
                appDomain:appDomain,
                loading: false,
                form: {
                    email: null
                }
            }
        },
        // Define Methods
        methods: {

            // Login Method
            sendLinkForResetPassword(){
                this.loading = true;
                var vm = this;
                // Validate Form
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        axios.post(api.sendMailResetPassword, this.form)
                        .then(response => {
                            if(response.data.success){
                                 var type = "success";       
                            }else{
                                var type = "warning"; 
                            }
                            swal({                                
                                html: response.data.msg,
                                type: type,
                            }).then(function() {
                                // on a warning stay here, so a mistyped email can be fixed
                                if(response.data.success){
                                    vm.$router.push({name:"login"});
                                }
                                vm.loading = false;
                            });
                        })
                        //If error
                        .catch(err => {
                            // `response` doesn't exist here: the ReferenceError left the button disabled for good.
                            // A 422 from Laravel's validation carries `message` rather than `msg`.
                            toastr.error(err.response ? (err.response.data.msg || err.response.data.message) : err.message);
                            vm.loading = false;
                        });
                    }else{
                        vm.loading = false;
                    }
                })
            }

        }
    }
</script>