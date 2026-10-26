<template>
    <div class="element-box p-3">
        <form @submit.prevent="formSubmit">
            <h5 class="form-header"><i class="fa fa-bar-chart m-r-10 mr-2"></i> <translate>Parametros de Autorización</translate></h5>
            <hr>
            <!-- ALL indicators -->
            <div class="form-group m-b-5" v-if="ready">               
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="">Encargados de Autorizar Ordenes de Pago:&nbsp;</label>
                            <el-select multiple v-model="usersValue" placeholder="...">
                                <el-option
                                v-for="item in userOptions"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id">
                                </el-option>
                            </el-select>
                            <!-- Error Message -->
                            <div class="invalid-feedback"  v-show="errors.has('contact_id')" >
                                {{ errors.first('contact_id') }}
                            </div>       
                        </div>
                    </div>
                </div>
            </div>
            <!-- SUBMIT BUTTONS -->
            <div class="form-buttons-w">
                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                    <span v-show="loading"><translate>Guardando</translate>...</span>
                    <span v-show="!loading"><translate>Guardar</translate></span>
                </button>
            </div>
        </form>
    </div>
</template>
<script>

import {api} from "../../../config/jwt-routes"
import {EventBus} from "../../../core/eventBus"

export default {
    props:['appDomain','siteName', 'auth'],
    
    data(){
        return {
            loading : false,
            ready : false,
            userOptions:[],
            usersValue:[],
            form:{
                rowsDelete:1,
                authorization:{
                    name:'authorization',
                    type: 'order',
                    category:'authorization',
                    columns:{
                        name:'',
                        slug:'',
                        comment:'Orden de Pago'
                    },
                    required:[ 'name', 'slug'],
                    rowsDelete:[],
                    rows:[]
                }
            }
        }
    },
    methods:{

        data:async function(){
            let vm = this
            await axios.get(api.listUser )
            .then(async function (response) {
                if(response.data.success){
                    vm.userOptions = response.data.users
                }else{
                    toastr.error(response.data.msg)
                }
                vm.loading = false
            })
            .catch(function (error) {
                vm.loading = false
                toastr.error(error);
                console.log(error);
            })
        },
        
        formSubmit:async function() {
            let vm = this
            this.$validator.validateAll().then((result) => {
                if (result){
                    vm.loading = true
                    
                    _.each(vm.usersValue, function(value, index){
                        vm.form.authorization.rows.push({name: value, type:'order', category:'authorization'})
                    })

                    axios.put(api.updateParametersCategories, vm.form )
                    .then(async function (response) {
                        if(response.data.success){
                            toastr.success(response.data.msg)
                        }else{
                            toastr.error(response.data.msg)
                        }
                        vm.loading = false
                    })
                    .catch(function (error) {
                        vm.loading = false
                        toastr.error(error);
                        console.log(error);
                    })
                }
            })
        }
    },
    created: async function(){

    },
    mounted: async function(){
        let vm = this

        vm.data();

        await EventBus.$on("categories", function(categories) {
            if(categories != undefined )
            {
                let rows = _.where(categories , { category:'authorization' }) 
                if(rows.length){
                    vm.form.authorization.rows = []                        
                    rows.forEach(function(value, key){
                        //console.log(value)
                        vm.usersValue.push(Number(value.name))
                       
                    })
                }else{
                   // vm.form.authorization.rows.push({id:'', name:null, slug:''})
                }
                vm.ready = true
            } 
        })

    }
}
</script>