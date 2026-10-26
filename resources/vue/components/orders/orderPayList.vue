<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">            
            <h6 class="element-header">
                <default-header></default-header>        
            </h6>           
           
            <!-- <div class="element-filters mb-4 ">
                <h6 class="element-header mb-3" v-translate>Periodo</h6>
                <div class="row">
                    <div class=" col-sm-6 col-md-4 col-lg-3">
                        <form class="form-inline ">
                            <div class="form-group">
                                <label v-translate>Desde</label>
                                <div class="date-input">
                                    <input type="text" name="from" v-model="from" class="single-date from form-control" placeholder="..." value="" data-vv-as=" " v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('from') }" >
                                    <div class="invalid-feedback" v-show="errors.has('from')" >{{ errors.first('from') }}</div>  
                                </div>
                                <label v-translate>Hasta</label> 
                                <div class="date-input">
                                    <input type="text" name="to" v-model="to" class="single-date to form-control" placeholder="..." value="" data-vv-as=" " v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('to') }" >
                                    <div class="invalid-feedback" v-show="errors.has('to')" >{{ errors.first('to') }}</div>  
                                </div>
                            </div>    
                        </form>
                    </div>
                </div>
            </div> -->
            <div class="element-box">            
                <!-- DataTable -->
                <div class="table-responsive table-small">
                     <table class="table table-padded" id="OrderList" width="100%"></table>                  
                </div>

            </div>
		</div>
	</div>
</template>

