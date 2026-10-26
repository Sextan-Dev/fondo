<template>
	<div class="content-box" >
		<div class="element-wrapper print-ready">

      <div class="element-actions">
          <form class="form-inline justify-content-sm-end">

                <div class="form-group pt-1">
                    <label v-translate>Año</label> 
                    <el-date-picker
                            v-model="year"
                            type="year"
                            format="yyyy"	
                            value="yyyy"	
                            placeholder="Selecciona un Año">
                    </el-date-picker>
                </div>
                <button onClick="window.print()" class="btn btn-success ml-3"> <i class="icon-feather-printer"></i> </button>
          </form>
      </div>
            
      <h6 class="element-header">        
          <span class="default-title"> <i class="os-icon os-icon-bar-chart-stats-up mr-1"></i> <translate>Reporte de Donadores</translate></span>
      </h6>

      <div class="element-box print-ready" id="print-wrapper">

     
      </div>

		</div>
	</div>
  
</template>

<script>

  import {api} from "../../config/jwt-routes";

	export default {
        props:['auth','appDomain','siteName'],
        data(){
            return {
                loading : true,    
                curDate: moment().format('DD/MM/YYYY'),             
                year: moment().format('YYYY'),    
                donor_id :187,          
            }
        },  
        methods:{
          
          
        },
        mounted: async function() {

            var vm = this;
         
            vm.loading = false;

        }
    }

</script>

