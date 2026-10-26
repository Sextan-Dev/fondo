<template>
    <div data-backdrop="static" data-keyboard="false" class="onboarding-modal modal fade animated" id="formAddProofModal" role="dialog" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-centered" role="document">
            <div class="modal-content text-center">
                <button aria-label="Close"  @click="closeForm()" class="close" data-dismiss="modal" type="button">
                    <span class="close-label" v-translate>Cerrar</span>
                    <span class="os-icon os-icon-close"></span>
                </button>
                <div class="onboarding-side-by-side">
                    <div class="onboarding-media">
                        <img alt="" :src="appDomain + '/images/bigicon6.png'" width="200px">
                    </div>
                    <div v-if="!successMesaage" class="onboarding-content with-gradient">
                        <h4 class="onboarding-title" v-translate>Listado de Comprobantes</h4>
                        <div  v-if="type=='operation'" class="alert alert-warning borderless">
                            <h6 class="alert-heading" v-translate>Atención</h6>
                            <p v-translate>
                                Aquí de muestran los Comprobante adejuntados al momento de realizar la operación de pago correspondiente a la orden de pago seleccionada.
                            </p>
                        </div>                 
                        <form @submit.prevent="formSubmit" id="formModalProof" enctype="multipart/form-data">                            
                           <div class="row">
                               <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="" v-if="type!='operation'">
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
                                                            <a  v-if="type=='operation'" target="_blank" :href="appDomain+'/uploads/operations/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                                            <a  v-else target="_blank" :href="appDomain+'/uploads/documents/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                                        </td>
                                                        <td class="text-left row-actions"><a class=" option  danger" href="#" @click.prevent="removeFile(index) "><i class="icon-feather-trash-2"></i></a> </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <input v-if="type!='operation'" name="files[]" type="file"  multiple="" class="form-control" >
                                    </div>                                    
                                </div>
                           </div>
                            <!-- BUTTONS FORM -->
                            <div class="form-buttons-w" v-if="type!='operation'">
                                <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                                    <span v-show="loading"><translate>Guardando</translate>...</span>
                                    <span v-show="!loading"><translate>Guardar</translate></span>
                                </button>
                                <button class=" btn btn-light" data-dismiss="modal" type="button"  @click="closeForm()"  v-translate> Cancelar</button>
                            </div>
                            <!-- END BUTTONS FORM -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
	import {siteName,appDomain} from './../../config/app';
    import jwtToken from '../../helpers/jwt-token';
    import {api} from "../../config/jwt-routes";
    import { EventBus } from './../../core/eventBus.js';
    export default {
        data() {
			return {
                loading:false,
                successMesaage:false,
                siteName: siteName,
                appDomain: appDomain,
                filesList:null,
                document:{} ,
                type:null  
			}
        },        
        methods:{
            removeFile:function(index){
                this.filesList.splice(index, 1)                
            },
            show: async function( id, type ){
                var vm = this;
                await axios.post( api.utilsShowContent, {id : id} )
                .then(function (response) {
                    if(response.data.success){
                        vm.document = response.data.data
                        let files = (type== 'operation') ?  JSON.parse(vm.document.paidProofs) : JSON.parse(vm.document.files)
                        if(_.isObject(files)){
                            vm.filesList = files
                        }
                    } else {
                        toastr.error(response.data.msg);
                    }
                    
                })
                .catch(function (error) {
                    console.log(error);
                })
            },
            closeForm(){
                let vm = this
                vm.type = null
                var errors = vm.errors
                $('input[type=file]').val('') 
                setTimeout(function () {
                    errors.clear()
                }, 1)
            },
            formSubmit:async function() {
                let vm = this
                var errors = vm.errors;
                this.$validator.validateAll().then((result) => {
                    if (result){
                        let formData = new FormData(document.getElementById('formModalProof'));
                        formData.append('filesOld',JSON.stringify(vm.filesList))
                        formData.append('id',vm.document.id)
                        formData.append('id',vm.document.id)
                        vm.loading = true
                        axios.post(api.utilsUpdateProof, formData )
                        .then(async function (response) {
                            if(response.data.success){
                                $('input[type=file]').val('') 
                                vm.show(vm.document.id)
                                toastr.success(response.data.msg);
                                vm.loading=false               
                               
                            }else{
                                toastr.error(response.data.msg);
                                vm.loading=false               
                            }
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            vm.loading=false
                        })
                    }
                })
            }
        },
        mounted: async function(){
            let vm = this

            EventBus.$on("addProof", async function(data) {
                vm.filesList = null
                vm.type = data.type
                await vm.show(data.id, data.type)
                $('#formAddProofModal').modal()
            });
        }
    }
</script>