<template>
	<div class="content-box">
		<div class="element-wrapper">        
            <h6 class="element-header">
                <span v-if="$route.params.id" class="default-title"> <i class="icon-feather-edit mr-1"></i> <translate>Editar Operación</translate></span>
                <span v-else class="default-title"> <i class="icon-feather-plus-circle mr-1"></i> <translate>Nueva Operación</translate></span>
                <button @click.prevent="$router.push({name:'OperationList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i> <translate>Estado de Cuenta</translate> </button>
            </h6>
            <div v-show="!ready" class="progress mb-2"><div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar progress-bar-striped " role="progressbar" style="width: 100%"></div></div>
            <div class=" grid simple "  v-if="ready" :class=" form.type == 'income' ? 'green' : (form.type=='outcome' ? 'red' : 'blue') ">
                <div class="element-box">
                <!-- Form Users New & Edit-->
                    <form  enctype="multipart/form-data" id="form" @submit.prevent="formSubmit"  novalidate="true">
                        <div class="row">
                            
                            <div class="col-sm-12">
                                <!-- OPERATION CONFIG -->                                                               
                                <div class="row">
                                    <legend><translate>Datos generales</translate></legend>
                                    <div class="col-md-12" v-if="!$route.params.content_id">
                                       <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Tipo</span>
                                            </label><br>                                            
                                            <el-radio :disabled="form.id?true:false" v-model="form.type" label="income">Ingreso</el-radio>
                                            <el-radio :disabled="form.id?true:false" v-model="form.type" label="outcome">Egreso</el-radio>
                                            <el-radio :disabled="form.id?true:false" v-model="form.type" label="transfer">Traspaso</el-radio>                                          
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha</label><br>
                                            <el-date-picker
                                                name="date"
                                                v-model="form.date"  
                                                v-validate="'required'" data-vv-as=" " :class="{'input': true, 'is-invalid': errors.has('date') }"                                          
                                                type="date"                                            
                                                format="dd/MM/yyyy"										
                                                value-format="yyyy-MM-dd">
                                            </el-date-picker>
                                            <div class="invalid-feedback" v-show="errors.has('date')" >{{ errors.first('date') }}</div>
                                          
                                        </div>
                                    </div>
                                    <div class="col-md-5" v-if="form.type!='transfer'">
                                        <div class="form-group">
                                            <label>
                                                <span v-translate>Folio</span>
                                            </label><br>
                                            <div class="input-group">
                                                <div class="input-group-prepend" >
                                                    <div class="input-group-text"><i class="os-icon os-icon-cv-2"></i></div>
                                                </div>
                                                <input type="text" name="reference" class="form-control"                                                         
                                                        v-validate="''"                                                        
                                                        :class="{'input': true, 'is-invalid': errors.has('reference')}"
                                                        v-model="form.reference">
                                                <div class="invalid-feedback" v-show="errors.has('reference')" >{{ errors.first('reference') }}</div>
                                            </div>
                                        </div>
                                    </div> 
                                </div>
                                <div class="row">  
                                    <div class="col-md-4" v-if="form.type=='outcome'">
                                        <div class="form-group">
                                            <label>                                                
                                                <span>Selecciona el Destino</span>
                                            </label><br>                                            
                                            <el-radio v-model="form.contact_type" label="Aliado">Aliados</el-radio>
                                            <el-radio v-model="form.contact_type" label="Proveedor">Proveedores</el-radio>  
                                            <el-radio v-model="form.contact_type" label="Donador">Donador</el-radio>  
                                        </div>                                              
                                    </div> 
                                    <div class="col-md-6" v-if="form.type!='transfer'">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-safe-html="form.contact_type"></span>
                                            </label>                                                                                                                              
                                            <el-select name="contact_id" v-model="form.contact_id" v-validate="'required'" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('contact_id') }">
                                                <template v-if="form.type == 'income'">
                                                    <el-option v-for="item in optionsContacts" :key="item.value" :label="item.label" :value="item.value">
                                                    </el-option>
                                                </template>
                                                <template v-else>
                                                    <template v-if="form.contact_type == 'Donador'">
                                                        <el-option v-for="item in optionsContacts" :key="item.value" :label="item.label" :value="item.value">
                                                        </el-option>
                                                    </template>
                                                    <template v-else>
                                                        <el-option v-for="item in optionsAllContacts" :key="item.value" :label="item.label" :value="item.value">
                                                        </el-option>
                                                    </template>
                                                </template>
                                            </el-select>                                            
                                            <div class="invalid-feedback" v-show="errors.has('contact_id')" >{{ errors.first('contact_id') }}</div>
                                        </div>
                                    </div>                                                                       
                                </div>
                                <!-- END IF NEW CLIENT -->                              

                                <!-- ORIGEN DE FONDOS -->
                                <div class="row">
                                    <legend v-if="form.type != 'income'"><translate>Procedencia de Fondos</translate></legend>
                                    <div class="col-md-4"  v-if="form.type!='income'">
                                        <div class="form-group" >
                                            <label class="required">
                                                <span>Donante</span>
                                            </label>                                            
                                            <br>                                          
                                            <el-select name="origin_donor_id" v-model="form.origin_donor_id" v-validate="'required'"  @change="updateCategoriesOrigin()" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('origin_donor_id') }">
                                                <el-option v-for="item in optionsDonors" :key="item.value" :label="item.label" :value="item.value">
                                                </el-option>
                                            </el-select>                                            
                                            <div class="invalid-feedback" v-show="errors.has('origin_donor_id')" >{{ errors.first('origin_donor_id') }}</div>                                    
                                        </div>                             
                                    </div>    
                                    <div class="col-md-4" v-if="form.type != 'income'">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Clasificación de Ingresos</span>
                                            </label>
                                            <br>                                                    
                                            <el-select name="category" v-model="originCategory" @change="updateSubCategoriesOrigin()" v-validate="'required'" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('category') }">
                                                <el-option v-for="item in optionsCategoriesOrigin" :key="item.category" :label="item.category" :value="item.category">
                                                </el-option>
                                            </el-select>                                                         
                                            <div class="invalid-feedback" v-show="errors.has('originCategory')" >{{ errors.first('originCategory') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" v-if="originCategory && form.type != 'income'">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Sub Clasificación de Ingresos</span>
                                            </label>
                                            <br>                                                    
                                            <el-select name="origin_subcategory" v-model="form.origin_subcategory" @change="updateProjectsOrigin()" v-validate="'required'" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('origin_subcategory') }">
                                                <el-option v-for="item in optionsSubCategoriesOrigin" :key="item.subcategory" :label="item.subcategory" :value="item.subcategory">
                                                </el-option>
                                            </el-select>                                                         
                                            <div class="invalid-feedback" v-show="errors.has('origin_subcategory')" >{{ errors.first('origin_subcategory') }}</div>
                                        </div>
                                    </div>       
                                    <div class="col-md-4" v-if="form.type!='income' && originCategory  && originCategory != 'Sin Clasificación' && form.origin_subcategory && optionsProjectsOrigin.length">
                                        <div class="form-group">
                                            <label :class="(form.type!='transfer') ?'required':''">Proyecto</label><br>
                                            <el-select v-model="form.origin_project_id" name="origin_project_id" @change="updateMaxAmount()"   v-validate="" data-vv-as=" " :class="{'input': true, 'is-invalid': errors.has('origin_project_id') }" filterable placeholder="...">                                            
                                                <el-option
                                                v-for="(item, key) in optionsProjectsOrigin"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id">
                                                </el-option>
                                            </el-select>
                                            <div class="invalid-feedback" v-show="errors.has('origin_project_id')">{{ errors.first('origin_project_id') }}</div>
                                        </div>
                                    </div>   
                                </div>
                               
                                <!-- <div class="row" v-if="form.type != 'income' && form.origin_category" style="display: none;">
                                    <div class="col-4 mt-2">
                                        <div class="element-balances" style="padding: 0px !important;">
                                            <div class="balance element-box" style="margin: 0px !important;background: rgba(236, 238, 245, 0.5) !important;width: 100%;">
                                                <div class="balance-title"><translate>Saldo Disponible:</translate></div>
                                                <div class="balance-value" style="font-size: 20px;">
                                                    ${{formatPrice(maxAmount)}}											
                                                </div>
                                            </div>
                                        </div>
                                    </div>	    
                                </div> -->


                                <!-- DESTINO DE FONDOS -->
                                <div class="row">
                                    <legend v-if="form.type == 'transfer'"><translate>Destino de Fondos</translate></legend>                                  
                                    <div class="col-md-4"  v-if="form.type=='transfer'">
                                        <div class="form-group" >
                                            <label class="required">
                                                <span>Donante</span>
                                            </label>
                                            <br>                                          
                                            <el-select name="target_contact_id" v-model="form.target_contact_id" v-validate="'required'" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('target_contact_id') }">
                                                <el-option v-for="item in optionsDonors" :key="item.value" :label="item.label" :value="item.value">
                                                </el-option>
                                            </el-select>                                            
                                            <div class="invalid-feedback" v-show="errors.has('target_contact_id')" >{{ errors.first('target_contact_id') }}</div>
                                    
                                        </div>                             
                                    </div>   
                                    <div class="col-md-4" v-if="form.type == 'transfer'">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Clasificación de Ingresos</span>
                                            </label>
                                            <br>                                                    
                                            <el-select name="target_category" v-model="form.target_category"  v-validate="'required'" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('target_category') }">
                                                <el-option :label="'Sin Clasificación'" :value="'Sin Clasificación'"></el-option>
                                                <el-option v-for="(item, key) in optionsCategories" :key="key" :label="key" :value="key">
                                                </el-option>
                                            </el-select>                                                         
                                            <div class="invalid-feedback" v-show="errors.has('target_category')" >{{ errors.first('target_category') }}</div>
                                        </div>
                                    </div> 
                                    <div class="col-md-4" v-if="form.target_category && form.type != 'income' &&  form.target_category != 'Sin Clasificación' ">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Sub Clasificación de Ingresos</span>
                                            </label>
                                            <br>                                                    
                                            <el-select name="target_subcategory" v-model="form.target_subcategory"  v-validate="'required'" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('target_subcategory') }">
                                                <el-option v-for="item in optionsSubCategoriesTarget" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>                                                         
                                            <div class="invalid-feedback" v-show="errors.has('origin_subcategory')" >{{ errors.first('origin_subcategory') }}</div>
                                        </div>
                                    </div>         
                                    <div class="col-md-4" v-if="form.type=='transfer' && form.target_category  && showProyect(form.target_category)">
                                        <div class="form-group">
                                            <label>Proyecto</label><br>
                                            <el-select v-model="form.target_project_id" name="target_project_id" data-vv-as=" " :class="{'input': true, 'is-invalid': errors.has('target_project_id') }" filterable placeholder="...">                                            
                                                <el-option :label="'Sin Proyecto'" :value="0"></el-option>
                                                <el-option
                                                v-for="(item, key) in optionsProjects"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id">
                                                </el-option>
                                            </el-select>
                                            <div class="invalid-feedback" v-show="errors.has('target_project_id')">{{ errors.first('target_project_id') }}</div>
                                        </div>
                                    </div>                                                           
                                </div>
                                                                                    
                                <div class="row">
                                    <legend><translate>Conceptos & Importes</translate></legend>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Forma de pago</span>
                                            </label>
                                            <span class="help" v-translate>Seleccione la forma de pago</span>
                                            <br>
                                            <div class="form-group">                                              
                                                <el-select name="mode" v-model="form.mode_id" v-validate="'required'" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('mode_id') }">
                                                    <el-option v-for="item in optionsMode" :key="item.value" :label="item.label" :value="item.value">
                                                    </el-option>
                                                </el-select>  
                                                <div class="invalid-feedback" v-show="errors.has('mode_id')" >{{ errors.first('mode_id') }}</div>
                                            </div>
                                        </div>
                                    </div>                                                 
                                </div> 

                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="">
                                                <span>Importe</span>
                                            </label><br>
                                            <div class="input-group">
                                                <div class="input-group-prepend" >
                                                    <div class="input-group-text"><i class="fa fa-dollar"></i></div>
                                                </div>
                                                <!-- v-validate="'required' + (form.type!='income' ? '|max_value:'+maxAmount : '')"  -->
                                                <input type="number" name="amount" class="form-control"                                                         
                                                        data-vv-as=" "
                                                        :disabled="$route.params.content_id"
                                                        :class="{'input': true, 'is-invalid': errors.has('amount')}"
                                                        v-model="form.amount">
                                                <div class="invalid-feedback" v-show="errors.has('amount')" >{{ errors.first('amount') }}</div>
                                            </div>
                                            <span class="bolder smaller" v-if="form.type!='income'" v-safe-html="'Importe Maximo: $' + formatPrice(maxAmount)"></span>
                                        </div>
                                    </div>
                                    <div class="col-sm-3" v-if="form.type=='income'">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>FEE %</span>
                                            </label><br>
                                            <el-select v-model="form.fee" name="project_id" data-vv-as=" " :class="{'input': true, 'is-invalid': errors.has('fee') }" filterable placeholder="...">                                            
                                                <el-option :label="'N/A'" :value="0"></el-option>
                                                <el-option v-for="item in optionsFee" :key="'fee-' + item.label" :label="item.label + '%'" :value="item.label"></el-option>
                                            </el-select>
                                        </div>
                                    </div> 
                                    <div class="col-md-4" v-if="form.type=='income'">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Clasificación de Ingresos</span>
                                            </label>
                                            <br>                                                    
                                            <el-select name="category" v-model="form.category" v-validate="'required'" data-vv-as=" " filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('category') }">
                                                <el-option :label="'Sin Clasificación'" :value="'Sin Clasificación'"></el-option>
                                                <el-option v-for="(item, key) in optionsCategories" :key="key" :label="key" :value="key">
                                                </el-option>
                                            </el-select>                                                         
                                            <div class="invalid-feedback" v-show="errors.has('category')" >{{ errors.first('category') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" v-if="form.category && form.type == 'income' && form.category!='Sin Clasificación'">
                                        <div class="form-group">
                                            <label class="required">
                                                <span v-translate>Sub Clasificación de Ingresos</span>
                                            </label>
                                            <br>                                                    
                                            <el-select name="subcategory" v-model="form.subcategory" v-validate="'required'" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('subcategory') }">
                                                <el-option v-for="item in optionsSubCategories" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>                                                         
                                            <div class="invalid-feedback" v-show="errors.has('subcategory')" >{{ errors.first('subcategory') }}</div>
                                        </div>
                                    </div>                                                                   
                                </div>

                                <div class="row" v-if="form.type=='income'">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <el-checkbox v-model="form.retain_operating_cost" name="retain_operating_cost" :true-label="1" :false-label="0">Retener costo operativo</el-checkbox>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" v-if="form.type=='income'">
                                    <div class="col-sm-3" v-if="form.retain_operating_cost == 1">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Retener costo operativo %</span>
                                            </label><br>
                                            <el-select v-model="form.percentage_operating_cost" name="percentage_operating_cost" data-vv-as=" " :class="{'input': true, 'is-invalid': errors.has('percentage_operating_cost') }" filterable placeholder="...">                                            
                                                <el-option :label="'N/A'" :value="0"></el-option>
                                                <el-option v-for="item in optionsMonitoring" :key="'percentage_operating_cost-' + item.label" :label="item.label + '%'" :value="item.label"></el-option>
                                            </el-select>
                                        </div>
                                    </div>
                                </div>

                                                              
                                <!-- COMMENTS & FILES -->
                                <div class="row"> 
                                    <legend><translate>Información Complementaria</translate></legend>                                   
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Comprobante(s)</span>
                                                <span class="help" v-translate>Seleccione el o los documentos que crea necesarios.</span>
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
                                                                <a target="_blank" :href="appDomain+'/uploads/operations/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                                            </td>
                                                            <td class="text-left row-actions"><a class=" option  danger" href="#" @click.prevent="removeFile(index) "><i class="icon-feather-trash-2"></i></a> </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                </div>
                                            <input name="files[]" type="file" multiple="" class="form-control" >
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Comentarios</span>
                                                <span class="help" v-translate>Este campo es opcional</span>
                                            </label><br>
                                            <textarea name="comment" class="form-control" v-model="form.comment" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <!-- END COMMENTS & FILES -->

                                <!-- BUTTONS FORM -->
                                <div class="form-buttons-w">
                                    <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                        <span v-show="loading"><translate>Guardando</translate>...</span>
                                        <span v-show="!loading"><translate>Guardar</translate></span>
                                    </button>
                                    <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'OperationList'})" v-translate> Cancelar</button>
                                </div>
                                <!-- END BUTTONS FORM -->
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
                loading : true,
                newClient:false,
                optionsContactsAvailables:[],
                optionsContactsAllAvailables:[],
                optionsContacts:[],
                optionsAllContacts:[],
                optionsDonors:[],                
                // Income classifications => subclassifications, from config/operations.php
                optionsCategories:{},
                optionsCategoriesOrigin:[],
                optionsSubCategoriesOrigin:[],
                optionsSubCategoriesTarget:[],
                optionsProjectsOrigin:[],
                optionsSubCategories:[],
                optionsTaskAvailables:[],  
                optionsCategoriesTarget: [],
                optionsProjectsTarget: [],
                optionsProjects:[],
                optionsFee:[],
                optionsMonitoring:[],
                filesList : null,        
                preventWatchType:false,
                preventWatchCategory:false,
                preventWatchProject:false,
                preventWatchContact:false,
                preventWatchOrigin:false, 
                maxAmount:false,  
                percentMonitoring:0,  
                project_duration:'', 
                originCategory: null,                   
                form:{
                    contact_type:'Donador',                   
                    date: moment().format('YYYY-MM-DD'),
                    type:'income',                    
                    contact_id: null,
                    donor_id: null,                 
                    category: 'Sin Clasificación',
                    subcategory:null,
                    mode_id:null,
                    reference:null,
                    account_id:null,
                    amount:0,
                    amount_execute:0,
                    monitoring:0,
                    vat:0,
                    fee:0,                  
                    files:'',
                    comment:'',
                    target_contact_id:null,
                    target_category:null,
                    target_subcategory:null,
                    target_project_id: null,
                    target_monitoring:0,
                    origin_contact_id:null,
                    origin_category:null,
                    origin_subcategory:null,
                    origin_project_id: null,
                    file:[],
                    project_id:0,
                    retain_operating_cost:0,
                    percentage_operating_cost:0
                }
            }
        },
        computed:{

            type: function(){
                return this.form.type
            },
            
            category: function(){
                return this.form.category
            },
            target_category: function(){
                return this.form.target_category
            },
            
            contact_type: function(){
                return this.form.contact_type
            },
            target_project_id:function(){
                return this.form.target_project_id
            }
        },

        watch:{

            'target_project_id': function(){
                if(this.form.target_project_id){
                    let project
                    project = _.findWhere(this.optionsProjects, {id: this.form.target_project_id})
                    this.percentMonitoring = project.monitoring
                    this.project_duration = moment(project.date_from).format('MM/YYYY') + ' - ' + moment(project.date_to).format('MM/YYYY')
                }else{
                    this.project_duration = '';
                }
            },

            'contact_type': function(){
                if(!this.preventWatchContact){
                    var errors = this.errors
                    this.form.contact_id = null
                    this.optionsContacts = _.where(this.optionsContactsAvailables, {category : this.form.contact_type})
                    this.optionsAllContacts = _.where(this.optionsContactsAllAvailables, {category : this.form.contact_type})
                    setTimeout(function () {errors.clear() }, 1)
                }else{
                    this.preventWatchContact= false
                }
            },

            'type' : function(){
                this.form.origin_subcategory = null;
                this.form.target_category = null;
                if(!this.preventWatchType){
                    this.form.monitoring = 0
                    this.form.fee = 0
                    if(this.form.type == 'income'){  
                        this.form.contact_type = 'Donador'
                        this.optionsContacts = _.where(this.optionsContactsAvailables, {category : 'Donador'})
                        this.optionsAllContacts = _.where(this.optionsContactsAllAvailables,  {category : 'Donador'})
                    }else{
                        this.form.contact_type = 'Aliado'
                        this.optionsContacts = _.where(this.optionsContactsAvailables, {category : 'Aliado'})
                        this.optionsAllContacts = _.where(this.optionsContactsAllAvailables, {category : 'Aliado'})
                    }
                }else{
                    this.preventWatchType = false
                }
            },
            
            'category': function(){
                if(!this.preventWatchCategory){
                    this.form.subcategory = null
                    this.optionsSubCategories = this.optionsCategories[this.form.category]
                }else{
                    this.preventWatchCategory = false
                }
            },

            'target_category': function(){            
                this.form.target_subcategory = null
                this.optionsSubCategoriesTarget = this.optionsCategories[this.form.target_category]
            
            }
        },
        methods:{

            async updateMaxAmount(){
                let vm = this
                let maxAmount 
                maxAmount = _.findWhere(vm.optionsProjectsOrigin, {id: vm.form.origin_project_id})
                vm.maxAmount = (maxAmount.totalAvailable) ? maxAmount.totalAvailable : 0
            },

            async updateCategoriesOrigin(){
                let vm = this
                console.log('here')
                vm.maxAmount = false
                await axios.get(api.balanceAccountOperations + '?contact_id=' + vm.form.origin_donor_id + '&resource=categories' )
                .then(function (response) {
                    if(response.data.success){   
                        vm.optionsCategoriesOrigin = response.data.items 
                        vm.optionsSubCategoriesOrigin = []
                        vm.optionsProjectsOrigin = []
                        vm.form.origin_project_id = null                      
                        //vm.form.origin_category = null
                        vm.form.origin_subcategory = null   
                        vm.originCategory = '';                   
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
                
            },

            async updateSubCategoriesOrigin(){
                let vm = this
                vm.maxAmount = false
                await axios.get(api.balanceAccountOperations + '?contact_id=' + vm.form.origin_donor_id + '&resource=subcategories&category='+vm.originCategory )
                .then(function (response) {
                    if(response.data.success){   
                        vm.optionsSubCategoriesOrigin = response.data.items 
                        vm.optionsProjectsOrigin = []
                        vm.form.origin_project_id = null                      
                        vm.form.origin_subcategory = null    
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
                
            },

            async updateProjectsOrigin(){
                let vm = this
                let maxAmount 
                maxAmount = _.findWhere(vm.optionsSubCategoriesOrigin, {subcategory: vm.form.origin_subcategory})
                vm.maxAmount = (maxAmount.totalAvailable) ? maxAmount.totalAvailable : 0
                await axios.get(api.balanceAccountOperations + '?contact_id=' + vm.form.origin_donor_id + '&resource=projects&category='+vm.originCategory+'&subcategory='+vm.form.origin_subcategory )
                .then(function (response) {
                    if(response.data.success){   
                        vm.optionsProjectsOrigin = response.data.items 
                        vm.form.origin_project_id = null                                          
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
                
            },           
            // Get data for Form & Set all selects
            data: async function(){
                var vm = this;
                await axios.post(api.dataOperation)
                .then(function (response) {
                    if(response.data.success){                        
                        vm.optionsMode = response.data.mode                        
                        vm.optionsFee = response.data.fee
                        vm.optionsMonitoring = response.data.monitoring
                        vm.optionsCategories = response.data.incomeCategories
                        vm.optionsProjects = response.data.projects                        
                        vm.optionsContactsAvailables = response.data.contacts
                        vm.optionsContactsAllAvailables = response.data.allContacts
                        vm.optionsContacts = _.where(response.data.contacts, {category:'Donador'})
                        vm.optionsDonors = _.where(response.data.contacts, {category:'Donador'})
                        vm.optionsAllContacts = _.where(response.data.allContacts, {category:'Donador'})
                    }else{
                        toastr.error(response.data.msg);
                    }                         
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
            },
            
            removeFile:function(index){
                this.filesList.splice(index, 1)                
            },

            // Show User data for Edit
            showItem:async function( item ){
                var vm = this;
                if(item){
                    await axios.post(api.showOperation, {item: item })
                    .then(async function (response) {                        
                        if(response.data.success){
                            vm.preventWatchCategory = true                            
                            vm.preventWatchProject = true                            
                            vm.preventWatchContact = true                            
                            vm.preventWatchOrigin = true                            
                            vm.preventWatchType = true                            
                            
                            let info = JSON.parse(JSON.stringify(response.data.item))
                            //data.origin_donor_id = data.contact_id
                            //data.origin_category = data.category
                            //data.origin_subcategory = data.subcategory
                            //data.origin_project_id = data.project_id
                            vm.form =  info
                            if(vm.form.type == 'outcome'){
                                vm.form.origin_donor_id = response.data.item.donor_id
                                await vm.updateCategoriesOrigin()

                                vm.originCategory = response.data.item.category
                                vm.form.origin_category = response.data.item.category

                                await vm.updateSubCategoriesOrigin()
                                vm.form.origin_subcategory = response.data.item.subcategory

                                await vm.updateProjectsOrigin()
                                vm.form.origin_project_id = response.data.item.project_id

                                vm.optionsCategoriesOuts = response.data.accounts
                                vm.optionsProjectsOuts = response.data.projects
                                vm.optionsContacts = _.where(vm.optionsContactsAvailables, {category : vm.form.contact_type})
                                vm.optionsAllContacts = _.where(vm.optionsContactsAllAvailables, {category : vm.form.contact_type})
                            }
                            let files = JSON.parse(vm.form.files)
                            if(_.isObject(files)){
                                vm.filesList = files
                            }
                            if (response.data.item.percentage_operating_cost) {
                                vm.form.retain_operating_cost  = 1
                            }
                        }else{
                            if(response.data.code != undefined && response.data.code == 404){
                                vm.$router.push({name:'PageNotFound'})
                            }else{
                                toastr.error(response.data.msg);
                            }
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
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading=true;
                        let formData = new FormData(document.getElementById('form'));
                        this.form.origin_category = this.originCategory
                        formData.append('form',JSON.stringify(this.form))
                        if(!this.$route.params.id){
                            var apiRoute = api.storeOperation;
                            var redirectToList = true           
                        }else{
                            formData.append('filesOld',JSON.stringify(this.filesList))
                            formData.append( 'id' ,this.$route.params.id )
                            formData.append( 'retain_operating_cost' , this.form.retain_operating_cost )
                            formData.append( 'percentage_operating_cost' , this.form.percentage_operating_cost )
                            var apiRoute =api.updateOperation
                            var redirectToList = false                        
                        }
                        
                        axios.post(apiRoute, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);
                                 if(redirectToList){
                                     vm.loading=false;        
                                     vm.$router.push({name:"OperationList"})
                                }else{
                                    let files = JSON.parse(response.data.data.files)
                                    if(_.isObject(files)){
                                        vm.filesList = files
                                    }
                                    $('input[type=file]').val('') 
                                    vm.loading=false;        
                                }                               
                            }else{
                                toastr.error(response.data.msg);
                                vm.loading=false;        
                            }        
                        })
                        .catch(function (error) {
                            vm.loading=false;        
                            toastr.error(error);
                            console.log(error);
                        })                         
                    }                    
                })
            },

            showProyect(clasification){
                if (clasification == 'Sin Clasificación' || clasification == 'Remanentes' || clasification == 'Costo Operativo') {
                    this.form.target_project_id = null;
                    return false;
                }
                return true;
            }
        },
        mounted: async function() { 
            let vm = this
            
            // this.$route.params.id = 204
            this.loading = false

            // Get Data for selects
            await this.data();

            // if Request has item id    
            if(this.$route.params.id) {
                await this.showItem(this.$route.params.id);                
            }                     
         
            vm.ready = true

        }
    }

</script>
<style>
    .el-select {
        width: 100%;
    }

    .form-check-label{
        cursor:pointer!important;
    }
</style>