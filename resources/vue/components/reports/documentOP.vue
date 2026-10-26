<template>
	<div class="content-box" >
		<div class="element-wrapper print-ready">

      <div class="element-actions p-3">
          <button onClick="window.print()" class="btn btn-success"> <i class="icon-feather-printer"></i> </button>
      </div>
      <h6 class="element-header">
          <span  class="default-title"> <i class="icon-feather-file-text mr-1"></i> <translate>Orden de Pago</translate></span>                    
          <!-- <button @click.prevent="$router.push({name:'OrderPayList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Listado de Ordenes</translate> </button> -->
      </h6>
      
      <div class="invoice-w" v-if="ready" id="print-wrapper">
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
            <h5>PROVEEDOR</h5>
            <div class="company-name mb-0">
              {{provider.name}}
            </div>
            <div class="company-extra">
              <b>RFC:</b> {{provider.id_number||'-'}}<br>
              <b>Email:</b> {{provider.email}}<br>
              <b>Telefono:</b> {{provider.phone}}<br>
              <b>Banco:</b> {{provider.bank}}<br>
              <b>Numero de Cuenta:</b> {{provider.bank_account}}<br>
              <b>Cable Interbancaria:</b> {{provider.bank_clabe}}
            </div>
            <div class="company-address">
              <b>Dirección:</b> {{provider.address}}<br>{{provider.city}}{{ "CP: "+(provider.postal||'-')}}
            </div>
            
          </div>
        </div>
        <div class="row">
          
          <div class="col-6 mb-4 mt-4"  v-if="operation">
            <h4>Empresa Donante</h4>
              <div class="invoice-date">
                <span class="bolder">Razon Social :</span> {{ (operation.company== '') ? operation.name : operation.company}}<br>
                <span class="bolder">RFC:</span> {{operation.id_number}}<br>
                <span class="bolder">Email:</span> {{operation.email}}<br>
                <span class="bolder">Telefono:</span> {{operation.phone}}<br>
                <span class="bolder">Ciudad:</span> {{operation.city}}<br>
                <span class="bolder">Dirección:</span> {{operation.address}}<br>
                <span class="bolder">C.P.:</span> {{operation.postal}} <br>
                <span class="bolder">Procedencia de Donativos:</span> {{operation.concept}} 
              </div>
          </div>
          <div class="col-6  mb-4 mt-4">
            <h4>
              Orden de Pago <br><small class="ml-1 text-grey">Nº {{content.name}}</small>
            </h4>
            <div class="invoice-date" v-if="operation">
              <span class="bolder">Fecha:</span> {{content.date}}<br>
              <span class="bolder">Programa:</span> {{content.program}}<br>
              <span class="bolder">Proyecto:</span> {{content.project}}<br>
              <span class="bolder">Actividad:</span> {{content.task}}<br>
              <span class="bolder">Concepto:</span> {{operation.concept}}            
            </div>
          </div>
        </div>
        
        <div class="invoice-body">
          <div class="invoice-table" style="width:100%">
            <table class="table">
              <thead>
                <tr>
                  <th>Cant.</th>
                  <th>Descripción</th>
                  <th class="text-center">IVA</th>
                  <th class="text-right">Costo</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in rows" :key="index" >
                  <td class="bolder">{{(item.qt)?item.qt:0}}</td>
                  <td class="smaller">{{(item.article=='' || item.article == null)?  (  (item.comment == '' || item.comment == null) ? '-' : item.comment  ): item.article}}</td>
                  <td class="text-right">$ {{ (item.apply_vat) ? ( parseFloat( item.cost) * parseFloat(0.16) ) : 0}}</td>                 
                  <td class="text-right bolder">${{parseFloat(Number(item.cost)).toFixed(0)}}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="smaller">
                  <td class="border-0"></td>
                  <td>SubTotal</td>
                  <td class="text-right" colspan="2">
                    $ {{sumCost.toLocaleString('en')}}
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
            
          </div>
        </div>
        <div class="terms">
          <div class="row">
            <div class="col-md-6">
              <div class="terms-header bolder" v-if="content.public" >
                Público Objetivo                          
              </div>
              <div class="terms-content mb-3" v-if="content.public">
                <ul>
                  <li class="mr-2" >{{content.public}}</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="terms-header bolder" v-if="content.program" >
                Programas de Inversión                         
              </div>
              <div class="terms-content mb-3" v-if="content.program">
                <ul>
                <li class="mr-2" >{{program}}</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="terms-header bolder" v-if="content.comment!=''">
            Comentarios
          </div>
          <div class="terms-content mb-3" v-if="content.comment!=''">
            {{content.comment}}
          </div>
          <div class="terms-header bolder">
            Terminos & Condiciones
          </div>
          <div class="terms-content mb-3" >
            <ul>
            <li>Esta solicitud debe incluir la guía del proyecto, presupuesto, anexo de autorización Diario Oficial, factura o recibo, dos cotizaciones.</li>
            <li>Los montos superiores a $ 100,000.00 (cien mil pesos 00/100 M.N.) deben ser autorizados por el Presidente del Patronato</li>
          </ul>  
          </div>
        </div>
        <div class="row">
          <div style="border-top: 1px solid;" class="col-5 text-center  col-centered  bolder mt-5 m-b2">
            <span style="font-family: sans-serif;" class="mt-4 smaller">Solicitante: {{content.user_created}}</span>
          </div>  
          <div style="border-top: 1px solid;" class="col-5 text-center  col-centered  bolder mt-5 mb-2">            
            <span style="font-family: sans-serif;" class="mt-4 smaller">Aprovada por {{content.user_approved}}</span>
          </div>   
        </div>
        <div class="row">
           <div style="border-top: 1px solid;" class="col-5 text-center col-centered bolder mt-5">
            <span style="font-family: sans-serif;" class="mt-4 smaller">Administración (Nombre y Firma)</span>

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
                ready:false,
                loading : true,
                program:'',
                client:[],
                provider:[],
                content:[],
                vats: [],              
                curDate: moment().format('DD/MM/YYYY'),                
                rows:[],     
                sumCost: 0,
                sumVat: 0,
                operation:null,
                sumTotal: 0
            }
        },       
        methods:{ 
            show:async function(){
                let vm = this
                await axios.post(api.showOrderPay, {item: vm.$route.params.id  })
                .then(async function (response) {
                    if(response.data.success){
                        vm.content = response.data.item 
                        vm.rows = response.data.rows
                        vm.provider = response.data.contact
                        vm.operation = response.data.operation
                        vm.sumCost = 0
                        vm.sumVat = 0
                       _.each(vm.rows, function(value, key){
                            vm.sumCost = parseFloat(vm.sumCost) + ( parseFloat(Number(value.cost)) * parseFloat(Number(value.qt)) )
                            vm.sumVat = parseFloat(vm.sumVat) + ( parseFloat(Number(value.cost))* 0.16 * parseFloat(Number(value.qt)) )
                        });

                        if(!vm.content.apply_vat){
                          vm.sumVat = 0.00
                        }
                        vm.sumTotal = vm.sumCost + vm.sumVat;
                        
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
         // vm.$route.params.id = 17
          //PROJECT REPORT
          if (vm.$route.params.id) {           
            await this.show();
            vm.loading = false;
          }else{
            vm.$router.push({name:'PageNotFound'})
          }

          vm.ready = true
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
