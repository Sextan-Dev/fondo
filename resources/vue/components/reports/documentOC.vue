<template>
	<div class="content-box" >
		<div class="element-wrapper print-ready">

      <div class="element-actions p-3">
          <button onClick="window.print()" class="btn btn-success"> <i class="icon-feather-printer"></i> </button>
      </div>
      <h6 class="element-header">
          <span  class="default-title"> <i class="icon-feather-file-text mr-1"></i> <translate>Orden de Cobro</translate></span>                    
          <!-- <button @click.prevent="$router.push({name:'OrderPayList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Listado de Ordenes</translate> </button> -->
      </h6>
      
      <div class="invoice-w" id="print-wrapper">
        <div class="infos">
          <div class="info-1" style="max-width: 46%;">
            <div class="invoice-logo-w">
              <img :src="appDomain +'/images/logo_main.png?r='+ (Math.floor(Math.random() * (10)))" onerror="javascript:$(this).hide()" width="70%">
            </div>
            <div class="company-name">
              {{auth.company.name}}
            </div>
            <div class="company-address">
              {{auth.company.address}}<br>{{auth.company.city}}<br>{{auth.company.post_code}}
            </div>
            <div class="company-extra">
              <b>RFC:</b> {{auth.company.rfc}}
            </div>
          </div>
          <div class="info-2">
            <h5>DONADOR</h5>
            <div class="company-name mb-0">
              {{provider.name}}
            </div>
            <div class="company-extra">
              <b>RFC:</b> {{provider.id_number}}<br>
              <b>Email:</b> {{provider.email}}<br>
              <b>Telefono:</b> {{provider.phone}}<br>
            </div>
            <div class="company-address">
              <b>Dirección:</b> {{provider.address}}<br>{{provider.city}}{{"CP: "+provider.postal||'-'}}
            </div>
          </div>
        </div>
        <div class="invoice-heading">
          <h3>
            Orden de Cobro <small class="ml-1 text-grey">Nº {{content.name}}</small>
          </h3>
          <div class="invoice-date">
             Fecha: {{content.date}}<br>
             Proyecto: {{content.project}}<br>
             Actividad: {{content.task}}<br>
             Concepto: {{content.concept}}            
          </div>
        </div>
        <div class="invoice-body">
          <div class="invoice-desc">
            <div class="desc-label">
             
            </div>
            <div class="desc-value">
            </div>
          </div>
          <div class="invoice-table">
            <table class="table">
              <thead>
                <tr>
                  <th>Cant.</th>
                  <th>Descripción</th>
                  <th class="text-center">IVA</th>
                  <th class="text-right">Precio</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in rows" :key="index" >
                  <td class="bolder">{{(item.qt)?item.qt:0}}</td>
                  <td class="smaller">{{(item.comment=='')?'-':item.comment}}</td>
                  <td class="text-right">$ {{  ( parseFloat( item.amount) * parseFloat(0.16) )}}</td>                 
                  <td class="text-right bolder">${{parseFloat(Number(item.amount)).toFixed(0)}}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="smaller">
                  <td class="border-0"></td>
                  <td>SubTotal</td>
                  <td class="text-right" colspan="2">
                    $ {{sumAmount.toLocaleString('en')}}
                  </td>
                </tr>
                <tr class="smaller border-top-0">
                  <td class="border-0"></td>
                  <td>IVA</td>
                  <td class="text-right" colspan="2">
                     $ {{sumVat.toLocaleString('en')}}                     
                  </td>
                </tr>
                <tr class="border-top-0 bolder text-right">
                  <td class="border-0"></td>
                  <td>Total</td>
                  <td colspan="2">
                    ${{sumTotal.toFixed(2).toLocaleString('en')}}
                  </td>
                </tr>
              </tfoot>
            </table>
            <div class="terms">
              <div class="terms-header" v-if="content.comment!=''">
                Comentario
              </div>
              <div class="terms-content mb-3" v-if="content.comment!=''">
                {{content.comment}}
              </div>
              <div class="terms-header ">
                Terminos & Condiciones
              </div>
              <div class="terms-content">
                Orden de compra por pagar según los terminos y condiciones acordados con el cliente.
              </div>
            </div>
          </div>
        </div>
        <div class="invoice-footer">
          <div class="invoice-logo">
            <img :src="auth.company_logo" height="50px"> 
          </div>
          <!-- <div class="invoice-info">
            <span>facturacion@20f.com</span><span>55 4323 1921</span>
          </div> -->
        </div>
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
                client:[],
                provider:[],
                content:[],  
                vats: [],              
                curDate: moment().format('DD/MM/YYYY'),                
                rows:[],     
                sumAmount: 0,
                sumVat: 0,
                sumTotal: 0
            }
        },       
        methods:{ 
            show:async function(){
                let vm = this
                await axios.post(api.showOrderCollect, {item: vm.$route.params.id  })
                .then(async function (response) {
                    if(response.data.success){
                        vm.content = response.data.item     
                       
                        vm.rows = response.data.rows
                        vm.provider = response.data.contact
                        vm.sumAmount = 0
                        vm.sumVat = 0
                       _.each(vm.rows, function(value, key){
                            vm.sumAmount = parseFloat(vm.sumAmount) + ( parseFloat(Number(value.amount)) * parseFloat(Number(value.qt)) )
                            vm.sumVat = parseFloat(vm.sumVat) + ( parseFloat(Number(value.amount))* 0.16 * parseFloat(Number(value.qt)) )
                        });
                        vm.sumTotal = vm.sumAmount + vm.sumVat;
                        
                    }else{
                        toastr.error(response.data.msg);
                    }        
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })                 
            },
        },
        mounted: async function() {

          var vm = this;
          //vm.$route.params.id = 27
          //PROJECT REPORT
          if (vm.$route.params.id) {           
            await this.show();
            vm.loading = false;
          }else{
            vm.$router.push({name:'PageNotFound'})
          }
        }
    }

</script>

<style>
@media print {
  .el-tablo.padded {
    padding: 1rem;
  }
  .activity-box {
    max-width: 60%;
    overflow: hidden;
  }
  .support-index .support-ticket-content-w {
    display: block !important;
  }
}
</style>
