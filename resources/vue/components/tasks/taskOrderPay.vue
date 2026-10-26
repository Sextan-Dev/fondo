<template>
	<div class="row">
		<div class="col-md-12">
            <div v-show="!ready" class="progress mb-2"><div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar progress-bar-striped " role="progressbar" style="width: 100%"></div></div>
            <div class="row" v-show="ready && !showForm">
                <div class="col-md-12">
                    <legend>
                        <span>
                            Listado de Ordenes de Pago
                            <button  @click.prevent="clearForm();showForm=true;" class="ml-3 btn btn-primary"> 
                                <i class="fa fa-plus mr-2"></i> Nueva Orden de Pago
                            </button>
                        </span>
                    </legend>
                    <!-- DataTable -->
                    
                    <div class="element-box mt-3">                        
                        <div class="table-responsive">
                            <table class="table table-padded" id="OrderList" width="100%"></table>                    
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 task_order_pay">
                <div class="subform" v-show="showForm && ready">
                    <div class="row">
                        <div class="col-md-12" v-if="order_id">
                            <div class="form-group">
                                <h6><i class="icon-feather-edit mr-2"></i> <span>Editar Orden de Pago</span></h6>
                            </div>
                        </div>
                        <div class="col-md-12" v-else>
                            <div class="form-group">
                                <h6><i class="icon-feather-plus mr-2"></i> <span>Crear Orden de Pago</span></h6>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning borderless">
                        <h5 class="alert-heading" v-translate>Atención</h5>
                        <p v-translate>
                            Una vez creada la orden de pago se enviara una solicitud de aprovación al responsable de los pagos (la persona responsable de autorizar se define desde la sección de parametros), para que este apruebe el pago de dicha orden, en caso de que no se apruebe no se podra proceder al pago de la orden.
                        </p>
                    </div>
                    <!-- Form Users New & Edit-->
                    <form  id="form" @submit.prevent="formSubmit"  novalidate="true">
                        <div class="row">
                            <div class="col-md-12">
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
                                                        <span v-translate>Proveedor</span>
                                                    </label><br>
                                                    <span class="help" v-translate>Provienen de los egresos presupuestados</span>
                                                    <br>
                                                    <el-select v-model="form.provider_id" 
                                                        name="provider_id"
                                                        v-validate="'required'" 
                                                        data-vv-as=" "
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
                                            <div  class="col-md-5">
                                                <div class="form-group">
                                                    <label >
                                                        <span v-translate>Ingreso</span>
                                                    </label><br>
                                                    <span class="help" v-translate>Selecciona el ingreso de donde saldrá el pago</span>
                                                    <br>
                                                    <el-select v-model="form.operation_id" 
                                                        name="operation_id"
                                                        v-validate="'required'" 
                                                        data-vv-as=" "
                                                        :class="{'input': true, 'is-invalid': errors.has('operation_id') }"
                                                        placeholder="...">
                                                        <el-option
                                                        v-for="item in incomes"
                                                        :key="item.value"
                                                        :label="item.label"
                                                        :value="item.value">
                                                        </el-option>
                                                    </el-select>
                                                    <div class="invalid-feedback" v-show="errors.has('operation_id')" >{{ errors.first('operation_id') }}</div>                                        
                                                </div>
                                            </div>
                                        </div>
                                        <!-- <div class="row">
                                            <div  class="col-md-5">
                                                <div class="form-group">
                                                    <label >
                                                        <span v-translate>Población Beneficiada</span>
                                                    </label>
                                                    <span class="help" v-translate></span>
                                                    <br>
                                                    <el-select v-model="form.html" 
                                                        name="html"
                                                        multiple
                                                        :class="{'input': true, 'is-invalid': errors.has('html') }"
                                                        placeholder="...">
                                                        <el-option
                                                        v-for="item in beneficiaries"
                                                        :key="item.value"
                                                        :label="item.label"
                                                        :value="item.value">
                                                        </el-option>
                                                    </el-select>
                                                    <div class="invalid-feedback" v-show="errors.has('html')" >{{ errors.first('html') }}</div>                                        
                                                </div>
                                            </div> -->
                                            <!-- <div  class="col-md-5">
                                                <div class="form-group">
                                                    <label>
                                                        <span v-translate>Programa de Inversión</span>
                                                    </label>
                                                    <span class="help" v-translate></span>
                                                    <br>
                                                    <el-select v-model="form.item" 
                                                        name="item"
                                                        multiple
                                                        :class="{'input': true, 'is-invalid': errors.has('item') }"
                                                        placeholder="...">
                                                        <el-option
                                                        v-for="item in investments"
                                                        :key="item.value"
                                                        :label="item.label"
                                                        :value="item.value">
                                                        </el-option>
                                                    </el-select>
                                                    <div class="invalid-feedback" v-show="errors.has('item')" >{{ errors.first('item') }}</div>                                        
                                                </div>
                                            </div>
                                        </div>  -->
                                        <!-- <div class="row">
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
                                        </div> -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <el-checkbox v-model="form.apply_vat" v-validate="''" :true-label="1" :false-label="0">Agregar IVA (Tasa 16%)</el-checkbox>                                                
                                            </div>
                                        </div>
                                        <div class="row" v-if="form.provider_id">
                                            <div class="col-md-12">
                                                <table class="table table-striped table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Agregar</th>
                                                            <th>Cantidad</th>
                                                            <th>Articulo</th>                                                
                                                            <th>Descripción</th>                                                                                              
                                                            <th>Costo</th>
                                                            <!-- <th>Precio</th> -->
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, key) in tableDynamic.rows" :key="key">
                                                            <td><input type="checkbox" v-model="tableDynamic.rows[key].include"> </td>
                                                            <td style="max-width: 50px;"><input  type="number"  :name="'qt_add_'+key" :class="{  'input': true, 'is-invalid': errors.has('qt_add_'+key) }" data-vv-as=" " v-validate="'required'" class="form-control" v-model="tableDynamic.rows[key].qt"></td>
                                                            <td class="smaller">{{item.article}}</td>
                                                            <td class="smaller">{{item.comment}}</td>
                                                            <td style="max-width: 100px;"><input   type="number"  :name="'cost'+key" :class="{  'input': true, 'is-invalid': errors.has('cost'+key) }" data-vv-as=" " v-validate="'required'" class="form-control" v-model="tableDynamic.rows[key].cost"></td>
                                                            <!-- <td style="max-width: 100px;"><input   type="number"  :name="'amount'+key" :class="{  'input': true, 'is-invalid': errors.has('amount'+key) }" data-vv-as=" " v-validate="'required'" class="form-control" v-model="tableDynamic.rows[key].amount"></td> -->
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
                                                                <span>${{ (form.apply_vat) ?  formatPrice( ( total * .16 ) + total )  :  formatPrice(total) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row" v-if="filesList">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="">
                                                        <span v-translate>Comprobante(s)</span>
                                                    </label><br>
                                                    <div class="" v-if="filesList">
                                                        <table class="table table-lightborder">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-center" v-translate>Adjuntos</th>
                                                                    <th ></th>
                                                                    <th ></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for=" (item, index ) in filesList" :key="index">
                                                                    <td class="text-left"><div class="status-pill green" data-title="Complete" data-toggle="tooltip" data-original-title="" title=""></div>&nbsp;{{ item.file }}</td>
                                                                    <td class="text-left">
                                                                        <a target="_blank" :href="appDomain+'/uploads/documents/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                                                    </td>                                                                    
                                                                </tr>
                                                            </tbody>
                                                        </table>
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
    import tableDynamic from '../../helpers/tableDynamic'
    import {EventBus} from '../../core/eventBus.js';
	export default {
        props:['appDomain','siteName', 'auth','task'],
        data(){
            return {
                ready:false,
                order_id:null,
                showForm : false,
                orderIdAuth:null,
                loading : true,
                errorOrder: false,
                optionsProvider:[],
                optionsVats:[],
                optionsOrders:[],                
                optionsBudgets:[],
                preventWatch: false,
                incomes:[],
                debits:[],
                beneficiaries:[],
                userAuth:null,
                optionsConcepts:[],
                filesList:null,
                investments:[],
                form:{
                    name:'',
                    date: moment().format('YYYY-MM-DD'),
                    provider_id:null,
                    comment:null,
                    category_id:null,
                    order_id:null,
                    item:[],
                    html:[],
                    budget:null,
                    apply_vat:true,
                },
                tableDynamic:{                    
                    rows:[]
                },         
            }
        }, 
        computed: {

            total() {
                return this.tableDynamic.rows.reduce((total, item) => {
                    return  total + ((item.include) ? ( Number(item.qt) * Number(item.cost) ) : 0);
                }, 0);
            },

            provider_id : function(){
                return this.form.provider_id
            },


            apply_vat: function(){
                return this.form.apply_vat
            }
        }, 

        watch:{
            'provider_id' : async function(){
                await this.data()
                if(!_.isNull(  this.form.provider_id ) && this.form.provider_id != '' && !_.isNull(this.debits) && !_.isEmpty(this.debits) && !this.preventWatch) {
                    this.tableDynamic.rows = JSON.parse(JSON.stringify(this.debits[this.form.provider_id]))
                }else{
                    this.preventWatch = false
                }
            }
        },
    
        components:{
            'tableDynamic' : tableDynamic
        },     
        methods:{
            clearForm: async function(element){
                this.form = {
                    name:'',
                    date: moment().format('YYYY-MM-DD'),
                    provider_id:null,
                    category_id:null,
                    operation_id:null,
                    html:[],
                    item:[],
                    comment:null,
                    apply_vat:false,
                    order_id:null,
                    filesList:null,
                    budget:null
                }
                this.order_id = null
                this.tableDynamic={
                    rows:[]
                }
                await this.data()
            },

            data:async function(){
                var vm = this;
               
                await axios.post(api.dataOrderPay,{ task_id : vm.$route.params.id, project_id:vm.task.project_id })
                .then(function (response) {
                    if(response.data.success){                
                        vm.optionsProvider = response.data.clients                        
                        vm.debits = response.data.debits
                        vm.beneficiaries = response.data.beneficiaries
                        vm.investments = response.data.investments
                        vm.optionsConcepts = response.data.optionConcepts
                        vm.incomes = response.data.incomes
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
            // Show User data for Edit
            showItem:async function( item ){
                var vm = this;
                vm.preventWatch =true
                vm.ready = false
                if(item){
                    await this.data()
                    await axios.post(api.showOrderPay, {item: item })
                    .then(async function (response) {                        
                        if(response.data.success){
                            vm.preventWatch = true
                            let form = JSON.parse(JSON.stringify(response.data.item))
                            //form.item = (form.item) ?  JSON.parse(form.item) : []
                            //form.html = (form.html) ?  JSON.parse(form.html) : []
                            vm.form = JSON.parse(JSON.stringify(form))
                            vm.form.provider_id = response.data.item.contact_id 
                            vm.filesList = JSON.parse(vm.form.files)
                            vm.tableDynamic.rows = await JSON.parse(JSON.stringify(vm.debits[vm.form.provider_id]))                                             
                            await _.each(response.data.rows, function(item,key){
                                let find = _.findWhere( vm.tableDynamic.rows, {id: item.reference_id} )
                                if(_.findWhere( vm.tableDynamic.rows, {id: item.reference_id} )  ){                                 
                                    find.include = true
                                    find.qt = item.qt
                                    find.amount = item.amount
                                    find.cost = item.cost
                                }
                            })
                            vm.showForm=true 
                            vm.ready = true  
                        }else{
                            toastr.error(response.data.msg);
                            vm.ready = true
                        }
                    })
                    .catch(function (error) {
                         vm.ready = true
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
                            var apiRoute = api.createOrderPay;                    
                        }else{                                            
                            var apiRoute = api.updateOrderPay;
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
                        className: 'smaller'
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
                                ((row.status == 'payment') ? '' :'<a href="#" class="option editItem" data-item="'+data+'"><i class="icon-feather-edit"></i></a>')+
                                '<a class="'+((!_.isNull(vm.auth) && vm.auth.role.slug != 'admin') ? 'd-none' : '' )+' option danger deleteItem" h  ref="#" data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                            '</div>';
                        } 
                    },
                ],
                ajax: {
                    url: api.selectOrderPay,
                    type: "POST",
                    data: {task_id : vm.$route.params.id}
                },
                order: [[ 0, 'desc' ]], 
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
                    vm.$router.push({name:'documentOP',params: {id: id } })
                }else if($(this).hasClass('addProof')){
                    EventBus.$emit("addProof", {id : id} ) ;
                }else if($(this).hasClass('addProofPaid')){

                    EventBus.$emit("addProof", {id : id, type:'operation'} ) ;
                }else if($(this).hasClass('deleteItem')){
                    vm.deleteItem(id);
                }else if($(this).hasClass('payItem')){
                    vm.$router.push({name:'OperationForm',params: {content_id : id, type: 'order_pay' } })
                }else if($(this).hasClass('viewContact')){
                    vm.$router.push({name:'DirectoryForm',params: {id : id } })
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
