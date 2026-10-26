<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'AlliesForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nuevo Aliado</translate> </button>
            </h6>

            <div class="element-box">
                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-padded" id="alliesList" width="100%"></table>                    
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
                category: '',
                categories: [],
                datatable: null,
                loading : true        
            }
        },
        methods: {
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
                        axios.post(api.deleteContact, {id:id})
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
                            toastr.error(response.data.msg);
                            console.log(error);
                        })
                    } else {
                        swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                    }
                });
            },
            // Get treeview and groups list
            data:async function(){
                var vm = this;
                await axios.get(api.dataContact)
                .then(function (response) {
                    if(response.data.success){
                        vm.categories = response.data.categories;
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                })
            },
            reloadTable : function() {
                this.datatable.ajax.reload();
            }
        },
        mounted: async function(){
            var vm = this;
            
            await vm.data();
            
            //DATATABLE
            vm.datatable = $('#alliesList').DataTable({
                columns: [
                    { 
                        data: 'name',
                        title: '<translate>Organización civil</translate>',
                        className: 'font-weight-bold'
                    },
                    { 
                        data: 'email',
                        title: '<translate>Email</translate>',
                    },
                    { 
                        data: 'phone',
                        title: '<translate>Teléfono</translate>',
                        className: 'smaller'
                    },
                    { 
                        data: 'address',
                        title: '<translate>Direccíon</translate>',
                        className: 'smaller'
                    },
                    { 
                        data: 'id',
                        title: '<translate>Opciones</translate>',
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            return '<div class="row-actions">'+
                                        '<a href="#" class="option viewItem" data-item="'+data+'"><i class="icon-feather-file-text"></i></a>'+
                                        '<a href="#" class="option editItem" data-item="'+data+'"><i class="icon-feather-edit"></i></a>'+
                                        '<a class="option danger deleteItem" href="#" data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                                    '</div>';
                        } 
                    },
                ],
                ajax: {
                    url: api.selectContact,
                    type: "POST",
                    data: function(){ return {category:'Aliado'}; }
                },
                order: [[ 0, 'asc' ]],
            });

            vm.loading = false;
            
            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.$router.push({name:'AlliesForm',params: {id: id } })
                } else if ( $(this).hasClass('viewItem') ) {
                    let key = Math.random().toString(36).substring(2, 15)+'ca68'+id
                    vm.$router.push({name:'AllyReport',params: {id: key } })
                } else{
                    if($(this).hasClass('deleteItem')){
                        vm.deleteItem(id);
                    }
                }
            });
        }
    }

</script>
