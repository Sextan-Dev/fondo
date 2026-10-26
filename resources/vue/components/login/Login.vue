<template>
    <div class="auth-box-w">
        <div class="logo-w">
            <img alt="Fondo Unido México" :src="appDomain+'/images/logo-impactr.png'">
        </div>
        <h4 class="auth-header">
          <translate>Iniciar sesión</translate>
        </h4>

        <!-- Login Form -->
        <form  @submit.prevent="login">
            <div class="form-group">
                <label for="user" v-translate>Usuario</label>
                <div class="auth-input">
                    <i class="os-icon os-icon-user-male-circle"></i>
                    <input
                        type="text"
                        name="user"
                        placeholder="Tu usuario"
                        class="form-control"
                        :class="{'input': true, 'is-invalid': errors.has('user') }"
                        id="user"
                        v-model="form.user"
                        autocomplete="username"
                        v-validate="'required'"
                    />
                </div>
                <div class="invalid-feedback"  v-show="errors.has('user')" >{{ errors.first('user') }}</div>
            </div>
            <div class="form-group">
                <label v-translate for="password">Contraseña</label>
                <div class="auth-input">
                    <i class="os-icon os-icon-fingerprint"></i>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        :class="{'input': true, 'is-invalid': errors.has('password') }"
                        id="password"
                        v-model="form.password"
                        placeholder="••••••"
                        autocomplete="current-password"
                        v-validate="'required'"
                    />
                </div>
                <div class="invalid-feedback"  v-show="errors.has('password')" >{{ errors.first('password') }}</div>
            </div>

            <div class="auth-forgot">
                <router-link :to="{name:'ForgotPassword'}"><translate>¿Olvidaste tu contraseña?</translate></router-link>
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                    <span v-translate v-show="loading">Entrando...</span>
                    <span v-translate v-show="!loading">Entrar</span>
                </button>
            </div>
        </form>
        <!-- End Login Form -->

        <span class="copyright">Powered by <img :src="appDomain+'/images/logo_sextan_grey.png'" /></span>

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
                    user: null,
                    password: null
                }
            }
        },
        // Define Methods
        methods: {
            // Import Action of module auth.js
            ...mapActions([
                    'setAuthUser',
                    'setPrivilegesUser'
            ]),
            // Login Method
            login(){
                let vm = this
                this.loading = true;
                // Validate Form
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        axios.post(api.login, this.form)
                        //If success
                        .then(res => {
                            
                            let auth = res.data;                         
                            if(auth.success){
                                // Set token
                                jwtToken.setToken(auth.token);                
                                // Set User Auth
                                this.setAuthUser(auth.user);

                                // Redirect to index with user auth
                                this.$router.push({name: 'index'});
                            }else{
                                
                                // Display Login Error
                                toastr.error(auth.error);
                                this.loading = false;
                            }                        
                        })
                        //If error
                        .catch(err => {
                            console.log(err.response)
                            if(err.response.status === 401){
                                toastr.error(err.response.data.msg);
                            }
                        
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