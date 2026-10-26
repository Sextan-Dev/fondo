<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">            
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'GoodsAndServicesForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Agregar nuevo</translate> </button>
            </h6>
             <!-- FILTERS -->
            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>Tipos</h6>
                <div class="row">
                    <div class="form-check form-filter-small col-sm-6 col-md-4 col-lg-3" v-for="(item , key) in types" :key="key" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <translate>Tipo</translate>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                    <input class="form-check-input" name="type" @change="reloadTable" type="radio" :value="item.value" v-model="type" >
                                    <span>{{item.name}}</span>
                                </label>
                            </div>
                            <div class="trending hidden-sm-up hidden-md-down">
                                <span class="badge badge-primary">{{item.count}}</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>Categorias</h6>
                <div class="row">
                    <div class="form-check form-filter-small col-sm-6 col-md-4 col-lg-3" v-for="(cat , index) in categories" :key="index" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <translate>Categoria</translate>
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
                     <table class="table table-padded" id="goodsAndServicesList" width="100%"></table>                  
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
                datatable:null,
                categories: [],
                type:null,
                category:null,
                types:[],
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
                        axios.post(api.deleteGoodAndServices, {item:item})
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
            // GET FILTERS & COUNTS
            data:async function(){
                var vm = this;
                await axios.post(api.listGoodAndServices)
                .then(function (response) {
                    if(response.data.success){
                        vm.categories = response.data.categories;
                        vm.types = response.data.types;
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                     toastr.error(error);
                    console.log(error);
                })
            },
             // RELOAD DATATABLE
            reloadTable : function() {
                this.datatable.ajax.reload();
            }
        },
        mounted: async function(){
            var vm = this;
            var random = Math.random() * 10;
          
            // FILTERS
            await vm.data();
            //DATATABLE
            vm.datatable = $('#goodsAndServicesList').DataTable({
                columns: [
                    
                    { 
                        data: 'article',
                        title: '<translate>Item</translate>',
                        className: 'font-weight-bold',
                        
                    },
                    // { 
                    //     data: 'slug',
                    //     title: '<translate>Clave</translate>',
                    // },
                    { 
                        data: 'types',
                        title: '<translate>Tipo</translate>',
                        render: function(data, type, row, meta) {
                            return '<span class="badge badge-primary-inverted">'+data+'</span>';
                        }
                    },
                    { 
                        data: 'category',
                        title: '<translate>Categoria</translate>',
                    },
                     { 
                        data: 'price',
                        title: '<translate>Precio</translate>',
                        className: 'bolder',
                        render: function(data, type, row, meta) {
                            return '$ '+data;
                        }
                    },
                     { 
                        data: 'cost',
                        title: '<translate>Costo</translate>',
                        className: '',
                        render: function(data, type, row, meta) {
                            return '$ '+data;
                        }
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
                    url: api.selectGoodAndServices,
                    type: "POST",
                    data: function(){return {category:vm.category, type:vm.type };}
                },
                order: [[ 0, 'desc' ]],                
            });

            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.$router.push({name:'GoodsAndServicesForm',params: {id: id } })
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
