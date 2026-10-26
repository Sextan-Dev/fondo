<template>
    <div class="auth-box-w">
        <div class="logo-w">
            <img alt="Fondo Unido México" :src="appDomain+'/images/logo_main.png'">
        </div>
        <h4 class="auth-header">
          <translate>Reestablece tu Contraseña</translate>
        </h4>
        <!-- Login Form -->
        <form  @submit.prevent="resetPassword">

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

            <div class="form-group">
                <label for="password" v-translate>Contraseña</label>
                <div class="auth-input">
                    <i class="os-icon os-icon-fingerprint"></i>
                    <input
                        type="password"
                        name="password"
                        placeholder="Mínimo 8 caracteres"
                        class="form-control"
                        :class="{'input': true, 'is-invalid': errors.has('password') }"
                        id="password"
                        v-model="form.password"
                        autocomplete="new-password"
                        ref="password"
                        v-validate="'required|min:8'"
                    />
                </div>
                <div class="invalid-feedback"  v-show="errors.has('password')" >{{ errors.first('password') }}</div>
            </div>
            <div class="form-group">
                <label for="password_confirmation" v-translate>Confirma tu Contraseña</label>
                <div class="auth-input">
                    <i class="os-icon os-icon-fingerprint"></i>
                    <input
                        type="password"
                        name="password_confirmation"
                        placeholder="Repite la contraseña"
                        class="form-control"
                        :class="{'input': true, 'is-invalid': errors.has('password_confirmation') }"
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        v-validate="'required|confirmed:password'"
                    />
                </div>
                <div class="invalid-feedback"  v-show="errors.has('password_confirmation')" >{{ errors.first('password_confirmation') }}</div>
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                    <span v-translate v-show="loading">Actualizando...</span>
                    <span v-translate v-show="!loading">Actualizar Contraseña</span>
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
    import jwtToken from "../../helpers/jwt-token";
    import {api} from "../../config/jwt-routes";
    import {mapActions} from "vuex";
    import {siteName,appDomain} from '../../config/app';
    // Export Data & Methods 
    export default {
        // Set data
        data() {
            return {
                appDomain:appDomain,
                loading: false,
                form: {
                    // the emailed link carries it as ?email=
                    email: this.$route.query.email || null,
                    password: null,
                    password_confirmation: null,
                    token:this.$route.params.token
                }
            }
        },
        // Define Methods
        methods: {

            // Login Method
            resetPassword(){
                this.loading = true;
                var vm = this;
                // Validate Form
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        axios.post(api.resetPassword, this.form)                        
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
                                // on a warning stay here, so a mistyped email can be fixed without filling the form again
                                if(response.data.success){
                                    vm.$router.push({name: 'login'});
                                }
                            });
                            this.loading = false;                                
                        })
                        //If error
                        .catch(err => {
                            // `response` doesn't exist here: the ReferenceError left the button disabled for good.
                            // A 422 from Laravel's validation carries `message` rather than `msg`.
                            toastr.error(err.response ? (err.response.data.msg || err.response.data.message) : err.message);
                            this.loading = false;
                        });
                        
                    }else{
                        this.loading = false;
                    }
                })
            }
        }
    }
</script>