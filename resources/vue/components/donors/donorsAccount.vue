<template>
	<div class="content-box" >
		<div class="element-wrapper">            
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'OperationForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nueva Operacion</translate> </button>
                <!-- <button  class="btn btn-sm btn-primary" data-toggle="modal" data-target=".upload-layout" > <i class="fa fa-download mr-2"></i><translate>Importar Operaciones</translate> </button> -->
            </h6>
            <!-- UPLOAD LAYOUT MODAL -->
            <div aria-labelledby="upload-layout" data-backdrop="static" data-keyboard="false" class="modal fade upload-layout" role="dialog" tabindex="-1" aria-hidden="false" style="display: none;">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="alert alert-warning borderless">
                                <h5 class="alert-heading" v-translate>Registro masivo de Operaciones</h5>
                                <p v-translate>
                                    Aquí puedes importar un layout con la lista de operaciones que desees registrar en el sistema. Es importante tener un buen conocimiento de las consecuencias antes de cargar cualquier documento.
                                </p>
                                <p><translate>Nota.- Para una carga exitosa es importante repetar el formato preestablecido, favor de usar el siquiere documento como referencia </translate> <b><a target="_blank" :href="appDomain+'/files/layout_operations.xlsx'" v-translate>Plantilla.xlsx</a></b> </p>
                            </div>
                        </div>
                        <div class="modal-body">
                            <form  enctype="multipart/form-data" id="form" @submit.prevent="formUpload" >
                                <div class="row" v-show="!loading">
                                    <div class="col-md-8 col-centered" >
                                        <div class="form-group">
                                            <label for="">Agregar Archivo</label>
                                            <input @change.prevent="resetFormUpload()" :class="{'input': true, 'is-invalid': errors.has('files') }" v-validate="'required|ext:xlsx'"  name="files" type="file" class="form-control" >
                                            <div class="invalid-feedback" v-show="errors.has('files')" >{{ errors.first('files') }}</div>  
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="uploadFail">
                                    <!-- SHOW ERROR MESSAGE -->
                                    <div class="col-md-8 col-centered" >
                                        <div class="alert alert-warning" role="alert">
                                            <h4 class="alert-heading" v-translate>Oups... Errores encontrados!!</h4>
                                            <p v-translate>El proceso finalizo, pero se presentaron algunos errores, para mayor información por favor descarga el Log y vuelve a intentarlo, gracias.</p>
                                            <table class="table table-lightborder">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th v-translate>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><div data-title="Complete" data-toggle="tooltip" data-original-title="" title="" class="status-pill green"></div></td>
                                                        <td>{{countSuccess}}&nbsp;<translate>Operaciones registradas</translate></td>
                                                    </tr>
                                                    <tr>
                                                        <td><div data-title="Complete" data-toggle="tooltip" data-original-title="" title="" class="status-pill red"></div></td>
                                                        <td>{{countFail}}&nbsp;<translate>Operaciones no registradas</translate></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p><translate>Descargar </translate> <b><a target="_blank" :href="appDomain + logFile" v-translate>Log.xlsx</a></b> </p>
                                        </div>  
                                    </div>
                                </div>
                                <!-- LOADER -->
                                <div class="row" >
                                    <div class="col-md-8 col-centered" v-show="loading" >
                                        <h5 class="form-header" v-translate>Cargando Documento... Esto puede tardar algunos minutos, por favor no cierre esta ventana hasta que el proceso finalice, gracias.</h5>
                                        <div class="progress">
                                            <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100" class="progress-bar progress-bar-striped progress-bar-animated " role="progressbar" style="width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- FORM BUTTONS -->
                                <div class="row">
                                    <div class="co-md-8 col-centered">
                                         <div class="form-buttons-w">
                                            <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                                <span v-show="loading"><translate>Subiendo</translate>...</span>
                                                <span v-show="!loading"><translate>Subir</translate></span>
                                            </button>
                                            <!-- FOR DEBUG -->
                                            <!-- <button class="btn btn-primary btn-lg" type="submit" >
                                                <span ><translate>Subir</translate></span>
                                            </button> -->
                                            <button v-show="!loading" data-dismiss="modal" class=" btn btn-light" type="button" @click.prevent="resetFormUpload()" v-translate> Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>FEE & Seguimiento y Monitoreo</h6>
                <div class="row">
                    <div class="form-check form-filter-large col-sm-6 col-md-4 col-lg-3" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <span>FEE</span>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                   
                                    <span><small>$</small>{{ formatPrice(fee||0).split('.')[0]}}<small><small>.{{ formatPrice(fee||0).split('.')[1]}}</small></small></span>
                                </label>
                            </div>
                        </a>
                    </div>
                     <div class="form-check form-filter-large col-sm-6 col-md-4 col-lg-3" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <span>Seguimiento y Monitoreo</span>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                    <span><small>$</small>{{ formatPrice(monitoring||0).split('.')[0]}}<small><small>.{{ formatPrice(monitoring||0).split('.')[1]}}</small></small></span>
                                </label>
                            </div>
                        </a>
                    </div>
                </div>                
            </div>
            <!-- FILTERS -->
            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>Cuentas</h6>
                <div class="row">
                    <div class="form-check form-filter-large col-sm-6 col-md-4 col-lg-3" v-for="(item , key) in accounts" :key="key" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <span>{{item.name}}</span>
                            </div>
                            <div class="value">
                                <label class="form-check-label">
                                    <input class="form-check-input" name="account" @change="reloadTable" type="radio" :value="item.value" v-model="account" >
                                    <span v-if="item.sum"><small>$</small>{{item.sum.split('.')[0]}}<small><small>.{{item.sum.split('.')[1]}}</small></small></span>
                                </label>
                            </div>
                            <div class="trending hidden-sm-up hidden-md-down">
                                <span class="badge badge-primary">{{item.count}}</span>
                            </div>
                        </a>
                    </div>
                </div>

            </div>
            <!-- FILTERS -->
            
            <div class="element-filters">
                <h6 class="element-header mb-3" v-translate>Tipo de Operacion</h6>
                <div class="row">
                    <div class="form-check form-filter-small col-sm-6 col-md-4 col-lg-3" v-for="(cat , index) in categories" :key="index" >
                        <a class="element-box el-tablo">
                            <div class="label">
                                <translate>Operación</translate>
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
            <div class="element-filters mb-4">
                <h6 class="element-header mb-3" v-translate>Donador/Cliente/Proveedor</h6>
                <div class="row">
                    <div class=" col-sm-6 col-md-4 col-lg-3">
                        <form class="form-inline ">
                            <el-select
                                v-model="donor"
                                filterable   
                                @change="reloadTable()"                                                                        
                                placeholder="...">
                                <el-option
                                :key="'all'"
                                :label="'TODOS'"
                                :value="'all'">
                                </el-option>
                                <el-option
                                v-for="item in optionsDonors"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id">
                                </el-option>
                            </el-select>
                    </form>
                    </div>
                </div>
            </div>
            <!-- DATEPICKER RANGE FILTER -->
            <div class="element-filters mb-4">
                <h6 class="element-header mb-3" v-translate>Periodo</h6>
                <div class="row">
                    <div class=" col-sm-6 col-md-4 col-lg-3">
                        <form class="form-inline ">
                            <div class="form-group">
                                <label v-translate>Desde</label> 
                                <div class="date-input">
                                    <input type="text" name="from" v-model="from" class="single-date from form-control" placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('from') }" >
                                    <div class="invalid-feedback" v-show="errors.has('from')" >{{ errors.first('from') }}</div>  
                                </div>
                                <label v-translate>Hasta</label> 
                                <div class="date-input">
                                    <input type="text" name="to" v-model="to" class="single-date to form-control" placeholder="..." value="" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('to') }" >
                                    <div class="invalid-feedback" v-show="errors.has('to')" >{{ errors.first('to') }}</div>  
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- DataTable -->
            <div class="element-box">
                <div class="table-responsive">
                    <table class="table table-padded" id="operationList" width="100%"></table>                    
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
                from: moment().startOf('year').format('YYYY-MM-DD'),
                to: moment().endOf('year').format('YYYY-MM-DD'),
                category: '',
                account:'',
                donor:'all',
                logFile:'',
                logOperations:[],
                categories: [],
                optionsDonors:[],
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
            },
            // UPLOAD LAYOUT FILE
            formUpload: function(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading  = true
                        vm.uploadFail = false
                        let formData = new FormData(document.getElementById('form'));
                        axios.post(api.uploadOperations,formData )
                        .then( function (response) {
                            if(response.data.success){
                                vm.loading = false
                                if( response.data.complete ){
                                    toastr.success(response.data.msg);
                                }else{
                                    vm.countSuccess = response.data.countSuccess
                                    vm.countFail = response.data.countFail
                                    vm.logFile = response.data.logFile
                                    vm.uploadFail = true
                                    toastr.warning(response.data.msg);
                                }
                                vm.importFails = response.data.dataFail
                                vm.importSuccess = response.data.dataSuccess

                                vm.reloadTable();
                            }else{
                                toastr.error(response.data.msg);
                            }                         
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                            vm.loading = false
                        })
                    }
                })
                vm.loading = false
            },
            // GET FILTERS & COUNTS
            data:async function(){
                var vm = this;
                await axios.post(api.listOperation)
                .then(function (response) {
                    if(response.data.success){
                        vm.categories = response.data.categories;
                        vm.fee = response.data.fee
                        vm.monitoring = response.data.monitoring
                        vm.optionsDonors = response.data.optionsDonors;
                        vm.accounts = response.data.accounts;
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                     toastr.error(error);
                    console.log(error);
                })
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
            reloadTable : function() {
                this.datatable.ajax.reload();
            }
        },
        mounted: async function(){
            var vm = this;
            // FILTERS
            await vm.data();
            //DATATABLE
            vm.datatable = $('#operationList').DataTable({
                columns: [
                    {
                        data: 'id',
                        title: '<translate>Fecha</translate>',
                        visible:false
                    },
                    { 
                        data: 'date',
                        title: '<translate>Fecha</translate>',
                        className: 'font-weight-bold'
                    },
                     { 
                        data: 'task',
                        title: '<translate>Actividad</translate>',
                    },
                    { 
                        data: 'client',
                        title: '<translate>Cliente/Proveedor</translate>',
                        className: 'bolder'
                    },
                    { 
                        data: 'account',
                        title: '<translate>Cuenta</translate>',
                    },
                    { 
                        data: 'category',
                        title: '<translate>Categoria</translate>',
                    },
                    { 
                        data: 'type',
                        title: '<translate>Tipo</translate>',
                        render: function(data, type, row, meta) {
                            if( data == 'income' ){
                                return `<span class="badge badge-success-inverted"><translate>Ingreso</translate></span>`;
                            }else{
                                if( data == 'balance' ){
                                    return `<span class="badge badge-info-inverted"><translate>Balance</translate></span>`;
                                }else{

                                    return `<span class="badge badge-danger-inverted"><translate>Egreso</translate></span>`;
                                }
                            }
                        } 
                    },
                    { 
                        data: 'amount',
                        title: '<translate>Importe</translate>',
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) {
                            if( row.type == 'income' ){
                                return '<span class="text-success">$'+vm.formatPrice(data)+'</span>';
                            }else{
                                 if( row.type == 'balance' ){
                                    return '<span class="text-info">$'+vm.formatPrice(data)+'</span>';
                                }else{
                                    return '<span class="text-danger">$-'+vm.formatPrice(data)+'</span>';
                                }
                            }
                        }
                    },
                    { 
                        data: 'vat',
                        title: '<translate>IVA</translate>',
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) {
                          
                                return '$'+vm.formatPrice(data)+'';
                          
                        }
                    },
                    { 
                        data: 'fee',
                        title: '<translate>FEE</translate>',
                        className: 'font-weight-bold',
                        render: function(data, type, row, meta) {
                            return ( (row.type=='outcome') ? 'No Aplica' :
                                 "<span class='smaller'>Procentaje: " + (_.isNull(data)? 0 + "%" : data + "%") + '</span><br>Total: ' + (_.isNull(data)? 0  : vm.formatPrice((row.amount *  (data /100)) )));
                          
                        }
                    },
                    { 
                        data: 'monitoring',
                        title: '<translate>Seguimiento y Monitoreo</translate>',
                        className: 'font-weight-bold s',
                        render: function(data, type, row, meta) {
                            return ( (row.type=='outcome') ? 'No Aplica' :
                                 "<span class='smaller'>Procentaje: " + (_.isNull(data)? 0 + "%" : data + "%") + '</span><br>Total: ' + (_.isNull(data)? 0  : vm.formatPrice((row.amount *  (data /100)) )));
                          
                        }
                    },
                    
                    { 
                        data: 'mode',
                        title: '<translate>Forma</translate>',
                    },
                    
                    { 
                        data: 'comment',
                        title: '<translate>Comentario</translate>',
                        class: "smaller"
                    },
                                  
                    { 
                        data: 'id',
                        title: '<translate>Opciones</translate>',
                        className: 'lighter',
                        render: function(data, type, row, meta) {
                             return '<div class="row-actions">'+
                                        '<a href="#" title="Duplicar Registro" class="option replicateItem tip"   data-item="'+data+'"><i class="fa fa-window-restore"></i></a>'+
                                        '<a href="#" title="Editar Registro" class="option editItem tip"   data-item="'+data+'"><i class="icon-feather-edit"></i></a>'+  
                                        '<a title="Eliminar Registro" class="option danger deleteItem tip"  href="#"  data-item="'+data+'" ><i class="icon-feather-trash-2"></i></a>'+
                                    '</div>';
                        } 
                    },
                ],
                ajax: {
                    url: api.selectOperation,
                    type: "POST",
                    data: function(){ return {category:vm.category, account:vm.account ,from:vm.from,to:vm.to, contact:vm.donor}; }
                },
                order: [[ 0, 'desc' ]],
            });

             //DATEPICKER WITH AUTO SET
            $('.single-date').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                },
                opens: "left",
                autoApply: true,
                autoUpdateInput: false
            }, function(start, end, label) {
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
                fromPicker.setStartDate(selectedStartDate);
                fromPicker.setEndDate(selectedEndDate);
                //RELOAD DATATABLE
                vm.from = selectedStartDate;
                vm.to = selectedEndDate;
                vm.datatable.ajax.reload();
                vm.data()
            })
            
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
