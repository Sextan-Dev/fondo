<template>
<div class="content-box">
    <div class="element-wrapper">

        <h6 class="element-header">
            <span v-if="$route.params.id" class="default-title"> <i class="icon-feather-edit mr-1"></i>
                <translate>Editar Proyecto</translate>
            </span>
            <span v-else class="default-title"> <i class="icon-feather-plus-circle mr-1"></i>
                <translate>Nuevo Proyecto</translate>
            </span>
            <button @click.prevent="$router.push({name:'ProjectList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i>
                <translate>Todos los Proyectos</translate>
            </button>
            <button v-if="$route.params.id" @click.prevent="$router.push({name:'TaskList',params: {project: $route.params.id} })" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i>
                <translate>Ver Actividades</translate>
            </button>
        </h6>

        <div class="element-box">         
            <!-- Form Users New & Edit-->
            <form  @submit.prevent="formSubmit" novalidate="true">
                <div v-show="!ready" class="progress mb-2"><div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar progress-bar-striped " role="progressbar" style="width: 100%"></div></div>        
                <div class="row" v-show="ready" >
                    <div class="col-sm-8">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-wpforms m-r-10 mr-2"></i>Datos Generales
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label  class="required" v-translate>Nombre del Proyecto</label>
                                            <el-input name="name" placeholder="..." v-model="form.name" v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('name') }">
                                                <template slot="append"><i class="os-icon os-icon-agenda-1"></i></template>
                                            </el-input>
                                            <div class="invalid-feedback" v-show="errors.has('name')">
                                                {{ errors.first('name') }}
                                            </div>                                            
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12" v-if="showDataConta == 1">
                                        <div class="alert alert-warning borderless">
                                            <strong>Atención</strong>
                                            <p>Este valor es solo de caracter informativo, el monto real es calculado en base al total de ingresos registrados en este proyecto.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6" v-if="showDataConta == 1">
                                        <div class="form-group">
                                            <label v-translate>Monto de Inversión</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="fa fa-dollar"></i></div>
                                                </div>
                                                <input  v-model="form.amount_invertion" type="number" name="amount_invertion" class="form-control md-6" placeholder="..."  v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('amount_invertion') }">
                                                <!-- Error Message -->
                                                <div class="invalid-feedback" v-show="errors.has('amount_invertion')">
                                                    {{ errors.first('amount_invertion') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>                                     
                                </div>
                                <div class="row" >
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label  class="required" v-translate>Donantes</label>
                                            <span class="help">Desde Donantes</span>
                                            <el-select name="donors" v-model="form.donors" filterable placeholder="..." v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('donors') }">
                                                <el-option v-for="item in optionsDonors" :key="item.value" :label="item.label" :value="item.value">
                                                </el-option>
                                            </el-select>
                                            <div class="invalid-feedback" v-show="errors.has('donors')">
                                                {{ errors.first('donors') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">                                    
                                    <div class="col-md-6">
                                        <div class="form-group ">
                                            <label v-translate>Lider del Proyecto</label>
                                          
                                             <el-select name="user_id" v-model="form.user_id" v-validate="''" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('user_id') }">
                                                <el-option v-for="item in optionsUsers" :key="item.value" :label="item.label" :value="item.value">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('user_id')">
                                                {{ errors.first('user_id') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" v-translate>Estatus</label>
                                            <el-select name="status" v-model="form.status" v-validate="'required'" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('status_id') }">
                                                <el-option v-for="item in optionsStatus" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                          
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('status')">
                                                {{ errors.first('status') }}
                                            </div>
                                        </div>
                                    </div>                                   
                                </div>  

                                <div class="row"  v-if="showDataConta == 1">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="os-icon os-icon-bar-chart-stats-up m-r-10 mr-2"></i>Configuración de Costo Operativo
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row"  v-if="showDataConta == 1">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning borderless">
                                            <strong>Atención</strong>
                                            <p>Este valor es solo de caracter informativo, el monto real es calculado en base al total transferido hacia la cuenta COSTO OPERATIVO.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Monto de Costo Operativo</span>
                                            </label><br>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="fa fa-dollar"></i></div>
                                                </div>
                                                <input  v-model="form.monitoring_fix" type="number" name="monitoring_fix" class="form-control md-6" placeholder="..."  v-validate="''" :class="{'input': true, 'is-invalid': errors.has('monitoring_fix') }">
                                                <!-- Error Message -->
                                                <div class="invalid-feedback" v-show="errors.has('monitoring_fix')">
                                                    {{ errors.first('monitoring_fix') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="row" v-if="form.id && form.donors">
                                    <div class="col-md-6">
                                        <a class="element-box el-tablo" href="#">
                                            <div class="label">Inversion Total Recibida</div>
                                            <div class="value" style="font-size:30px!important">${{formatPrice(donations)}}</div>
                                            <div class="trending trending-up-basic">
                                                <i class="os-icon os-icon-arrow-2-up"></i>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a class="element-box el-tablo" href="#">
                                            <div class="label">Costo Operativo Congelado</div>
                                            <div class="value" style="font-size:30px!important">${{formatPrice(monitoring)}}</div>
                                            <div class="trending trending-up-basic">
                                                <i class="os-icon os-icon-arrow-2-up"></i>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a class="element-box el-tablo" href="#">
                                            <div class="label">Saldo por ejecutar</div>
                                            <div class="value" style="font-size:30px!important">${{formatPrice(donations - monitoring )}}</div>
                                            <div class="trending trending-up-basic">
                                                <i class="os-icon os-icon-arrow-2-up"></i>
                                            </div>
                                        </a>
                                    </div>
                                </div> -->
                                <div class="row" v-if="form.id && form.donors">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-calendar m-r-10 mr-2"></i>Temporalidad
                                            </h6>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <br>
                                            <div class="form-check d-inline-block mt-1 mr-3">
                                                <label class="form-check-label">
                                                    <input class="form-check-input" name="temporality" type="radio" value="1" :class="{'input': true, 'is-invalid': errors.has('temporality') }" v-model="form.temporality" >Temporalidad
                                                </label>
                                            </div>
                                            <div class="form-check d-inline-block mt-1 mr-3">
                                                <label class="form-check-label">
                                                    <input class="form-check-input" name="temporality" type="radio" value="2" :class="{'input': true, 'is-invalid': errors.has('temporality') }" v-model="form.temporality" >Ministración
                                                </label>
                                            </div>
                                            <div class="invalid-feedback"  v-show="errors.has('temporality')" >{{ errors.first('temporality') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.id && form.donors && form.temporality == 1">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning borderless">
                                            <strong>Atención</strong>
                                            <p>Este periodo de fechas influye en la amotización del costo operativo.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3" v-if="form.id && form.donors && form.temporality == 1">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Fecha de Inicio</label><br>                                        
                                            <el-date-picker
                                                name="date_from"
                                                v-model="form.date_from"                                            
                                                type="date"                                            
                                                format="dd/MM/yyyy"										
                                                value-format="yyyy-MM-dd">
                                            </el-date-picker>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Fecha de Termino</label><br>                                        
                                            <el-date-picker
                                                name="date_to"
                                                v-model="form.date_to"                                            
                                                type="date"                                            
                                                format="dd/MM/yyyy"										
                                                value-format="yyyy-MM-dd">
                                            </el-date-picker>
                                        </div>
                                    </div>
                                </div>   
                                
                                <div class="row mb-3" v-if="form.id && form.donors && form.temporality == 2">
                                    <div class="col-md-12">
                                        <!-- <div class="form-group"> -->
                                            <table  class="table table-temporality" >
                                                <tbody>
                                                    <tr>
                                                        <th><label v-translate>Mes</label></th>
                                                        <th><label v-translate>Año</label></th>
                                                        <th><label v-translate>Monto</label></th>
                                                        <th><label v-translate>Acciones</label></th>
                                                    </tr>
                                                    <tr v-for="(item, index) in tableRows" :key="item.id">
                                                        <td>
                                                            <div class="col-md-12">
                                                                <div class="input-group">
                                                                    <el-select name="ministering_month[]" v-model="item.month" filterable placeholder="..." v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('ministering_month') }">
                                                                        <el-option v-for="item in months" :key="item.value" :label="item.label" :value="item.value">
                                                                        </el-option>
                                                                    </el-select>

                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="col-md-12">
                                                                <div class="input-group">
                                                                    <el-select name="ministering_year[]" v-model="item.year" filterable placeholder="..." v-validate="'required'" :class="{'input': true, 'is-invalid': errors.has('ministering_year') }">
                                                                        <el-option v-for="year in years" :key="year" :label="year" :value="year">
                                                                        </el-option>
                                                                    </el-select>

                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="col-md-12">
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text"><i class="fa fa-dollar"></i></div>
                                                                    </div>
                                                                    <input type="number" v-model="item.amount" name="ministering_amount[]" class="form-control md-6" placeholder="..."  :class="{'input': true, 'is-invalid': errors.has('ministering_amount') }">
                                                                    <!-- Error Message -->
                                                                    <div class="invalid-feedback" v-show="errors.has('ministering_amount')">
                                                                        {{ errors.first('ministering_amount') }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="row-actions">
                                                                <a class="option danger deleteItem" href="#" @click.prevent="deleteRow(index)" ><i class="icon-feather-trash-2"></i></a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>                                               
                                            </table>
                                        <div class="form-buttons-w">
                                            <button class="btn btn-primary" type="button" @click.prevent="insertRow('', '', '')">
                                                <span>
                                                    <translate>Agregar</translate>
                                                </span>
                                            </button>
                                        </div>
                                        <!-- </div> -->
                                    </div>
                                </div>   
                                
                                <div class="row" v-if="form.id && form.donors">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Avance del proyecto</label><br>
                                            <el-input-number v-model="form.progress" controls-position="right" :min="0" :max="100"></el-input-number>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.id && form.donors">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Pilar</label>
                                            <el-select name="pilar" v-model="form.pilar" v-validate="''" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('pilar') }">
                                                <el-option v-for="item in optionsPilars" :key="item.id" :label="item.name" :value="item.name">
                                                </el-option>
                                            </el-select>
                                          
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('pilar')">
                                                {{ errors.first('pilar') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.id && form.donors">
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Programa</label>
                                            <el-select name="program" v-model="form.program" v-validate="''" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('program') }">
                                                <el-option v-for="item in optionsPrograms" :key="item.id" :label="item.name" :value="item.name">
                                                </el-option>
                                            </el-select>
                                          
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('program')">
                                                {{ errors.first('program') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Proyecto Sombrilla</label>                                          
                                            <el-select name="project_umbrella" v-model="form.project_umbrella" v-validate="''" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('project_umbrella') }">
                                                <el-option v-for="item in optionsProjectsUmbrella" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('project_umbrella')">
                                                {{ errors.first('project_umbrella') }}
                                            </div>
                                        </div>
                                    </div>                                    
                                </div>
                                <div class="row" v-if="form.id && form.donors">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label v-translate>Objetivo del Proyecto</label><br>
                                            <editor id="description" v-model="form.description" :init="config"/>
                                        </div>
                                    </div>
                                </div>
                            </div>  
                        </div>
                        <div class="row" v-if="form.id && form.donors">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-users m-r-10 mr-2"></i>Involucrados
                                            </h6>
                                        </div>
                                    </div>
                                </div>                                
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label v-translate>Aliados</label>
                                            <span class="help">Desde aliados</span>
                                            <el-select v-model="form.allies" multiple filterable placeholder="...">
                                                <el-option v-for="item in optionsAllies" :key="item.value" :label="item.label" :value="item.value">
                                                </el-option>
                                            </el-select>
                                        </div>
                                    </div>
                                </div>     
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <el-checkbox v-model="form.comunication_required" :true-label="1" :false-label="0">Requiere Comunicacion</el-checkbox>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.comunication_required">
                                    <div class="col-md-6" v-if="form.comunication_required">
                                        <div class="form-group">
                                            <label>Opciones de comunicación</label>
                                            <el-select v-model="form.comunication_type" multiple filterable placeholder="...">
                                                <el-option v-for="item in optionsComunicationTypes" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                        </div>
                                    </div>  
                                    <div class="col-md-6" v-if="form.comunication_required">
                                        <div class="form-group">
                                            <label>Productos de comunicación</label>
                                            <el-select v-model="form.comunication_product" multiple filterable placeholder="...">
                                                <el-option v-for="item in optionsComunicationProducts" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                        </div>
                                    </div>                                                                
                                </div>                         
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <el-checkbox v-model="form.volunteers_required" :true-label="1" :false-label="0">Requiere Voluntariado</el-checkbox>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.id && form.volunteers_required">
                                    <div class="element-wrapper pb-0 mt-4">
                                        <h6 class="element-header">
                                            <i class="fa fa-bar-chart m-r-10 mr-2"></i>Detalles de Voluntariado
                                        </h6>
                                    </div>
                                </div>        
                                <div class="row" v-if="form.id && form.volunteers_required">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Numero de Voluntarios</label>
                                            <table  class="table table-padded" >
                                                <tbody>
                                                    <tr  v-for="(values, key) in form.volunteering">
                                                        <td v-safe-html="key"></td>
                                                        <th><el-input-number v-model="form.volunteering[key]" controls-position="right" :min="0" ></el-input-number></th>
                                                    </tr>
                                                </tbody>                                                
                                            </table>
                                        </div>    
                                    </div>    
                                </div>    
                                <div class="row" v-if="form.id && form.volunteers_required">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Horas de voluntariado</label><br>
                                            <el-input-number v-model="form.volunteering_hours" controls-position="right" :min="0" ></el-input-number>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.id && form.volunteers_required">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label v-translate>Descripcion de Actividades</label><br>
                                            <editor id="volunteering_description" v-model="form.volunteering_description" :init="config"/>
                                        </div>
                                    </div>
                                </div>      
                            </div>
                        </div>
                        <div class="row" v-if="form.id && form.donors">
                            <div class="col-md-12">                                
                                <div class="row">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-bar-chart m-r-10 mr-2"></i>Detalles de inversión
                                            </h6>
                                        </div>
                                    </div>
                                </div>        
                               
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label v-translate>Otras necesidades de inversion</label><br>
                                            <editor id="others_features_invertion" v-model="form.others_features_invertion" :init="config"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-if="form.id && form.donors">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group ">
                                            <img :src="appDomain + '/nu/'+$language.current+'/SDG.png'" style="max-width: 70%;" onerror="javascript:$(this).hide()" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group ">
                                            <label v-translate>Organización Beneficiaria</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="fa fa-bars"></i></div>
                                                </div>
                                                <input type="text" name="org_beneficiary" class="form-control md-6" placeholder="..." value="" v-model="form.org_beneficiary" v-validate="''" :class="{'input': true, 'is-invalid': errors.has('org_beneficiary') }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12" style="align-self: flex-end;">
                                        <div class="form-group ">
                                            <label v-translate>Objetivos</label>
                                            <v-select name="category_id" placeholder="..." v-model="form.ods" multiple :options="optionsCategoriesGoals" :class="{'input': true, 'is-invalid': errors.has('category_id') }">
                                            </v-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('category_id')">
                                                {{ errors.first('category_id') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.ods">
                                    <div class="col-xs-3 col-md-3" v-for="(item, index) in sdgImg" :key="index">
                                        <div class="form-group ">
                                            <img :src="appDomain + '/nu/'+$language.current+'/'+item+'.jpg'" style="max-width: 50%;" onerror="javascript:$(this).hide()" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-if="form.id && form.donors">
                            <div class="col-md-12">                               
                                <div class="row">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-handshake-o m-r-10 mr-2"></i>Detalle de Beneficiarios
                                            </h6>
                                        </div>
                                    </div>
                                </div>        
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Beneficiarios directos estimados</label>
                                            <div class="input-group">
                                                <input type="text" name="beneficiary_direct" class="form-control md-6" placeholder="..." value="" v-model="form.beneficiary_direct" v-validate="''" :class="{'input': true, 'is-invalid': errors.has('beneficiary_direct') }">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="fa fa-users"></i></div>
                                                </div>
                                                <!-- Error Message -->
                                                <div class="invalid-feedback" v-show="errors.has('beneficiary_direct')">
                                                    {{ errors.first('beneficiary_direct') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Beneficiarios indirectos estimados</label>
                                            <div class="input-group">
                                                <input type="text" name="beneficiary_indirect" class="form-control md-6" placeholder="..." value="" v-model="form.beneficiary_indirect" v-validate="''" :class="{'input': true, 'is-invalid': errors.has('beneficiary_indirect') }">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="fa fa-users"></i></div>
                                                </div>
                                                <!-- Error Message -->
                                                <div class="invalid-feedback" v-show="errors.has('beneficiary_indirect')">
                                                    {{ errors.first('beneficiary_indirect') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>                                   
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <table  class="table table-padded" >
                                                <thead>
                                                    <tr>
                                                        <th>Rango de Edades</th>
                                                        <th>Cantidad</th>
                                                    </tr>
                                                </thead>
                                                <tbody>                                                    
                                                    <tr v-for="(values, ages) in form.beneficiary_details">
                                                        <td v-safe-html="ages"></td>
                                                        <td>
                                                            <input type="number" class="form-control" v-model="form.beneficiary_details[ages]">
                                                        </td>
                                                    </tr>                                                    
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label v-translate>Perfil de Beneficiarios</label><br>
                                            <editor id="beneficiary_perfil" v-model="form.beneficiary_perfil" :init="config"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                                

                        <div class="row" v-if="form.id && form.donors">
                            <div class="col-md-12">                                
                                <div class="row">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-map-o m-r-10 mr-2"></i>Detalle de la Localidad
                                            </h6>
                                        </div>
                                    </div>
                                </div>        
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label v-translate>Estado</label><br>
                                            <span class="help">Seleccione una opción</span><br>
                                            <el-select v-model="form.zone_impact_state" filterable multiple name="zone_impact_state" default-first-option placeholder="..">
                                                <el-option v-for="item in optionsZoneImpactStated" :key="item.value" :label="item.value" :value="item.value">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('zone_impact_state')">
                                                {{ errors.first('zone_impact_state') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label v-translate>Municipio</label><br>
                                            <span class="help">Seleccione o escribe nueva</span><br>
                                            <el-select v-model="form.zone_impact_municipality" filterable allow-create multiple name="zone_impact_municipality" default-first-option placeholder="..">
                                                <el-option v-for="item in optionsZoneImpactMunicipality" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('zone_impact_municipality')">
                                                {{ errors.first('zone_impact_municipality') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label v-translate>Localidad</label><br>
                                            <span class="help">Seleccione o escribe nueva</span><br>
                                            <el-select v-model="form.zone_impact_locality" filterable multiple allow-create name="zone_impact_locality" default-first-option placeholder="..">
                                                <el-option v-for="item in optionsZoneImpactLocality" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('zone_impact_locality')">
                                                {{ errors.first('zone_impact_locality') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>   
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Público Objetivo</label>                                            
                                            <el-select v-model="form.target_audiences" filterable multiple name="public" default-first-option placeholder="..">
                                                <el-option v-for="item in optionsTargetAudiences" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('public')">
                                                {{ errors.first('public') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label v-translate>Grado de Rezago Social</label>
                                            <el-select v-model="form.social_grade" filterable multiple name="social" default-first-option placeholder="..">
                                                <el-option v-for="item in optionsSocialGrade" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                            <!-- Error Message -->
                                            <div class="invalid-feedback" v-show="errors.has('social_grade')">
                                                {{ errors.first('social_grade') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>                                                                            
                                                                                          
                            </div>
                        </div>                        
                        <div class="form-buttons-w">
                            <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                <span v-show="loading">
                                    <translate>Guardando</translate>...
                                </span>
                                <span v-show="!loading">
                                    <translate>Guardar</translate>
                                </span>
                            </button>
                            <button class=" btn btn-light" type="button" href="" @click.prevent="$router.push({name:'ProjectList'})" v-translate> Cancelar</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-md-12 ">
                                <div class="element-wrapper pb-0 mt-4">
                                    <h6 class="element-header">
                                        <i class="fa fa-paperclip m-r-10 mr-2"></i>Adjuntos
                                    </h6>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-if="form.id" >
                            <div class="col-md-12">
                                <el-upload
                                class="upload-demo"
                                :action="appDomain+ '/api/project/upload/attachments'"
                                :headers="{'Authorization':'Bearer ' + token, 'X-CSRF-TOKEN': csrfToken, id:form.id}"
                                multiple
                                :on-preview="attachmentPreview" 
                                :on-remove="attachmentRemove"        
                                :on-exceed="attachmentsExceed"
                                :on-success="attachmentsSuccess"                      
                                :file-list="form.attachments">
                                <el-button size="small" type="primary">Clic para subir Archivos</el-button>
                                </el-upload>
                            </div>
                        </div>
                        <small v-else>
                             <div class="alert alert-warning borderless">                                
                                <p>Para poder agregar archivos primero es necesario crear el proyecto</p>
                            </div>
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</template>

<script>
import {api} from "../../config/jwt-routes";
import jwtToken from '../../helpers/jwt-token';
export default {
    props: ['auth', 'appDomain', 'siteName', 'configPlugins'],
    data() {
        return {
            ready:false,
            loading: true,
            token: jwtToken.getToken(),
            csrfToken:window.Laravel.csrfToken,
            config: {
                menubar: false,
                branding: false,
                height: 100,
                plugins: [
                    'advlist table autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                    alignleft aligncenter alignright alignjustify | \
                    table tabledelete | tableprops tablerowprops tablecellprops | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol \
                    bullist numlist outdent indent | removeformat | help'
            },            
            optionsPilars: [],
            optionsPrograms: [],
            optionsProgramsAvailables: [],
            optionsProjectsUmbrella: [],
            optionsCategories: [],                        
            optionsDonors: [],
            optionsAllies: [],
            optionsUsers: [],            
            optionsCategoriesGoals: [],            
            optionsZoneImpactStated:[],
            optionsZoneImpactMunicipality:[],
            optionsZoneImpactLocality:[],
            optionsComunicationProducts:[
                "Fotografia",
                "Video",
                "Diseño de Materiales",
                "Materiales para redes sociales",
                "Medios de comunicacion",
                "Redaccion de historias"
            ],
            optionsComunicationTypes:[
                "Estrategia de comunicación personalizada con presupuesto ",
                "Estrategia básica de comunicación sin costo",
                "Integrar nota en boletín mensual de aliados ",
                "Informe final o de medio término con diseño de plantilla. (No personalizado)"
            ],
            optionsSocialGrade:[
                "Muy Alto",
                "Alto",
                "Medio",
                "Bajo",
                "Muy Bajo",

            ],
            optionsTargetAudiences:[
                "Primera infancia",
                "Primaria",
                "Jóvenes",
                "Adultos",
                "Comunidad"
            ],
            optionsBeneficiaryAges:[
                "0 a 5",
                "6 a 11",
                "12 a 18",
                "19 a 26",
                "27 a 59",
                "mas de 60"

            ],   
            optionsStatus:[
                "En ejecución",
                "Concluido",
                "Cancelado"
            ],  
            optionsMonitoring:[],    
            filesList: null,
            sdgImg: [],
            ods: [],
            preventWatchPilar:false,
            preventWatchProgram:false,     
            monitoring:0,   
            donations:0, 
            form: {
                name: '',
                progress:1,
                program:null,                            
                pilar:null,
                project_umbrella:null,
                status: 'En ejecución',
                donors: null,
                allies:'',        
                ods: [], 
                user_id: null,
                description: '',                
                files: '',
                attachments:[],
                zone_impact_state:null,
                zone_impact_municipality:null,
                zone_impact_locality:null,
                date_from:moment().format('YYYY-MM-DD'),
                date_to:moment().format('YYYY-MM-DD'),
                amount_invertion:0, 
                others_features_invertion:'',
                beneficiary_perfil:'',
                location_info:'',       
                target_general:'',
                target_specifics:'',
                beneficiary_direct:'0',
                beneficiary_indirect:'0',
                target_audiences:'',
                social_grade:'',    
                org_beneficiary:'', 
                comunication_required:0,
                volunteers_required:0,
                comunication_product:'',
                comunication_type:'',
                beneficiary_ages:null,
                beneficiary_details:{
                    "0 a 5" : 0,
                    "6 a 11" : 0,
                    "12 a 18" : 0,
                    "19 a 26" : 0,
                    "27 a 59" : 0,
                    "mas de 60" : 0
                },
                volunteering:{
                    "Número de voluntarios hombres":0,
                    "Número de voluntarios mujeres":0,
                    "Número de voluntarios niñas":0,
                    "Número de voluntarios niños":0
                },
                volunteering_hours: 0,
                volunteering_description: null,
                monitoring:0,
                monitoring_fix:0,
                monitoring_type:'fix',
                temporality: 1,
                ministering_value: {}
            },
            showDataConta:0,
            tableRowsLength:1,
            tableRows:[
                    {
                        "id": 1,
                        "month": "...",
                        "year": "...",
                        "amount": ""
                    }
            ],
            months:[
                {
                    "label": "Enero",
                    "value": 1
                },
                {
                    "label": "Febrero",
                    "value": 2
                },
                {
                    "label": "Marzo",
                    "value": 3
                },
                {
                    "label": "Abril",
                    "value": 4
                },
                {
                    "label": "Mayo",
                    "value": 5
                },
                {
                    "label": "Junio",
                    "value": 6
                },
                {
                    "label": "Julio",
                    "value": 7
                },
                {
                    "label": "Agosto",
                    "value": 8
                },
                {
                    "label": "Septiembre",
                    "value": 9
                },
                {
                    "label": "Octubre",
                    "value": 10
                },
                {
                    "label": "Noviembre",
                    "value": 11
                },
                {
                    "label": "Diciembre",
                    "value": 12
                }
            ],
            years:[]
        }
    },

    computed: {
        pilar:function(){
            return this.form.pilar  
        },
        program:function(){
            return this.form.program  
        },
        categories: function () {
            return this.form.ods
        }
    },

    watch: {

        'pilar': function(value){
            if(!this.preventWatchPilar){
                this.optionsPrograms = _.where(this.optionsProgramsAvailables, {pilar: value})
                this.form.program = null
                this.form.project_umbrella = null
            }else{
                this.preventWatchPilar = false
            }

        },

        'program': function(value){
            if(!this.preventWatchProgram){
                this.form.project_umbrella = null
                if(value){
                    let projects = _.findWhere(this.optionsPrograms, {name: value})            
                    this.optionsProjectsUmbrella = ( _.isNull(projects.others) || !projects.others) ? [] : JSON.parse(projects.others)
                }else{
                    this.optionsProjectsUmbrella = []
                }
            }else{
                this.preventWatchProgram = false
            }
        },

        'categories': function () {
            let vm = this
            let sdgImg = []
            _.each(vm.form.ods, function (item, key) {
                sdgImg.push(item.slug)
            })
            vm.sdgImg = sdgImg.filter(function (elem, index, self) {
                return index === self.indexOf(elem);
            })
        }
    },

    methods: {
        // ATTACHMENTS
        async attachmentsSuccess(file, fileList){
            this.form.attachments.push(file)
        },

        async attachmentPreview(file, fileList){
            window.open(file.url)
        },

        async attachmentRemove(file, fileList){
            let vm = this               
            await axios.post(api.removeAttachmentProject, {file: file, id:vm.form.id})
            .then(function (response) {                        
                if(response.data.success){
                    vm.form.attachments = response.data.attachments          
                }else{
                    toastr.error(response.data.msg);
                }
            })
            .catch(function (error) {
                toastr.error(error);
                console.log(error);
            })
        },

        async attachmentsExceed(file, fileList){
            toastr.error("10 files maximum");
        },
        // GET FILTERS & COUNTS
        data: async function () {
            var vm = this;
            await axios.get(api.dataProject)
                .then(function (response) {
                    if (response.data.success) {
                        vm.optionsMonitoring = response.data.monitoring                        
                        vm.optionsPilars = response.data.pilars                        
                        vm.optionsProgramsAvailables = response.data.programs                                                
                        vm.optionsDonors = response.data.donors                        
                        vm.optionsAllies = response.data.allies                        
                        vm.optionsUsers = response.data.users
                        vm.optionsCategoriesGoals = response.data.goals
                        vm.optionsZoneImpactStated = response.data.zone_impact_state
                        vm.optionsZoneImpactMunicipality = response.data.zone_impact_municipality
                        vm.optionsZoneImpactLocality = response.data.zone_impact_locality
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    toastr.error(error);
                    console.log(error);
                })
        },       

        // Show User data for Edit
        showItem: async function (id) {
            var vm = this;
            if (id) {
                await axios.get(api.showProject + '/' + id)
                .then(function (response) {
                    if (response.data.success) { 
                        vm.preventWatchPilar = !_.isNull(response.data.data.pilar)
                        vm.preventWatchProgram =  !_.isNull(response.data.data.program)
                        vm.monitoring = response.data.monitoring
                        vm.donations = response.data.donations
                        vm.form = response.data.data
                        vm.tableRows=[];
                        if (vm.form.temporality == 2 && vm.form.ministering_value){                            
                            Object.keys(vm.form.ministering_value).forEach(function (item) {
                                vm.insertRow(vm.form.ministering_value[item].month, vm.form.ministering_value[item].amount, vm.form.ministering_value[item].year)
                            })
                        }
                    } else {
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
        formSubmit() {
            var vm = this;
            this.$validator.validateAll().then((result) => {
                if (result) {
                    vm.loading = true;
                    const mMonth = document.querySelectorAll('input[name="ministering_month[]"]');
                    const mAmount = document.querySelectorAll('input[name="ministering_amount[]"]');
                    const mYear = document.querySelectorAll('input[name="ministering_year[]"]');
                    let objectMinisteringVal = {}
                    for (let index = 0; index < mMonth.length; ++index) {
                        let month = mMonth[index].value
                        let amount = mAmount[index].value 
                        let year = mYear[index].value 
                        objectMinisteringVal[index] =  {'year' : year, 'month' : month, 'amount' : amount }
                    }
                    console.info(objectMinisteringVal)
                    vm.form.ministering_value = objectMinisteringVal
                    if (this.$route.params.id || this.form.id) {
                        var apiRoute = api.updateProject                
                    } else {
                        var apiRoute = api.storeProject                      
                    }

                    axios.post(apiRoute, vm.form)
                    .then(async function (response) {
                        if (response.data.success) {
                            toastr.success(response.data.msg);      
                            vm.form.id = response.data.id                        
                        } else {
                            toastr.error(response.data.msg);
                        }
                        vm.loading = false;
                        $('input[type=file]').val('')
                    })
                    .catch(function (error) {
                        toastr.error(error);
                        console.log(error);
                        vm.loading = false;
                    })

                }
            })
        },
        validateIsConta:async function (){
            var vm = this;
            await axios.post(api.validateContaProject)
            .then(function (response) {
                if (response.data.contaData != undefined) {
                    vm.showDataConta = response.data.contaData
                }
            })
            .catch(function (error) {
                console.log(error);
            })
        },
        insertRow(month = "", amount = "", year= "") {
            let vm = this
            let newTotalRow = vm.tableRowsLength + 1
            vm.tableRowsLength = newTotalRow
            vm.tableRows.push(
                {
                    "id": newTotalRow,
                    "month": month,
                    "year": year,
                    "amount": amount
                }
            )
        },
        deleteRow(id){
            let vm = this
            vm.$delete(vm.tableRows, id)
        },
        getYears(){
            let vm = this
            let currentDate = new Date()
            let currentYear = currentDate.getFullYear()
            let nextyear = currentYear + 10
            let yearsArray = []
            yearsArray.push(currentYear-2);
            yearsArray.push(currentYear-1);
            while (currentYear <=  nextyear) {
                yearsArray.push(currentYear++);
            }
            vm.years = yearsArray
        }
    },

    mounted: async function () {
        await this.data();
        if (this.$route.params.id) {
            await this.showItem(this.$route.params.id);
        }

        this.ready= true
        this.loading = false
        this.validateIsConta()
        this.getYears()
    }
}
</script>

<style>
.el-select {
    width: 100% !important;
}

.el-date-editor {
    width: 100% !important;
}

.v-select .selected-tag {
    overflow: hidden;
}
</style>
