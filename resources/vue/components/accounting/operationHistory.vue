<template>
	<div class="content-box operations">
        <div class="element-wrapper"> 
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">
                <default-header></default-header>
            </h6>
            <!-- DATEPICKER RANGE FILTER -->
            <div class="element-filters mb-4" v-if="ready">
                <h6 class="element-header mb-3" v-translate>Filtros</h6>
                <div class="row">
                    <div class="col-md-12">
                        <form class="form-inline ">
                            <div class="row" style="width: 100%">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Periodo</label><br>
                                        <el-date-picker
                                            style="width: 100%;"
                                            v-model="range"
                                            type="daterange"
                                            @change="reloadTable()"
                                            range-separator="-"
                                            format="dd/MM/yyyy"
                                            value-format="yyyy-MM-dd"
                                            start-placeholder="Desde"
                                            end-placeholder="Hasta">
                                        </el-date-picker>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
		    <!-- DataTable -->
		    <div class="element-box">
                <div class="table-responsive"  style="zoom: 90%;">
                    <table class="table table-padded"  id="operationHistory"  width="100%">                       
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
                    moment().startOf('year').format('YYYY-MM-DD'),
                    moment().endOf('month').format('YYYY-MM-DD')
                ],
                datatable: null,
                loading : true
            }
        },
		methods: {
            // RELOAD DATATABLE
            reloadTable: async function() {
                let vm = this
                this.datatable.ajax.reload();
                            
            },
            deleteOperationH(id){
                swal({
                    title: "Seguro ?",
                    html: ("Estas a punto de eliminar todos los registros relacionados a este layout, deseas continuar?"),
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: '<translate>Confirmar</translate>',
                    cancelButtonText: '<translate>Cancelar</translate>',
                }).then((confirm) => {
                    if (confirm.value) {
                        var vm = this;
                        axios.post(api.historyOperationDelete, {id:id})
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
		},
        mounted: async function(){
            var vm = this;
            // FILTERS
            //DATATABLE
            vm.datatable = $('#operationHistory').DataTable({
                columns: [
                    {
                        data: 'id',
                        visible:false
                    },
                    { 
                        data: 'date',
                        title: 'Fecha',
                        className: 'font-weight-bold'
                    },
                    { 
                        data: 'status',
                        title: 'Estatus',
                        className: 'font-weight-bold',
						render: function(data, type, row, meta) {
                            if( data == '1' ){
                                return '<span class="text-success">Activo</span>'+
                                '<br><span class="smaller">Realizado por : ' + row.name + '</span>'+
                                '<br><span class="smaller">El día: ' + row.date + '</span> ';
                            }else{
                                return '<span class="text-danger">Borrado</span>'+
                                '<br><span class="smaller">Realizado por : ' + row.name_delete + '</span>'+
                                '<br><span class="smaller">El día: ' + row.date_delete + '</span> ';
                                '<br><span class="smaller">Creado por : ' + row.name + '</span>'+
                                '<br><span class="smaller">Creado el día: ' + row.date + '</span> ';
                            }
                        } 
                    },
                    { 
                        data: 'original_name',
                        title: 'Layout',
                        className: 'font-weight-bold'
                    },
					{ 
                        data: 'import_key',
                        title: 'import key',
                        className: 'small'
                    },				
                    { 
                        data: 'id',
                        title: 'Acciones',
                        className: 'lighter',
                        render: function(data, type, row, meta) {                            
                            return '<div class="row-actions">'+                                        
                                        '<a title="Eliminar Registro" class="option danger operation-history tip"  href="#"  data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                                    '</div>';
                        } 
                    }
                   
                ],
                ajax: {
                    url: api.historyOperation,
                    type: "POST",                    
                    async:true,
                    data: function(){ 
						 return {
                            from:vm.range[0],
                            to:vm.range[1]
                        }; 
					}
                },              
                order: [[ 0, 'asc' ]],
            });
            vm.ready = true
          

            // OPTIONS EVENTS  EDIT & DELETE
            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if($(this).hasClass('operation-history')){
                    vm.deleteOperationH(id);
                }
                
            })
            vm.loading = false;
        }
    }
</script>