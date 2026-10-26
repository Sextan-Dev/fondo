<template>
	<div class="content-box operations">
		<div class="element-wrapper">            
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'OperationForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nueva Operacion</translate> </button>                    
            </h6>                                      
       
            <!-- DataTable -->
            <div class="element-box" style="    max-width: 1100px;">
                <div class="table-responsive" style="zoom: 70%;">
                    <table class="table table-padded"  id="operationList"  width="100%" >                       
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
                month: moment().format('YYYY-MM'),
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
                monitoring:0
            }
        },
       
        methods: {
            resetFormUpload:function(){
                this.uploadFail = false
                $('input[type=file]').val('') 
            },                                        
            // UPDATE ACTIVE 1 -> 0
            deleteItem(id){
                swal({
                    title: "<translate>Seguro ?</translate>",
                    html: "<translate>Estas a punto de eliminar este registro, deseas continuar?</translate>",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: '<translate>Confirmar</translate>',
                    cancelButtonText: '<translate>Cancelar</translate>',
                }).then((confirm) => {
                    if (confirm.value) {
                        var vm = this;
                        axios.post(api.deleteOperation, {id:id})
                        .then(async function (response) {
                            if(response.data.success){
                                vm.datatable.ajax.reload();
                                swal({ html : response.data.msg, type: "success", timer: 1500 });
                            }else{
                                swal.close();
                                toastr.error(response.data.msg);
                            }                             
                        })
                        .catch(function (error) {
                            console.log(error);
                        })
                    } else {
                        swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                    }
                });
            },
            replicateItem(id){
                swal({
                    title: "<translate>Seguro ?</translate>",
                    html: "<translate>Estas a punto de duplicar este registro, deseas continuar?</translate>",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: '<translate>Confirmar</translate>',
                    cancelButtonText: '<translate>Cancelar</translate>',
                }).then((confirm) => {
                    if (confirm.value) {
                        var vm = this;
                        axios.post(api.replicateOperation, {id:id})
                        .then(async function (response) {
                            if(response.data.success){
                                vm.datatable.ajax.reload();
                                swal({ html : response.data.msg, type: "success", timer: 1500 });
                            }else{
                                swal.close();
                                toastr.error(response.data.msg);
                            }                             
                        })
                        .catch(function (error) {
                            console.log(error);
                        })
                    } else {
                        swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                    }
                });
            },
            // RELOAD DATATABLE
            reloadTable: async function() {
                let vm = this
                $(vm.datatable.columns(2).header()).text('Saldo contable del ' + moment().subtract(1, 'months').format('YYYY-MM'));;
                this.datatable.ajax.reload();                                            
            }
        },
        mounted: async function(){
            var vm = this;
            // FILTERS            
            //DATATABLE
            vm.datatable = $('#operationList').DataTable({
                columns: [                   
                    {
                        data: 'project_id',
                        visible:false
                    },
                    {
                        data: 'donor',
                        title: 'Donante',
                        className: 'font-weight-bold'
                    },                                                                      
                    { 
                        data: 'donor',
                        title: 'Donante',
                        className: 'font-weight-bold'
                    },                                                                      
                    { 
                        data: 'category',
                        title: 'Fuente',
                        render: function(data, type, row, meta) {
                            return data  + ' ' + row.subcategory;
                        }
                    },                 
                    { 
                        data: 'project',
                        title: 'Proyecto',
                        
                    },                    
                    { 
                        data: 'date_from',
                        title: 'Inicio',
                         render: function(data, type, row, meta) {
                            return (data ? moment(data).format('DD/MM/YYYY') : '-')
                        }
                       
                    },
                    { 
                        data: 'date_to',
                        title: 'Fin',
                        render: function(data, type, row, meta) {
                            return (data ? moment(data).format('DD/MM/YYYY') : '-')
                        }
                    },
                    { 
                        data: 'amount_invertion',
                        title: 'Costo Original',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'monitoring',
                        title: 'Costo Operativo',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_1',
                        title: 'Enero',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_2',
                        title: 'Febrero',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_3',
                        title: 'Marzo',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_4',
                        title: 'Abril',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_5',
                        title: 'Mayo',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_6',
                        title: 'Junio',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_7',
                        title: 'Julio',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_8',
                        title: 'Agosto',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_9',
                        title: 'Septiembre',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_10',
                        title: 'Octubre',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_11',
                        title: 'Noviembre',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'month_12',
                        title: 'Diciembre',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    { 
                        data: 'monitoring_year',
                        title: 'Amortización Anual',
                        render: function(data, type, row, meta) {
                            return '$'+vm.formatPrice(data);
                        }
                    },
                    
                                                
                   
                ],
                ajax: {
                    url: api.selectOperationDonorsMonitoring,
                    type: "POST",                    
                    async:true,
                    data: function(){ 
                        return {                        
                            month:vm.month   
                        }                     
                    }
                },              
                order: [[ 1, 'asc' ]],
             
                initComplete:async function( settings, json){                    
                    vm.ready = true;     
                    await setTimeout(function() {vm.datatable.columns.adjust();}, 50)
                }, 
            });


            vm.ready = true
          
            
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