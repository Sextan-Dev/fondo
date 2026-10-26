<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">            
            <h6 class="element-header">
                <default-header></default-header>
            </h6>
            
            <div class="alert alert-warning borderless">
                <h5 class="alert-heading" v-translate>Configuración del programa</h5>
                <p v-translate>
                    Aquí puedes modificar la configuración general del programa. Algunos parámetros pueden afectar el funcionanimiento del programa. Es importante tener un buen conocimiento de las consecuencias antes guardar una modificación.
                </p>
            </div>

            <!-- COMPANY CONFIGURATION -->
            <configurationCompany :siteName="siteName" :appDomain="appDomain" :auth="auth" ></configurationCompany>
        
		</div>
	</div>
</template>

<script>
    
    import {api} from "../../../config/jwt-routes";
    import configurationCompany from './configuration-company'
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                loading : true
            }
        },
        components:{
            configurationCompany : configurationCompany,
        },
        methods:{
           
        },
        
        mounted:  async function() {

            var vm = this;
            vm.loading = false;
            
        }
    }

</script>
