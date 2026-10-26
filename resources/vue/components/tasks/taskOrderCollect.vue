<template>
	<div class="row">
		<div class="col-md-12">            
           
            <div v-show="!ready" class="progress mb-2"><div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar progress-bar-striped " role="progressbar" style="width: 100%"></div></div>
            <div class="row" v-show="ready && !showForm">
                    <div class="col-md-12">
                        <legend>
                            <span>
                                Listado de Ordenes de Cobro
                                <button  @click.prevent="clearForm();showForm=true;" class="ml-3 btn btn-primary"> 
                                    <i class="fa fa-plus mr-2"></i> Nueva Orden de Cobro
                                </button>
                            </span>
                        </legend>
                        <!-- DataTable -->
                        
                        <div class="element-box mt-3">                        
                            <div class="table-responsive">
                                <table class="table table-padded" id="OrderCollectList" width="100%"></table>                    
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 task_order_pay">
                    <div class="subform" v-show="showForm && ready">
                        <div class="row">
                            <div class="col-md-12" v-if="order_id">
                                <div class="form-group">
                                    <h6><i class="icon-feather-edit mr-2"></i> <span>Editar Orden de Cobro</span></h6>
                                </div>
                            </div>
                            <div class="col-md-12" v-else>
                                <div class="form-group">
                                    <h6><i class="icon-feather-plus mr-2"></i> <span>Crear Orden de Cobro</span></h6>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info borderless">
                            <h5 class="alert-heading" v-translate>Información</h5>
                            <p v-translate>
                                Los ingresos Presupuestados disponibles son aquellos que se han registrado previamente en el estado de cuenta de esta esta actividad, si necesitas agregar uno o varios elementos adicionales es necesario agregarlos en el estado de cuenta de esta actividad.
                            </p>
                        </div>
                        <!-- Form Users New & Edit-->
                        <form  id="form" @submit.prevent="formSubmit"  novalidate="true">
                            <div class="row">
                                <div class="col-sm-10">
                                    <div class="row">
                                        <div class="col-md-12">                                    
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="" v-translate>Fecha</label>
                                                        <div class="date-input">
                                                            <el-date-picker
                                                            :name="'date'"
                                                            data-vv-as=" " v-validate="'required'"
                                                            v-model="form.date"                                             
                                                            format= "yyyy-MM-dd"
                                                            value-format="yyyy-MM-dd"
                                                            type="date"
                                                            :class="{'input': true, 'is-invalid': errors.has('date') }"
                                                            placeholder="">
                                                            </el-date-picker>
                                                            <div class="invalid-feedback" v-show="errors.has('date')" >{{ errors.first('date') }}</div>
                                                        </div>
                                                    </div>
                                                </div>    
                                                <div class="col-md-5" v-if="order_id">
                                                    <div class="form-group">
                                                        <label v-translate>N° Orden</label>   
                                                        <div class="input-group">
                                                            <div class="input-group-prepend" >
                                                                <div class="input-group-text"><i class="fa fa-file-text"></i></div>
                                                            </div>
                                                            <input type="text" name="name" v-model="form.name" class="form-control"  placeholder="..." value="" v-validate="'required|min:3'" :class="{'input': true, 'is-invalid': errors.has('name') }">
                                                            <div class="invalid-feedback"  v-show="errors.has('name')" >{{ errors.first('name') }}</div>  
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div  class="col-md-5">
                                                    <div class="form-group">
                                                        <label >
                                                            <span v-translate>Donador</span>
                                                        </label>
                                                        <span class="help" v-translate>ex. Carmen Felix</span>
                                                        <br>
                                                        <el-select v-model="form.provider_id" 
                                                            name="provider_id"
                                                            :class="{'input': true, 'is-invalid': errors.has('provider_id') }"
                                                            placeholder="...">
                                                            <el-option
                                                            v-for="item in optionsProvider"
                                                            :key="item.value"
                                                            :label="item.label"
                                                            :value="item.value">
                                                            </el-option>
                                                        </el-select>
                                                        
                                                        <div class="invalid-feedback" v-show="errors.has('provider_id')" >{{ errors.first('provider_id') }}</div>                                        
                                                    </div>
                                                </div>
                                                
                                            </div>
                                              <div class="row">
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label>
                                                            <span v-translate>Concepto</span>
                                                        </label>
                                                        <br> 
                                                        <el-select v-model="form.category_id" 
                                                            name="category_id"
                                                            v-validate="'required'" 
                                                            :class="{'input': true, 'is-invalid': errors.has('category_id') }"
                                                            placeholder="...">
                                                            <el-option
                                                            v-for="item in optionsConcepts"
                                                            :key="item.value"
                                                            :label="item.label"
                                                            :value="item.value">
                                                            </el-option>
                                                        </el-select>
                                                      
                                                        <div class="invalid-feedback" v-show="errors.has('category_id')" >{{ errors.first('category_id') }}</div>
                                                    </div>
                                                </div>
                                            </div>                                                                                      
                                            <div class="row" >
                                                <div class="col-md-12">
                                                    <table class="table table-striped table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Agregar</th>
                                                                <th>Donador</th>
                                                                <th>Cantidad</th>                                             
                                                                <th>Descripción</th>                                                                                              
                                                                <th>Costo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(item, key) in form.credits" :key="key">
                                                                <td><input type="checkbox" v-model="form.credits[key].include"> </td>
                                                                <td>{{form.credits[key].provider_name}} </td>
                                                                <td><input  type="number"  :name="'qt_add_'+key" :class="{  'input': true, 'is-invalid': errors.has('qt_add_'+key) }" data-vv-as=" " v-validate="'required'" class="form-control" v-model="form.credits[key].qt"></td>                                                                
                                                                <td>{{item.comment}}</td>                                                                
                                                                <td><input  type="number"  :name="'amount'+key" :class="{  'input': true, 'is-invalid': errors.has('amount'+key) }" data-vv-as=" " v-validate="'required'" class="form-control" v-model="form.credits[key].amount"></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="form-group">
                                                        <div class="element-balances mt-0 pt-0 d-block">
                                                            <div class="balance bg-light align-right">
                                                                <div class="balance-title">
                                                                    <span>Total</span>
                                                                </div>
                                                                <div class="balance-value">
                                                                   <span>${{total.toLocaleString('mx')}}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for=""><span>Comentario</span></label>
                                                        <textarea name="comment" class="form-control" v-model="form.comment"  rows="5"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                                
                                        <div class="form-buttons-w"  >
                                            <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                                <span v-show="loading"><translate>Guardando</translate>...</span>
                                                <span v-show="!loading"><translate>Guardar</translate></span>
                                            </button>
                                            <button class=" btn btn-light" type="button" @click.prevent="clearForm();showForm=false;" v-translate> Cancelar</button>
                                        </div>
                                    </div>                                
                                </div>
                            </div>
                        </form>
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
                order_id:null,
                showForm : false,
                orderIdAuth:null,
                loading : true,
                errorOrder: false,
                optionsProvider:[],               
                preventWatch: false,
                credits:[],
                optionsConcepts:[],
                userAuth:null,
                form:{
                    name:'',
                    date: moment().format('YYYY-MM-DD'),
                    provider_id:null,
                    comment:null,
                    category_id:null,
                    order_id:null,
                    budget:null,
                    credits:[]
                }
                      
            }
        }, 
        computed: {

            total() {
                return this.form.credits.reduce((total, item) => {
                    return  total + ( (item.include) ? ( Number(item.qt) * Number(item.amount) ) : 0);
                }, 0);
            }

        },
               
        methods:{
            clearForm: async function(element){
                this.form = {
                    name:'',
                    date: moment().format('YYYY-MM-DD'),
                    provider_id:null,
                    comment:null,
                    category_id:null,
                    order_id:null,
                    budget:null,
                    credits:[],
                }
                this.order_id = null

                await this.data()
            },

            data:async function(){
                var vm = this;
                await axios.post(api.dataOrderCollect,{ task_id : vm.$route.params.id })
                .then(function (response) {
                    if(response.data.success){                
                        vm.optionsProvider = response.data.clients                        
                        vm.form.credits = response.data.credits
                        vm.optionsConcepts = response.data.optionConcepts
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
                        axios.post(api.deleteOrderCollect, {item:item})
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

            showItem:async function( item ){
                var vm = this;
                if(item){
                    await this.data()
                    await axios.post(api.showOrderCollect, {item: item })
                    .then(function (response) {                        
                        if(response.data.success){
                            vm.preventWatch = true
                            vm.form.id= response.data.item.id
                            vm.form.name= response.data.item.name
                            vm.form.date= response.data.item.date
                            vm.form.html= response.data.item.html
                            vm.form.category_id= response.data.item.category_id
                            vm.form.provider_id= response.data.item.contact_id
                            vm.form.comment=response.data.item.comment
                            _.each(response.data.rows, function(item,key){
                                let find = _.findWhere( vm.form.credits, {id: item.reference_id} )
                                if(_.findWhere( vm.form.credits, {id: item.reference_id} )  ){                                 
                                    find.include = true
                                    find.qt = item.qt
                                    find.amount = item.amount
                                }
                            })
                            vm.showForm=true                     
                        }else{
                            toastr.error(response.data.msg);
                        }
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                    })
                }              
            },                 
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                vm.$validator.validateAll().then((result) => {                    
                    var redirectToList = false;
                    if (result){
                        vm.loading = true
                        if(!vm.order_id){                           
                            var apiRoute = api.createOrderCollect;                    
                        }else{                                            
                            var apiRoute = api.updateOrderCollect;
                        }
                        axios.post(apiRoute,{task_id:vm.$route.params.id , form : vm.form, table: vm.tableDynamic} )
                        .then(async function (response) {
                            if(response.data.success){                                                            
                                toastr.success(response.data.msg);  
                                vm.clearForm()
                                vm.showForm=false
                                vm.datatable.ajax.reload();                     
                            }else{
                                toastr.error(response.data.msg);
                            }     
                            vm.loading = false                 
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                            vm.loading = false
                        })
                    }
                })
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
        },

        mounted: async function(){
            let vm = this
            await vm.data()

            vm.datatable = $('#OrderCollectList').DataTable({
                columns: [                  
                    { 
                        data: 'id',
                        title: '<translate>Fecha</translate>',
                        className: 'font-weight-bold',
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
                        data: 'provider_name',
                        title: '<translate>Proveedor</translate>',
                        className: 'font-weight-bold'
                    }, 
                    { 
                        data: 'name',
                        title: '<translate>Nº de Orden</translate>',
                        className: 'font-weight-bold'
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
                        render:function(data){
                            return '$' +data
                        }
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
                                ((row.status == 'pending' && ( vm.auth.group.name=='root' || vm.auth.id == Number(vm.userAuth))) ? '<span style="cursor:pointer" title="Aceptar o Rechazar OC" class="badge badge-success tip option validateItem mr-2" data-item="'+data+'"><i class="icon-feather-check ml-1"></i> Validar OC</span>':'' ) +
                                ((row.status == 'approved') ? '<span style="cursor:pointer" class="badge badge-primary tip option payItem mr-2" data-item="'+data+'"><i class="fa fa-money"></i> Cobrar Orden</span>':'' ) +
                                '<a href="#" class="option viewItem" data-item="'+data+'"><i class="icon-feather-file-text"></i></a>'+
                                ((row.status == 'payment') ? '' :'<a href="#" class="option editItem" data-item="'+data+'"><i class="icon-feather-edit"></i></a>')+
                                '<a class="'+((!_.isNull(vm.auth) && vm.auth.role.slug != 'admin') ? 'd-none' : '' )+' option danger deleteItem" h  ref="#" data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                            '</div>';
                        } 
                    },
                ],
                ajax: {
                    url: api.selectOrderCollect,
                    type: "POST",
                    data: {task_id : vm.$route.params.id}
                },
                order: [[ 0, 'asc' ]], 
                initComplete:async function( settings, json){
                    vm.ready = true; 
                    await setTimeout(function() {vm.datatable.responsive.recalc();}, 50)
                },
                drawCallback: function( settings ) {
                    $('.tip').tooltip();
                    
                }               
            });

            $('tbody').on( 'click', '.option', function(){
                let id = $(this).attr("data-item");
                if( $(this).hasClass('editItem') ){
                    vm.order_id = id
                     vm.showItem(id)
                }else if( $(this).hasClass('validateItem') ){
                    vm.validateContent(id)
                
                }else if($(this).hasClass('viewItem') ){
                    vm.$router.push({name:'documentOC',params: {id: id } })
                }else if($(this).hasClass('deleteItem')){
                    vm.deleteItem(id);
                }else if($(this).hasClass('payItem')){
                    vm.$router.push({name:'OperationForm',params: {content_id : id, type: 'order_collect' } })
                }
            });   
            this.loading = false
        }
    }
</script>
<style>
    .el-select {
        width: 100%!important;
    }
    .el-date-editor {
        width: 100%!important;
    }
    .task_order_pay .add-row-table{
        display: none
    }
</style>
