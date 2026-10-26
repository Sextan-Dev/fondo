<template>

    <div class="top-bar color-scheme-default">
        <div class="top-menu-left">
            <a @click="routerBack" class="btn-custom-grey"><i class="icon-feather-chevron-left"></i></a>
            <a @click="$router.forward()" class="btn-custom-grey d-none"><i class="icon-feather-chevron-right"></i></a>
        </div>
        <div class="top-menu-controls">
            <div class="top-icon d-md-inline">
                <a href="#" class="tip" data-target="#formNotificationModal" data-toggle="modal" title="Crear Notificacion">
                    <i class="os-icon os-icon-mail-14"></i>
                </a>               
            </div>
            <div class="top-icon d-none d-md-inline">
                <a href="#" @click.prevent="lockScreen">
                    <i class="icon-feather-lock"></i>
                </a>               
            </div>
            <div class="top-icon d-none d-md-inline">
                <a href="#" @click.prevent="toggleFullScreen">
                    <i id="fullscreenBtn" class="icon-feather-maximize-2"></i>
                </a>               
            </div>
            <div class="top-icon">
                <a href="#" @click.prevent="logout">
                    <i class="icon-feather-power"></i>
                </a>               
            </div>
            <div v-if="$route.path=='/'" class="top-icon content-panel-toggler">
                <a href="#" @click.prevent="togglePanel">
                    <i class="icon-feather-align-right"></i>
                </a>               
            </div>
        </div>

        <div id="lockedScreen" v-if="lockedScreen">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="auth-box-w">
                        <div class="logo-w">
                            <i v-show="loading" class="icon-feather-unlock"></i>
                            <i v-show="!loading" class="icon-feather-lock"></i>
                        </div>
                        <h4 class="auth-header">
                            <translate>Sesíon Bloqueada</translate>
                        </h4>
                        <form @submit.prevent="unlock">
                            <input class="d-none" name="user" v-model="form.user">
                            <div class="form-group">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    id="password"
                                    v-model="form.password"
                                    placeholder="******" 
                                    required   
                                />
                                <div class="invalid-feedback"  v-show="errors.has('password')" >{{ errors.first('password') }}</div>
                                <div class="pre-icon os-icon os-icon-fingerprint"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                                <span v-translate v-show="loading">Desbloqueando...</span>
                                <span v-translate v-show="!loading">Entrar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
          
</template>

<script>
	import {siteName,appDomain} from './../../config/app';
    import {mapState} from 'vuex';
    import jwtToken from '../../helpers/jwt-token';
    import {api} from "../../config/jwt-routes";
    import {mapActions} from "vuex";
    
	export default {
		data() {
			return {
                siteName: siteName,
                appDomain: appDomain,
                loading: false,
                lockedScreen: false,
                form: {
                    user: null,
                    password: null
                }
			}
        },
        computed:{
            ...mapState({
                auth: state => state.auth.user
            })
        },
        mounted: function() {
            
        },
		methods: {
            routerBack() {
                if (localStorage.fromParams && localStorage.fromName) {
                    console.log('FROM-2:'+localStorage.fromParams);
                    var params = JSON.parse(localStorage.fromParams);
                    this.$router.push({name: localStorage.fromName, params: params })
                } else {
                    this.$router.back;
                }
            },
            lockScreen() {
                jwtToken.removeToken();
                this.lockedScreen = true;
            },
            togglePanel() {
                $('.all-wrapper').toggleClass('content-panel-active');
            },
            toggleFullScreen() {
                $('#fullscreenBtn').toggleClass("icon-feather-maximize-2").toggleClass("icon-feather-minimize-2");
                if ((document.fullScreenElement && document.fullScreenElement !== null) ||    
                (!document.mozFullScreen && !document.webkitIsFullScreen)) {
                    if (document.documentElement.requestFullScreen) {  
                    document.documentElement.requestFullScreen();  
                    } else if (document.documentElement.mozRequestFullScreen) {  
                    document.documentElement.mozRequestFullScreen();  
                    } else if (document.documentElement.webkitRequestFullScreen) {  
                    document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);  
                    }  
                } else {
                    if (document.cancelFullScreen) {  
                    document.cancelFullScreen();  
                    } else if (document.mozCancelFullScreen) {  
                    document.mozCancelFullScreen();  
                    } else if (document.webkitCancelFullScreen) {  
                    document.webkitCancelFullScreen();  
                    }  
                }  
            },
            // Import Action of module auth.js
            ...mapActions([
                    'setAuthUser'
            ]),
            // Unlock Method
            unlock(){
                this.loading = true;
                this.form.user = this.auth.user;
                axios.post(api.login, this.form)
                .then(res => {
                    let authNew = res.data;
                    if(authNew.success){
                        jwtToken.setToken(authNew.token);
                        this.setAuthUser(authNew.user);
                        this.lockedScreen = false;
                        this.loading = false;      
                    }else{
                        Toastr.error(authNew.error);
                        this.loading = false;
                    }                        
                })
                .catch(err => {                          
                    Toastr.error('Error');
                    this.loading = false;
                });
            }
        }
	}
</script>

