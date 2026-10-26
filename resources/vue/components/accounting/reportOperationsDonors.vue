<template>
	<div class="content-box operations">
		<div class="element-wrapper">            
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'OperationForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nueva Operacion</translate> </button>                    
            </h6>

          
            <!-- DATEPICKER RANGE FILTER -->
            <div class="element-filters mb-4" v-if="ready">
                <h6 class="element-header mb-3" v-translate>Filtros</h6>
                <div class="row">
                    <div class="col-md-12">
                        <form class="form-inline ">
                            <div class="row  align-items-end" style="width: 100%">                               
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Periodo</label><br>
                                        <div class="block">                                            
                                            <el-date-picker
                                            @change="reloadTable()"
                                            v-model="month"
                                            value-format="yyyy-MM"
                                            type="month"
                                            placeholder="Pick a month">
                                            </el-date-picker>
                                            <button @click.prevent="reloadTable()" class="btn btn-sm btn-primary">
                                                <i class="fa fa-refresh"></i>
                                            </button>
                                        </div>                                       
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
       
            <!-- DataTable -->
            <div class="element-box">
                <div class="table-responsive" style="zoom: 70%;">
                    <table class="table table-padded"  id="operationList"  width="100%">                       
                    </table> 
                </div>
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
                ready:false,              
                range:[
                    moment().startOf('month').format('YYYY-MM-DD'),
                    moment().endOf('month').format('YYYY-MM-DD')
                ],
                category: 'all',
                month: '2021-04',//moment().format('YYYY-MM'),
                account:'',
                donor:'all',
                project:'all',
                logFile:'',
                logOperations:[],
                types: [],
                optionsDonors:[],
                optionsProjects:[],
                optionsCategories:[],
                accounts:[],
                datatable: null,
                loading : true,
                uploadFail: false,
                countFail:'',
                countSuccess:'',
                fee:0,
                monitoring:0,
                balanceResult : []
            }
        },
       
        methods: {            
            
            storeBalance(){
                let vm = this
                axios.post(api.storeBalance, {data: vm.balanceResult })
                .then(function (response) {                        
                    if(response.data.success){
                        vm.balanceResult = []                             
                    }else{
                        
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })                   
            },           
          
            // RELOAD DATATABLE
            reloadTable: async function() {
                let vm = this
                $(vm.datatable.columns(2).header()).text('Saldo contable del ' + moment(vm.month).subtract(1, 'months').format('YYYY-MM'));;
                await this.datatable.ajax.reload(); 
                vm.storeBalance()
            }
        },
        mounted: async function(){
            var vm = this;
            // FILTERS            
            //DATATABLE
            vm.datatable = $('#operationList').DataTable({
                columns: [                                                                    
                    { 
                        data: 'donor',
                        title: 'Donante',
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '<a href="'+vm.appDomain + '/operation/donor/'+ row.id  +'" target="_blank">'+data +'&nbsp;<i class="fa fa-external-link"></i></a>';
                        }
                    },                                                                      
                    { 
                        data: 'category',
                        title: 'Fuente',                        
                        // render: function(data, type, row, meta) {
                        //     return '<span class="badge badge-primary">'+data  + ' ' + row.subcategory +'</span>';
                        // }
                    },
                    { 
                        data: 'subcategory',
                        title: 'SubFuente',                        
                        // render: function(data, type, row, meta) {
                        //     return '<span class="badge badge-primary">'+data  + ' ' + row.subcategory +'</span>';
                        // }
                    },
                    { 
                        data: 'balancePrev',
                        title: 'Saldo contable del ' + moment().subtract(1, 'months').format('YYYY-MM'),
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) {                          
                            return  '<span class="text-' + (row.balancePrev > 0 ? 'success' : 'danger') + '" >$'+vm.formatPrice(row.balancePrev)+ '</span>';                            
                        }
                       
                    },
                    { 
                        data: 'sumIncomes',
                        title: 'Total de Ingresos',
                        class:'text-success font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'sumFee',
                        title: 'Fee Retenido',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'sumIncomesFee',
                        title: 'Monto total sin Fee',
                        class:'text-success font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'sumTransfersReceipt',
                        title: 'Traspasos Recibidos',
                        class:'text-success font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'sumTransfersSendings',
                        title: 'Traspasos Otorgados',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data - Number(row.sumRemnants) - Number(row.sumReleases) );
                        }
                       
                    },
                    { 
                        data: 'sumOutcomes',
                        title: 'Total de Egresos',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'monitoring',
                        title: 'Costo Operativo',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+ vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'sumReleases',
                        title: 'Liberaciones',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'sumRemnants',
                        title: 'Remanentes',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '$ '+vm.formatPrice(data);
                        }
                       
                    },
                    { 
                        data: 'id',
                        title: 'Saldo contable',
                        class:'text-danger font-weight-bold',
                        render: function(data, type, row, meta) {
                            let balance
                            let balancePrev
                            let monitoring = row.monitoringsPrev == 0  ? row.monitoring : 0
                            
                            
                           balancePrev = row.balancePrev

                            balance = Number(balancePrev)  + //64 489 
                                    Number(row.sumIncomesFee) +  // 0
                                 
                                    
                                    (
                                        Number(row.sumTransfersReceipt) + 
                                        (row.monitoringTake==0 ? 0: Number(row.sumTransfersSendingsMonitoring) ) 
                                    ) - // 44 980
                                    (
                                        Number(row.sumTransfersSendings) - // 0
                                        Number(row.sumRemnants) - // 0
                                        Number(row.sumReleases) // 0
                                    ) - 
                                    Number(row.sumRemnants) - 
                                    Number(row.sumReleases) - 
                                    Number(row.sumOutcomes) - // 56735.6
                                    
                                     row.monitoringTake  // 8 300

                            vm.balanceResult.push({
                                'date': vm.month,
                                'donor_id': row.id,
                                'balance':balance,
                                'category': row.category,
                                'subcategory': row.subcategory
                            })
                            return  '<span class="font-weight-bold text-' + (balance > 0 ? 'success' : 'danger') + '" monitoring ="'+monitoring+'" pre="'+(balancePrev)+'">$'+vm.formatPrice(balance) + '</span>';
                        }
                       
                    },
                ],
                ajax: {
                    url: api.selectOperationDonors,
                    type: "POST",                    
                    async:true,
                    data: function(){ 
                        return {                        
                            month:vm.month   
                        }                     
                    }
                },              
                order: [[ 0, 'asc' ]], 
                initComplete:async function( settings, json){                    
                    vm.ready = true;
                    await vm.storeBalance()
                },
                drawCallback:async function(){
                    await vm.storeBalance()

                }              
            });
            
                                  
            // OPTIONS EVENTS  EDIT & DELETE
            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.$router.push({name:'OperationForm',params: {id: id } })
                }else if($(this).hasClass('deleteItem')){
                    vm.deleteItem(id);
                }else if($(this).hasClass('replicateItem')){
                    vm.replicateItem(id);
                }
                
            })
            vm.loading = false;
        }
    }
</script>
<style>
    .el-select {
        width: 100%;
    }
    .operations .form-filter-large .value .form-check-label span {
        font-size: 16px;
        font-weight: 700;
    }
 
    .operations .form-filter-large .label {
        font-size: 12px;
    }
    .operations .el-tablo .value {
        font-size: 16px;
    }
    .operations div.dataTables_wrapper div.dataTables_processing{
        position: relative!important;
    }
  
</style>