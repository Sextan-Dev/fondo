<template>
	<div class="content-box">
		<div class="element-wrapper">

            <div class="element-actions">
                <form class="form-inline justify-content-sm-end">
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
            
            <h6 class="element-header">
                <default-header></default-header>
            </h6>

            <div class="element-box">
              
                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-padded" id="table" width="100%"></table>                    
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
                from: moment().format('YYYY-MM-DD'),
                to: moment().format('YYYY-MM-DD'),
                datatable: null,
                loading : true        
            }
        },
        mounted: function(){
            var vm = this;
            
            //DATATABLE
            vm.datatable = $('#table').DataTable({
                columns: [
                    { 
                        data: 'updated_at',
                        title: '<translate>Fecha</translate>'
                    },
                    { 
                        data: 'user',
                        title: '<translate>Usuario</translate>',
                    },
                    { 
                        data: 'type',
                        title: '<translate>Tipo</translate>',
                    },
                    { 
                        data: 'name',
                        title: '<translate>Descripcíon</translate>',
                    },
                    { 
                        data: 'element',
                        title: '<translate>Elemento</translate>',
                        className: 'lighter'
                    },
                    { 
                        data: 'updates',
                        title: '<translate>Modif.</translate>',
                        className: 'lighter',
                        render: function(data, type, row, meta) {
                            if (data) { data = JSON.parse(data); }
                            if (data && data.constructor === Array) {
                                var table = '<table class="table table-sm table-bordered">';
                                $.each( data, function (key,val) {
                                    table += '<tr><td>'+val[0]+'</td><td>'+val[1]+'</td><td>'+val[2]+'</td></tr>';
                                });
                                table += '</table>';
                                return table;
                            } else {
                                return data;
                            }
                        }
                    },
                ],
                ajax: {
                    url: api.selectAction,
                    type: "POST",
                    data: function(){ return {from:vm.from,to:vm.to}; }
                }
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
            });

            vm.loading = false;

        }
    }

</script>
