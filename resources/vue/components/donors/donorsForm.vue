<template>
<div class="content-box">
    <div class="element-wrapper">

        <h6 class="element-header">
            <default-header></default-header>
            <button @click.prevent="$router.push({name:'DonorsList'})" class="btn btn-sm btn-primary"> <i class="icon-feather-menu mr-2"></i>
                <translate>Todos los Donantes</translate>
            </button>
        </h6>
        <div v-show="!ready" class="progress mb-2"><div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar progress-bar-striped " role="progressbar" style="width: 100%"></div></div>        
        <div v-if ="ready" class="element-box">
            <!-- Form Users New & Edit-->
            <form id="form" @submit.prevent="formSubmit" novalidate="true">
                <div class="row">
                    <div class="col-md-12">

                        <h5 v-if="$route.params.id" class="form-header"> <i class="icon-feather-edit mr-1"></i>
                            <translate>Actualizar Datos del Donante</translate>
                        </h5>
                        <h5 v-else class="form-header"> <i class="icon-feather-plus mr-1"></i>
                            <translate>Nuevo Donante</translate>
                        </h5>
                        <hr>

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
                                            <el-radio v-model="form.type" label="personal">Persona Fisica</el-radio>
                                            <el-radio v-model="form.type" label="company">Persona Moral</el-radio>
                                        </div>
                                    </div>
                                </div>
                                 <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label  class="required" v-translate>Grupo</label>
                                            <el-select name="groups" v-model="form.groups" multiple allow-create filterable placeholder="..."  :class="{'input': true, 'is-invalid': errors.has('groups') }">
                                                <el-option v-for="item in optionsGroups" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                            <div class="invalid-feedback" v-show="errors.has('groups')">
                                                {{ errors.first('groups') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" v-if="form.type=='company'">
                                    <div class="col-md-5">
                                        <div class="form-group">                                            
                                            <label>Nombre Comercial</label>
                                            <input type="text" v-model="form.name_comercial" :class="{'input': true, 'is-invalid': errors.has('name_comercial') }" data-vv-as=" " v-validate="''" name="name_comercial" class="form-control md-6" placeholder="...">
                                            <div class="invalid-feedback" v-show="errors.has('name_comercial')">{{ errors.first('name_comercial') }}</div>
                                        </div>
                                    </div> 
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">                                            
                                            <label>Nombre Completo</label>
                                            <input type="text" v-model="form.name" :class="{'input': true, 'is-invalid': errors.has('name') }" data-vv-as=" " v-validate="'required|min:3'" name="name" class="form-control md-6" placeholder="...">
                                            <div class="invalid-feedback" v-show="errors.has('name')">{{ errors.first('name') }}</div>
                                        </div>
                                    </div>   
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label v-translate>Ejecutiva de cuenta</label>   
                                            <div class="input-group">
                                                <div class="input-group-prepend" >                                                        
                                                    <div class="input-group-text"><i class="icon-feather-user"></i></div>
                                                </div>
                                                <input v-model="form.name_executive" type="text" name="name_executive"  class="form-control"  placeholder="..." value=""  >
                                            </div>
                                        </div>
                                    </div>                                                                       
                                </div>
                                <div class="row">
                                    <div class="col-md-5" v-if="form.type=='company'">
                                        <div class="form-group">
                                            <label v-translate>Razón Social</label>
                                            <input type="text" v-model="form.company" :class="{'input': true, 'is-invalid': errors.has('company') }"  name="company" class="form-control md-6" placeholder="...">
                                            <div class="invalid-feedback" v-show="errors.has('company')">{{ errors.first('company') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-5" v-if="form.type=='company'">
                                        <div class="form-group">
                                            <label v-translate>Giro Empresarial</label>
                                             <el-select name="activity" v-model="form.activity" v-validate="''" filterable placeholder="..." :class="{'input': true, 'is-invalid': errors.has('activity') }">
                                                <el-option v-for="item in optionsActivity" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>                                            
                                            <div class="invalid-feedback" v-show="errors.has('activity')">{{ errors.first('activity') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" v-if="form.type=='personal'">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label v-translate>Edad</label>
                                            <input type="number" v-model="form.age" :class="{'input': true, 'is-invalid': errors.has('age') }" data-vv-as=" " v-validate="'numeric'" name="age" class="form-control md-6" placeholder="..." >
                                            <div class="invalid-feedback" v-show="errors.has('age')">{{ errors.first('age') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label v-translate>Genero</label>
                                            <el-select v-model="form.gender" placeholder="...">
                                                <el-option :key="'m'" :label="'Masculino'" :value="'m'">
                                                </el-option>
                                                <el-option :key="'f'" :label="'Femenino'" :value="'f'">
                                                </el-option>
                                            </el-select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label v-translate>Correo</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="icon-feather-mail"></i></div>
                                                </div>
                                                <input type="email" :class="{'input': true, 'is-invalid': errors.has('email') }" data-vv-as=" " v-validate="'required|email'" v-model="form.email" name="email" class="form-control md-6" placeholder="..." >
                                                <div class="invalid-feedback" v-show="errors.has('email')">{{ errors.first('email') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group ">
                                            <label v-translate>Telefono</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text"><i class="icon-feather-phone"></i></div>
                                                </div>
                                                <input type="text" :class="{'input': true, 'is-invalid': errors.has('phone') }" data-vv-as=" " v-validate="'required'" v-model="form.phone" name="phone" class="form-control md-6" placeholder="..." >
                                                <div class="invalid-feedback" v-show="errors.has('phone')">{{ errors.first('phone') }}</div>
                                            </div>
                                        </div>
                                    </div>                                    
                                </div>

                                 <div class="row">
                                    <div class="col-md-12 ">
                                        <div class="element-wrapper pb-0 mt-4">
                                            <h6 class="element-header">
                                                <i class="fa fa-phone m-r-10 mr-2"></i>Datos De Contacto
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <table  class="table table-padded" >
                                                <thead>
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Telefono</th>
                                                        <th>Ext.</th>
                                                        <th>Correo</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>                                                    
                                                    <tr v-for="(item, key) in form.contact_details">                                                    
                                                        <td>
                                                            <input type="text" :name="'name-' + key" class="form-control" :class="{'input': true, 'is-invalid': errors.has('name-' + key) }" data-vv-as=" " v-validate="'required'" v-model="form.contact_details[key].name">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="'phone-' + key" class="form-control" :class="{'input': true, 'is-invalid': errors.has('phone-' + key) }" data-vv-as=" " v-validate="'required'" v-model="form.contact_details[key].phone">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" v-model="form.contact_details[key].ext">
                                                        </td>
                                                        <td>
                                                            <input type="text" :name="'email-' + key" class="form-control" :class="{'input': true, 'is-invalid': errors.has('email-' + key) }" data-vv-as=" " v-validate="'required'" v-model="form.contact_details[key].email">
                                                        </td>
                                                        <td>
                                                            <a href="#" class="text-danger" @click.prevent="removeContact(key)"><span class="text-danger"><i class="fa fa-trash"></i></span></a>
                                                        </td>
                                                    </tr>                                                    
                                                </tbody>
                                            </table>
                                            <button class="btn btn-primary btn-sm" style="width:100%" type="button" @click.prevent="addContact()" :disabled="loading">                                            
                                                <span>
                                                    <i class="fa fa-plus"></i>
                                                    <translate>Añadir</translate>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group ">
                                    <label v-translate>Direccion</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                        </div>
                                        <input v-model="form.address" type="text" :class="{'input': true, 'is-invalid': errors.has('address') }" name="address" class="form-control md-6" placeholder="..." >
                                        <div class="invalid-feedback" v-show="errors.has('address')">{{ errors.first('address') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group ">
                                    <label v-translate>Ciudad</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                        </div>
                                        <input v-model="form.city" type="text" :class="{'input': true, 'is-invalid': errors.has('city') }" name="city" class="form-control md-6" placeholder="..." >
                                        <div class="invalid-feedback" v-show="errors.has('city')">{{ errors.first('city') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group ">
                                    <label v-translate>Codigo Postal</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                        </div>
                                        <input v-model="form.postal" type="text" :class="{'input': true, 'is-invalid': errors.has('postal') }" data-vv-as=" " v-validate="'numeric'" name="postal" class="form-control md-6" placeholder="..." >
                                        <div class="invalid-feedback" v-show="errors.has('postal')">{{ errors.first('postal') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group ">
                                    <label v-translate>Pais</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text"><i class="os-icon os-icon-mail-18"></i></div>
                                        </div>
                                        <input v-model="form.country" type="text" :class="{'input': true, 'is-invalid': errors.has('country') }" name="country" class="form-control md-6" placeholder="..." >
                                        <div class="invalid-feedback" v-show="errors.has('country')">{{ errors.first('country') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group ">
                                    <label v-translate>Categoria</label>
                                    <el-select v-model="form.deal" placeholder="...">
                                        <el-option :label="'Unidad de Negocio'" :value="'Unidad de Negocio'">
                                        </el-option>
                                        <el-option :label="'Planta'" :value="'Planta'">
                                        </el-option>
                                        <el-option :label="'Corporativo'" :value="'Corporativo'">
                                        </el-option>
                                        <el-option :label="'Centros de distribucion'" :value="'Centros de distribucion'">
                                        </el-option>
                                    </el-select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group ">
                                            <label v-translate>Marcas</label><br>
                                            <span class="help">Seleccione o escribe nueva</span>
                                            <el-select v-model="form.brands" filterable allow-create default-first-option placeholder="...">
                                                <el-option v-for="(item , key) in optionsBrands" :key="key" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Presupuesto estimado anual para colaboración con Fondo Unido</span>
                                            </label><br>
                                            <input type="number" v-model="form.budget" :class="{'input': true, 'is-invalid': errors.has('budget') }" data-vv-as=" " v-validate="'decimal'" name="budget" class="form-control md-6" placeholder="..." >
                                            <div class="invalid-feedback" v-show="errors.has('budget')">{{ errors.first('budget') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group ">
                                            <label v-translate>Detalle de la operación</label>
                                            <span class="help" v-safe-html="'debe agregar información relacionada año fiscal, timbrado CFDI, gestión y distribución de recursos, referencia bancaria, etc.'"></span>

                                            <editor id="description_html" v-model="form.description_html" :init="config" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Valores de Responsabilidad Social</span>
                                            </label><br>
                                            <textarea v-model="form.social" name="social" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <el-checkbox v-model="form.rse" :true-label="1" :false-label="0">¿Cuentan con estrategia global de RSE?</el-checkbox>
                                            <el-checkbox v-model="form.rse_themes" :true-label="1" :false-label="0">Integran temas de RSE</el-checkbox>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Estrategia Global</span>
                                            </label><br>
                                            <textarea v-model="form.global_strategy" name="global_strategy" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Estrategia Local</span>
                                            </label><br>
                                            <textarea v-model="form.global_strategy_mx" name="global_strategy_mx" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Aliados RSE</span>
                                            </label><br>
                                            <textarea v-model="form.allied_rse" name="allied_rse" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Voluntariado antes de FUM</span>
                                            </label><br>
                                            <textarea v-model="form.voluntary_fum" name="voluntary_fum" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Presupuesto asignado a temas de RSE</span>
                                            </label><br>
                                            <textarea v-model="form.budget_rse_themes" name="budget_rse_themes" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Grupos de Afinidad</span>
                                            </label><br>
                                            <textarea v-model="form.groups_affinity" name="groups_affinity" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Interés particular de marketing social o visibilidad</span>
                                            </label><br>
                                            <textarea v-model="form.interest" name="interest" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group ">
                                            <label v-translate>Retos que enfrentan al interior de su compañía</label>
                                            <editor id="challenge" v-model="form.challenge" :init="config" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group ">
                                            <label v-translate>Grupos que dificulten o pongan obstáculos para el desarrollo de los proyectos</label>
                                            <editor id="groups_difficulty" v-model="form.groups_difficulty" :init="config" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group ">
                                            <label v-translate>Competencia directa</label>
                                            <textarea v-model="form.direct_competition" name="direct_competition" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group ">
                                            <label v-translate>Notas Mediaticas de interés</label>
                                            <textarea v-model="form.notes" name="notes" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group ">
                                            <label v-translate>Areas de Interés</label>
                                            <span class="help">Seleccione o escribe nueva</span>
                                            <el-select v-model="form.areas_interest" multiple filterable allow-create default-first-option placeholder="...">
                                                <el-option :label="'Salud'" :value="'Salud'">
                                                </el-option>
                                                <el-option :label="'Educación'" :value="'Educación'">
                                                </el-option>
                                                <el-option :label="'Estabilidad Financiera'" :value="'Estabilidad Financiera'">
                                                </el-option>
                                                <el-option v-for="item in optionsAreas" :key="item" :label="item" :value="item">
                                                </el-option>
                                            </el-select>
                                        </div>
                                    </div>
                                </div>
                               
                                
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label for="">
                                                <span v-translate>Comentarios</span>
                                                <span class="help" v-translate>Este campo es opcional</span>
                                            </label><br>
                                            <textarea v-model="form.comment" name="comment" class="form-control" rows="3"></textarea>
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
                                    <button class=" btn btn-light" type="button" @click.prevent="$router.push({name:'Directory'})" v-translate> Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>  

</div>
</template>

<script>
import {
    api
} from "../../config/jwt-routes";
export default {
    props: ['appDomain', 'siteName', 'auth', 'configPlugins'],
    data() {
        return {
            ready:false,
            config: {
                menubar: false,
                branding: false,
                height: 300,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                alignleft aligncenter alignright alignjustify | \
                bullist numlist outdent indent | removeformat | help'
            },
            loading: true,
            optionsActivity:[                
                "Industrial",
                "Comercial",
                "De Servicios"

            ],
            optionsBrands: [],
            optionsAreas: [],                        
            optionsProjects:[],
            optionsTasks:[],
            optionsAccounts:[],
            operations:[],
            operationsTotals:{
                income:0,
                outcome:0
            },
            optionsGroups:[
                "AVN",
                "Matching corporativo",
                "Corporativo Nacional",
                "Corporativo Internacional",
                "Donantes individuales ",
                "Donativo en especie ",
                "Reciclaje"
            ],
            transaction:{
                project_id: null,
                task_id:null,
                account_id:null,
                amount: 0,
                amount_execute:0,
                monitoring : 0
            },
            form: {
                type: 'personal',
                name: null,
                category: 'Donador',
                company: null,
                email: null,
                phone: '',
                groups:[],
                age: '',
                gender: '',
                address: '',
                country: '',
                postal: '',
                contact_details:[
                    {
                        name:'',
                        phone:'',
                        ext:'',
                        email:''
                    },
                ],
                comment: ''
            }
        }
    },
    
    computed:{

        project_id: function (){
            return this.transaction.project_id
        }

    },
    
    watch:{
        'project_id': function (){
            let project_id = this.transaction.project_id
             this.transaction.task_id = null
            if(project_id){                
                this.optionsTasks = JSON.parse(JSON.stringify(this.optionsProjects[project_id].tasks))
            }else{
                this.optionsTasks = []
            }
        }
    },

    methods: {
        addContact(){
            this.form.contact_details.push( {
                name:'',
                phone:'',
                ext:'',
                email:''
            })
        },
        removeContact(key){
            this.form.contact_details.splice(key, 1);
        },
       
        // Get treeview and groups list
        data: async function () {
            var vm = this;
            await axios.get(api.dataContact)
                .then(function (response) {
                    if (response.data.success) {                        
                        vm.optionsBrands = response.data.brands
                        vm.optionsAreas = response.data.areas
                        vm.optionsProjects = response.data.projects                        
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    console.log(error);
                    toastr.error(error);
                })
        },

        // Show User data for Edit
        showItem: async function (id) {
            var vm = this;
            if (id) {
                await axios.post(api.showContact, {
                        id: id
                    })
                    .then(function (response) {                        
                        if (response.data.success) {
                            let data = JSON.parse(JSON.stringify(response.data.data))
                            data.groups = (_.isNull(data.groups)) ? [] : JSON.parse(data.groups)
                            data.groups_difficulty = (_.isNull(data.groups_difficulty)) ? '' : data.groups_difficulty
                            data.challenge = (_.isNull(data.challenge)) ? '' : data.challenge
                            data.description_html = (_.isNull(data.description_html)) ? '' : data.description_html
                            data.brands = (_.isNull(data.brands)) ? [] : JSON.parse(data.brands)
                            data.areas_interest = (_.isNull(data.areas_interest)) ? [] : JSON.parse(data.areas_interest)
                            vm.operations = response.data.operations
                            vm.operationsTotals = response.data.operationsTotals
                            vm.optionsAccounts = response.data.operationsAccount
                            vm.form = data
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
                var redirectToList = false;
                if (result) {
                    vm.loading = true;
                    if (!this.$route.params.id) {
                        axios.post(api.storeContact, this.form)
                            .then(async function (response) {
                                if (response.data.success) {
                                    toastr.success(response.data.msg);
                                    vm.$router.push({
                                        name: "DonorsList"
                                    })
                                } else {
                                    toastr.error(response.data.msg);
                                }
                                vm.loading = false;
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                console.log(error);
                                vm.loading = false;
                            })
                    } else {
                        vm.form.id = this.$route.params.id;
                        axios.post(api.updateContact, this.form)
                            .then(async function (response) {
                                if (response.data.success) {
                                    toastr.success(response.data.msg);
                                    setTimeout(() => {
                                        window.scrollTo(0, 0);
                                    }, 100);
                                    vm.loading = false;
                                } else {
                                    toastr.error(response.data.msg);
                                    vm.loading = false;
                                }
                            })
                            .catch(function (error) {
                                toastr.error(error);
                                vm.loading = false;
                                console.log(error);
                            })
                    }

                }
            })
        }

    },
    mounted: async function () {
        await this.data();
        if (this.$route.params.id) {
            await this.showItem(this.$route.params.id);
        }
        this.loading = false
        this.ready = true
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
</style>
