<template>
    <div class="row">
        <div class="col-md-12">
            <form id="formDocuments" enctype="multipart/form-data" @submit.prevent="formSubmit"   novalidate="true">
                <h5 class="form-header"><i class="os-icon os-icon-ui-44 m-r-10 mr-2"></i> <translate>Documentos y Comprobantes</translate></h5>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="">
                                <span v-translate>Documento(s)</span>
                            </label><br>
                            <div  v-if="form.files">
                                <table class="table table-lightborder">
                                    <thead>
                                        <tr>
                                            <th  v-translate>Documentos Adjuntos</th>
                                            <th ></th>
                                            <th ></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for=" (item, index ) in form.files" :key="index">
                                            <td class="text-left"><div class="status-pill green" data-title="Complete" data-toggle="tooltip" data-original-title="" title=""></div>&nbsp;{{ item.file }}</td>
                                            <td class="text-left">
                                                <a target="_blank" :href="appDomain+'/uploads/task/files/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                            </td>
                                            <td class="text-left row-actions"><a class=" option  danger" href="#" @click.prevent="removeFile(index, 'files') "><i class="icon-feather-trash-2"></i></a> </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <span class="help" v-translate>Seleccione el o los documentos que crea necesarios.</span>
                            <input name="files[]" type="file" multiple="" class="form-control" >
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="">
                                <span v-translate>Comprobante(s)</span>
                            </label><br>
                            <div  v-if="form.proof">
                                <table class="table table-lightborder">
                                    <thead>
                                        <tr>
                                            <th v-translate>Comprobantes Adjuntos</th>
                                            <th ></th>
                                            <th ></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for=" (item, index ) in form.proof" :key="index">
                                            <td class="text-left"><div class="status-pill green" data-title="Complete" data-toggle="tooltip" data-original-title="" title=""></div>&nbsp;{{ item.file }}</td>
                                            <td class="text-left">
                                                <a target="_blank" :href="appDomain+'/uploads/task/proof/'+item.file"><i class="fa fa-cloud-download"></i></a> 
                                            </td>
                                            <td class="text-left row-actions"><a class=" option  danger" href="#" @click.prevent="removeFile(index, 'proof') "><i class="icon-feather-trash-2"></i></a> </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <span class="help" v-translate>Seleccione el o los documentos que crea necesarios.</span>
                            <input name="proof[]" type="file" multiple="" class="form-control" >
                        </div>
                    </div>
                </div>

                <div class="form-buttons-w">
                    <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                        <span v-show="loading"><translate>Guardando</translate>...</span>
                        <span v-show="!loading"><translate>Guardar</translate></span>
                    </button>
                    <button class=" btn btn-light" type="button" href="" @click.prevent="$router.push({name:'TaskList'})" v-translate> Cancelar</button>
                </div>
                
            </form>
        </div>
    </div>
</template>

<script>

	import {appDomain} from '../../config/app';
    import {api} from "../../config/jwt-routes";

	export default {
        data(){
            return {
                loading : true,
                appDomain: appDomain,
                proofList: null,
                filesList : null,
                       
            }
        }, 
        props:[ 'form'],
        methods:{
            // REMOVE FILE AND PROOF
            removeFile:function(index, type){
                if(type == 'proof'){
                    this.form.proof.splice(index,1)
                    //this.deleteFile( '/uploads/task/proof/'+ this.form.proof[index]['file'] , index, this.form.proof)

                }else{
                    this.form.files.splice(index,1)
                    //this.deleteFile( '/uploads/task/files/'+ this.form.files[index]['file'] , index, this.form.files)

                }
            },
            // Form submit for Update & Store
            formSubmit(){
                var vm = this;
                this.$validator.validateAll().then((result) => {
                    if (result) {
                        vm.loading=true;
                        let formData = new FormData(document.getElementById('formDocuments'));
                        formData.append('filesOld',JSON.stringify(this.form.files))
                        formData.append('proofOld',JSON.stringify(this.form.proof))
                        formData.append( 'id' ,this.$route.params.id )
                        axios.post(api.updateTaskDocuments, formData)
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);
                                vm.$parent.$emit('updateDocuments', response.data.task)
                                $('input[type=file]').val('')                  
                            }else{
                                toastr.error(response.data.msg);
                            }        
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            console.log(error);
                        })                         
                        vm.loading=false;        
                    }                    
                })
            }
            
            
        },
        mounted: async function() {
            let vm = this
            this.loading = false
        }
    }

</script>
