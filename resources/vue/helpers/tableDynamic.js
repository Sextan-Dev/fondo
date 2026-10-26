import { Validator } from 'vee-validate'
require('bootstrap-daterangepicker'); 
require('bootstrap-datepicker'); 
const tableDynamic = {
    data: function () {
        return {
            article: null,
            // Name Unique for this table
            table : 'table-'+this.dataTable.name,
        }
    },   
    // Inject Validator Methods
    inject: ['$validator'], 
    // Import parent data
    props:['dataTable'],
    template:`<table :id="table" class="table table-striped table-sm" >
                <thead>
                    <tr>
                        <th v-for="(title, key) in dataTable.columns" :key="key" :class="hasClass(key)">{{title}}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="body-cont">                  
                    <template v-if="dataTable.rows.length">
                        <tr  v-for="(row, index) in dataTable.rows" :key="index" >
                            <td v-if="key != 'id'" v-for="(value, key) in row" :key="key" :class="hasClass(key)"> 
                                <template v-if="createElement(key)  " >
                                    
                                    <v-select   v-if="dataTable.defineTypeElement[key].type == 'selectWithEvent'"  :name="key+'_'+index" placeholder="..." :key="index"
                                                v-model="row[key]"
                                                :value.sync="row[key]"
                                                :on-change="actionParent"
                                                v-validate="(typeof dataTable.defineTypeElement[key].validate !='undefined' )?dataTable.defineTypeElement[key].validate : 'required'"
                                                :taggable="(typeof dataTable.defineTypeElement[key].taggable !='undefined' )?dataTable.defineTypeElement[key].taggable : true"
                                                :options="dataTable.defineTypeElement[key].data"
                                                :class="{  'input': true, 'is-invalid': errors.has(key+'_'+index) }">
                                    </v-select>

                                    <v-select   v-if="dataTable.defineTypeElement[key].type == 'select'"  :name="key+'_'+index" placeholder="..." :key="index"
                                                v-model="row[key]"
                                                v-validate="(typeof dataTable.defineTypeElement[key].validate !='undefined' )?dataTable.defineTypeElement[key].validate : 'required'"
                                                :taggable="(typeof dataTable.defineTypeElement[key].taggable !='undefined' )?dataTable.defineTypeElement[key].taggable : true"
                                                :options="dataTable.defineTypeElement[key].data"
                                                :class="{  'input': true, 'is-invalid': errors.has(key+'_'+index) }">
                                    </v-select>

                                    <input v-if="dataTable.defineTypeElement[key].type == 'date'"  :date-index="index" :date-model="key" :date-table="dataTable.name"  :class=" {'input': true, 'is-invalid': errors.has(key+'_'+index) }" v-validate="(typeof dataTable.defineTypeElement[key].validate !='undefined' )?dataTable.defineTypeElement[key].validate : 'required'" type="text" :name="key+'_'+index" class="date  form-control" v-model="row[key]" >
                                    <input v-if="dataTable.defineTypeElement[key].type == 'number'" :class="{'input': true, 'is-invalid': errors.has(key+'_'+index) }" v-validate="(typeof dataTable.defineTypeElement[key].validate !='undefined' )?dataTable.defineTypeElement[key].validate : 'required'" type="number" :name="key+'_'+index" class=" form-control" v-model="row[key]" >
                                    <input v-if="dataTable.defineTypeElement[key].type == 'checkbox'" type="checkbox" :name="key+'_'+index" class=" form-control" v-model="row[key]" >
                                    
                                    <div v-if="dataTable.defineTypeElement[key].type == 'plain'">
                                        <span>{{row[key]}}</span>
                                        <input  type="hidden" :name="key+'_'+index" class=" form-control" v-model="row[key]" >
                                    </div>
                                    <div v-if="dataTable.defineTypeElement[key].type == 'number-edit'">
                                        <a @dblclick="displayInput(key+'_'+index)" :id="key+'_'+index+'_label'" >{{formatPrice(row[key])}}</a>
                                        <input @blur="hideInput(key+'_'+index)" type="number" 
                                        v-validate="(typeof dataTable.defineTypeElement[key].validate !='undefined' )?dataTable.defineTypeElement[key].validate : 'required'"
                                        style="display:none" :name="key+'_'+index" 
                                        class=" form-control" 
                                        v-model="row[key]" >
                                    </div>
                                    

                                </template>

                                <template v-else>
                                    <div v-if="dataTable.required.length && dataTable.required.includes(key) ">
                                        <input :class="{'input': true, 'is-invalid': errors.has(key+'_'+index) }" v-validate="'required'" :name="key+'_'+index" class="form-control" v-model="row[key]" >                                        
                                    </div>
                                    <input v-else  :name="key+'_'+index" class="form-control" v-model="row[key]" >
                                </template>
                            </td>
                            <td class="text-center row-actions"><a href="#" class="danger" @click.prevent="removeRow(index)"><i class="icon-feather-trash-2"></i></a></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot  v-if="dataTable.task">
                    <tr>
                        <th colspan="10" class="pl-3 pr-3">
                            <button type="button" class="btn btn-block btn-primary add-row-table" @click.prevent="addNewRow()"><i class="fa fa-plus"></i></button>
                        </th>
                    </tr>
                    <tr>
                        <th colspan="4" >
                            <div class="form-group">                                
                                <v-select v-model="article" placeholder="Catalogo" :name="dataTable.name+'_v-select'"
                                    :options="dataTable.inventory">
                                </v-select>
                            </div>
                        </th>
                        <th colspan="6" class="pl-3 pr-3">
                            <a title="Agregar elemento del catalogo" ><button style="margin-bottom: 13px;" type="button"    class=" bolder btn btn-block btn-primary add-row-table" @click.prevent="addNewRowWhitArticle()"><i class="fa fa-plus"></i></button></a>
                        </th>
                    </tr>
                </tfoot>
                <tfoot v-else>
                    <tr >
                        <th colspan="10" class="pl-3 pr-3">
                            <button type="button" class="btn btn-block btn-primary add-row-table" @click.prevent="addNewRow()"><i class="fa fa-plus"></i></button>
                        </th>
                    </tr>
                </tfoot>
            </table>`,
    updated: function(){
        let vm = this
        $('.date').daterangepicker(
            {   singleDatePicker: true, autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
            }
        );
        $('.date').on('apply.daterangepicker', function(ev, picker) {
           let data = []
            data.index = $(ev.target).attr('date-index')
            data.name = $(ev.target).attr('date-model')
            data.value = picker.startDate.format('YYYY-MM-DD')
            data.table = $(ev.target).attr('date-table')
            vm.$parent.$emit('updateDate', data )
        });
        $('.select2').select2();
    },
    watch: {
        dataTable: function(){
           return this.dataTable
        }
    },
    methods: {
        displayInput:function(input){
            $('input[name='+input+']').show()
            $('input[name='+input+']').focus()
            $('#'+input+'_label').hide()
        },
        hideInput:function(input){
            $('input[name='+input+']').hide()            
            $('#'+input+'_label').show()
        },
        actionParent:function(e){
           console.log(e)
        },

        hasElement(key){
            if(_.has(this.dataTable.defineTypeElement ,key )){
                return true
            }else{
                return false
            }
        },
        hasClass(key){            
            if(this.hasElement(key)){
                if(_.has( this.dataTable.defineTypeElement[key], 'class')){
                    return this.dataTable.defineTypeElement[key].class 
                }else{
                    return ''
                }
            }else{
                return ''
            }
            
        },  
        // Remove Row
        removeRow:function(index){
            // Remove Index of Array Parent
            console.log(index)
            if (this.dataTable.rows[index]['id']!='') {
                this.dataTable.rowsDelete.push(this.dataTable.rows[index]);
            }
            //console.log(this.dataTable.rows[index])
            this.dataTable.rows.splice(index,1);
        },
        createElement(key){
            if(_.has(this.dataTable.defineTypeElement ,key )){
                return true
            }else{
                return false
            }
        },  
        addNewRowWhitArticle:function(){
            let vm = this
            if(this.article==null){
                alert('Seleccione el articulo que se va a agregar')
                return false
            }
             // Get colums keys 
            let keys = Object.keys(this.dataTable.columns)
            let newItem = {}
            // Create a new Object with empty values and columns keys
            keys.forEach(function(value){
                if(_.has(vm.dataTable.defineTypeElement ,value )){
                    if(_.has(vm.dataTable.defineTypeElement[value] , 'default')){
                        newItem[value] = vm.dataTable.defineTypeElement[value].default
                    }else{
                        if(vm.dataTable.defineTypeElement[value].type == 'number'){
                            newItem[value] = 0
                        }else{
                            newItem[value] = ''
                        }
                    }
                }else{
                    newItem[value] = ''
                }
            })
            newItem['id'] = ''
            
            //newItem['id'] = ''
            if(this.dataTable.name == 'debitTable'){
                newItem['out_article'] = this.article.value+' - '+this.article.label;
                newItem['out_amount'] = this.article.price;
                newItem['out_cost'] = this.article.cost;
                this.article=null   
            }
            //console.log(this.dataTable.name)
            // Push new Object in the original array
            this.dataTable.rows.push(newItem) 
        },
        // Add New Row
        addNewRow:function(){
            let vm = this
            // Get colums keys 
            let keys = Object.keys(this.dataTable.columns)
            let newItem = {}
            // Create a new Object with empty values and columns keys
            keys.forEach(function(value){
                if(_.has(vm.dataTable.defineTypeElement ,value )){

                    if(_.has(vm.dataTable.defineTypeElement[value] , 'default')){
                        newItem[value] = vm.dataTable.defineTypeElement[value].default
                    }else{

                        if(vm.dataTable.defineTypeElement[value].type == 'number'){
                            newItem[value] = 0
                        }else{
                            newItem[value] = ''
                        }
                    }
                }else{
                    newItem[value] = ''
                }
               
            })
            newItem['id'] = ''
            // Push new Object in the original array
            this.dataTable.rows.push(newItem)            
        }
    },
    mounted(){
    }
}
export default tableDynamic;