<script>
    import {api} from "../../config/jwt-routes";
    import {EventBus} from '../../core/eventBus.js';
	export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                from: moment().startOf('month').format('YYYY-MM-DD'),
                to: moment().endOf('month').format('YYYY-MM-DD'),
                items:[],
                loading : true,
                datatable:null,
                categories: [],
                type:'',
                etat:'',
                category:'',
                userAuth:null,
                types:[],
            }
        },
        methods:{
             data:async function(){
                var vm = this;
               
                await axios.post(api.dataOrderPay,{ task_id : null, project_id:null })
                .then(function (response) {
                    if(response.data.success){                
                        vm.userAuth = response.data.userAuth
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
            },
            deleteItem(item){
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
                        axios.post(api.deleteOrderPay, {item:item})
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
                        swal({title : "<translate>Operation Annulée</translate>", type : "error", timer:1200});
                    }
                });
            },           
            validateContent:async function(id){ 

                let vm = this;

                const {value: valid} = await swal({
                    title: "<translate>Aceptar o Rechazar ?</translate>",
                    input:'radio',
                    inputOptions: {
                        'approved' : '<b>ACEPTAR</b>',
                        'rejected' : '<b>RECHARZAR</b>'
                    },
                    html: "<translate>Puedes acompañar tu respuesta con un mensaje explicativo : </translate><br><br><textarea name='comment' class='form-control' id='validateComment' placeholder='Mensaje...'></textarea><br>",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: '<translate>Confirmar</translate>',
                    cancelButtonText: '<translate>Cancelar</translate>',
                });

                if (valid) {
                    let com = document.getElementById('validateComment').value;
                    axios.post(api.utilsUpdatePriority, {id:id, priority:valid, comment:com})
                    .then(async function (response) {
                        if(response.data.success){
                            swal({ html : 'Validación Registrada', type: "success", timer: 1500 });
                            vm.datatable.ajax.reload();   
                        }else{
                            toastr.error(response.data.msg);
                        }                             
                    })
                    .catch(function (error) {
                        console.log(error);
                        toastr.error(error);
                    })
                } else {
                    swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                }

			},
            reloadTable : function() {
                this.datatable.ajax.reload();
            }
        },
        mounted: async function(){
            var vm = this;          
            await vm.data()
            //DATATABLE
            vm.datatable = $('#OrderList').DataTable({
                columns: [                    
                    { 
                        data: 'id',
                        title: '<translate>id</translate>',
                        visible:false
                    }, 
                    { 
                        data: 'date',
                        title: '<translate>Fecha</translate>',
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) {
                            return '<span >'+data+'</span>'
                        }
                    }, 
                    { 
                        data: 'name',
                        title: '<translate>Nº de Orden</translate>',
                        className: 'font-weight-bold'
                    },                     
                    { 
                        data: 'task',
                        title: '<translate>Actividad</translate>',
                        className: 'font-weight-bold'
                    },                                    
                    { 
                        data: 'provider_name',
                        title: '<translate>Proveedor</translate>',
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) { 
                            return '<a href="#" class="option viewContact" data-item="'+row.provider_id+'">'+data+' <i class="fa fa-external-link"></i></a>'
                        }
                    },                                    
                    { 
                        data: 'company',
                        visible : ((vm.auth.group.all==1) ? true : false ),
                        title: '<translate>Emapresa</translate>',                        
                    },
                    { 
                        data: 'status',
                        title: '<translate>Estatus</translate>',
                        className: 'smaller',
                        render: function(data, type, row, meta) {  
                            let status
                            switch (data) {
                                case 'pending':
                                    status =   '<span class="badge badge-warning ">Por Autorizar</span>'
                                    break;
                                case 'approved':
                                    status =   '<span class="badge badge-info ">Aprobada</span>'
                                    break;
                                case 'payment':
                                    status =   '<span class="badge badge-success ">Pagada</span>'
                                    break;
                            
                                case 'rejected':
                                    status =   '<span class="badge badge-danger ">Rechazada</span>'
                                    break;
                            }                          
                            return status
                        }
                    },
                     { 
                        data: 'total',
                        title: '<translate>Total</translate>',
                        className: 'font-weight-bold',
                        render:function(data, type, row){
                            
                            return '$' + (  (  Number(row.apply_vat)==1  ) ?  vm.formatPrice( Number(row.total) +  (row.total * .16)) : vm.formatPrice(data) )
                        }
                    }, 
                    
                    { 
                        data: 'id',
                        title: '<translate>Adjuntos</translate>',
                        className: 'text-center lighter',
                        render: function(data, type, row, meta) {
                            return '<div class="row-actions"><span class="btn btn-primary btn-sm btn-upper tip mt-2 mr-2 addProof option" data-item="'+data+'"><i class="fa fa-file-text-o mr-2"></i>Adjuntos</span></div>';
                        } 
                    }, 
                    { 
                        data: 'id',
                        title: '<translate>Comprobantes</translate>',
                        className: 'text-center lighter',
                        render: function(data, type, row, meta) {
                            return '<div class="row-actions"><span class="btn btn-danger btn-sm btn-upper tip mt-2 mr-2 addProofPaid option" data-item="'+data+'"><i class="fa fa-file-text-o mr-2"></i>Comprobantes</span></div>';
                        } 
                    },   
                    { 
                        data: 'created_by',
                        title: '<translate>Creada Por</translate>',
                        className: 'font-weight-bold'
                    }, 
                    { 
                        data: 'comment',
                        title: '<translate>Comentarios</translate>',
                        className: 'smaller'
                    },   
                                 
                    { 
                        data: 'id',
                        title: '<translate>Opciones</translate>',
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            return '<div class="row-actions">'+ 
                                ((row.status == 'pending' && ( vm.auth.group.name=='root' || vm.auth.id == Number(vm.userAuth))) ? '<span style="cursor:pointer" title="Aceptar o Rechazar OP" class="badge badge-success tip option validateItem mr-2" data-item="'+data+'"><i class="icon-feather-check ml-1"></i> Validar OP</span>':'' ) +
                                ((row.status == 'approved') ? '<span style="cursor:pointer" class="badge badge-primary tip option payItem mr-2" data-item="'+data+'"><i class="fa fa-money"></i> Pagar Orden</span>':'' ) +
                                '<a href="#" class="option viewItem" data-item="'+data+'"><i class="icon-feather-file-text"></i></a>'+
                                '<a class="'+((!_.isNull(vm.auth) && vm.auth.role.slug != 'admin') ? 'd-none' : '' )+' option danger deleteItem" h  ref="#" data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                            '</div>';
                        } 
                    },
                ],
                ajax: {
                    url: api.selectOrderPay,
                    type: "POST",
                    data: function(){return {from:vm.from,to:vm.to};}
                },
                order: [[ 5, 'ASC' ]], 
                initComplete:async function( settings, json){
                    vm.ready = true; 
                    await setTimeout(function() {vm.datatable.responsive.recalc();}, 50)
                },
                drawCallback: function( settings ) {
                    $('.tip').tooltip();
                    
                }               
            });


            $('.single-date').daterangepicker({                
                locale: {
                    format: 'YYYY-MM-DD'
                },
                opens: "left",
                autoApply: true,
                autoUpdateInput: false
            }, async function(start, end, label) {
                var selectedStartDate = start.format('YYYY-MM-DD');
                var selectedEndDate = end.format('YYYY-MM-DD');
                var from = $('.single-date.from');
                var to = $('.single-date.to');
                from.val(selectedStartDate);
                to.val(selectedEndDate);
                var toPicker = to.data('daterangepicker');
                toPicker.setStartDate(selectedStartDate);
                toPicker.setEndDate(selectedEndDate);
                var fromPicker = from.data('daterangepicker');
                await fromPicker.setStartDate(selectedStartDate);
                await fromPicker.setEndDate(selectedEndDate);                
                vm.from = selectedStartDate;
                vm.to = selectedEndDate;
                await vm.datatable.ajax.reload();
            })

             

            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.order_id = id
                    vm.showItem(id)
                }else if( $(this).hasClass('validateItem') ){
                    vm.validateContent(id)
                }else if($(this).hasClass('viewItem') ){
                    vm.$router.push({name:'documentOP',params: {id: id } })
                }else if($(this).hasClass('deleteItem')){
                    vm.deleteItem(id);
                }else if($(this).hasClass('addProof')){
                    EventBus.$emit("addProof", {id : id} ) ;
                }else if($(this).hasClass('addProofPaid')){

                    EventBus.$emit("addProof", {id : id, type:'operation'} ) ;
                }else if($(this).hasClass('payItem')){
                    vm.$router.push({name:'OperationForm',params: {content_id : id, type: 'order_pay' } })
                
                }else if($(this).hasClass('viewContact')){
                    vm.$router.push({name:'DirectoryForm',params: {id : id } })
                }
            });           
            vm.loading = false;            
        }
    }

</script>
