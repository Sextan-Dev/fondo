<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">
            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'DirectoryForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nuevo Contacto</translate> </button>
            </h6>

            <div class="element-filters">

                <div class="row">
                    <div class="form-check form-filter-small col-sm-6 col-md-4 col-lg-3" v-for="(cat , index) in categories" :key="index" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <translate>CATEGORÍA</translate>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                    <input class="form-check-input" name="category" @change="reloadTable" type="radio" :value="cat.value" v-model="category" >
                                    <span>{{cat.name}}</span>
                                </label>
                            </div>
                            <div class="trending hidden-sm-up hidden-md-down">
                                <span class="badge badge-primary">{{cat.count}}</span>
                            </div>
                        </a>
                    </div>
                </div>

            </div>

            <div class="element-box">
              
                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-padded" id="directoryList" width="100%"></table>                    
                </div>

            </div>
		</div>
	</div>
</template>

<script>
    import {api} from "../../../config/jwt-routes";
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
            vm.datatable = $('#directoryList').DataTable({
                columns: [
                    { 
                        data: 'name',
                        title: '<translate>Nombre</translate>',
                        className: 'font-weight-bold'
                    },
                    { 
                        data: 'category',
                        title: '<translate>Categoría</translate>',
                        render: function(data, type, row, meta) {
                            return `<span class="badge badge-primary-inverted">`+data+`</span>`;
                        }
                    },
                    { 
                        data: 'email',
                        title: '<translate>Email</translate>',
                    },
                    { 
                        data: 'phone',
                        title: '<translate>Teléfono</translate>',
                    },
                    { 
                        data: 'id_number',
                        title: '<translate>Nº ID</translate>',
                        className: 'lighter'
                    },
                    { 
                        data: 'address',
                        title: '<translate>Direccíon</translate>',
                        className: 'lighter smaller'
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
                    url: api.selectContact,
                    type: "POST",
                    data: function(){ return {category:vm.category}; }
                },
                order: [[ 0, 'asc' ]],
            });

            vm.loading = false;
            
            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.$router.push({name:'DirectoryForm',params: {id: id } })
                }else{
                    if($(this).hasClass('deleteItem')){
                        vm.deleteItem(id);
                    }
                }
            });
        }
    }

</script>
