<template>
    <div class="element-box p-3">
        <form @submit.prevent="formSubmit">
            <h5 class="form-header"><i class="icon-feather-grid m-r-10 mr-2"></i> <translate>Pilares</translate></h5>
            <hr>
            <!-- ALL PROGRAMS -->
            <div class="form-group m-b-5">               
                <tableDynamic :dataTable="form.pilars"></tableDynamic>
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
// Import component tableDynamic
import tableDynamic from '../../../helpers/tableDynamic'

export default {
    props:['appDomain','siteName', 'auth'],
    data(){
        return {
            loading : false,
            form:{
                pilars:{
                    name:'programs',
                    type: 'pilar',
                    category:'project',
                    columns:{
                        name:'Nombre del Programa',
                        slug:'Clave',
                        comment:'Descripción'
                    },
                    required:[ 'name', 'slug'],
                    rowsDelete:[],
                    rows:[]
                }
            }
        }
    },
    components:{
        'tableDynamic' : tableDynamic
    },
    methods:{
        data:async function(){
            let vm = this
            await axios.post(api.showParameters )
            .then(async function (response) {
                if(response.data.success){
                    if (response.data.categories != undefined ) {
                        let rows = _.where(response.data.categories , { category:'project', type:'pilar' })
                        vm.form.pilars.rows= []
                        rows.forEach(function(value, key){
                            vm.form.pilars.rows.push(_.pick( value ,'id', 'name', 'slug', 'comment')  )
                        })
                    }
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
                    axios.put(api.updateParametersCategories, vm.form )
                    .then(async function (response) {
                        if(response.data.success){
                            toastr.success(response.data.msg)
                            await vm.data()
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
    mounted: async function(){
        let vm = this
        EventBus.$on("categories", function(categories) {
            if(categories != undefined )
            {
                let rows = _.where(categories , { category:'project', type:'pilar' })
                rows.forEach(function(value, key){
                   vm.form.pilars.rows.push(_.pick( value ,'id', 'name', 'slug', 'comment')  )
                })
            } 
        })
    }
}
</script>