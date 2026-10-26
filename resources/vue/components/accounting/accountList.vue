<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'AccountForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nueva Cuenta</translate> </button>
            </h6>

            <div class="element-box">
              
                <!-- DataTable -->
                <div class="table-responsive">
                     <table class="table table-padded" id="accountList" width="100%"></table>                  
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
                items:[],
                loading : true,
                datatable:null
            }
        },
        methods:{
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
                        axios.post(api.deleteAccount, {item:item})
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
            }
        },
        mounted: function(){
            var vm = this;
            var random = Math.random() * 10;
            //DATATABLE
            vm.datatable = $('#accountList').DataTable({
                columns: [
                    { 
                        data: 'img',
                        title: '',
                         render: function(data, type, row, meta) {
                            return '<img style="width: 100px" src="'+vm.appDomain +'/'+ data+'?'+ random +'" onerror="javascript:this.src=\'/images/bank.png\'" />';
                        } 
                    },
                    { 
                        data: 'name',
                        title: '<translate>Cuenta</translate>',
                        className: 'font-weight-bold'
                    },
                    { 
                        data: 'slug',
                        title: '<translate>Clave</translate>',
                    },
                    { 
                        data: 'bank',
                        title: '<translate>Banco</translate>',
                    },
                    { 
                        data: 'id',
                        title: '<translate>Opciones</translate>',
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            return '<div class="row-actions">'+
                                        '<a href="#" class="option editItem"  data-item="'+data+'"><i class="icon-feather-edit"></i></a>'+
                                        '<a class="option danger deleteItem" href="#"  data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                                    '</div>';
                        } 
                    },
                ],
                ajax: {
                    url: api.listAccount,
                    type: "POST",
                    data: function(){}
                },
                order: [[ 0, 'desc' ]],                
            });

            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.$router.push({name:'AccountForm',params: {id: id } })
                }else{
                    if($(this).hasClass('deleteItem')){
                        vm.deleteItem(id);
                    }
                }
            });
            
            vm.loading = false;
            
        }
    }

</script>
