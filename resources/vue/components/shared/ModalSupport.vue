<template>
    <div data-backdrop="static" data-keyboard="false" class="onboarding-modal modal fade animated" id="supportModal" role="dialog" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-centered" role="document">
            <div class="modal-content text-center">
                <button aria-label="Close"  @click="closeForm()" class="close" data-dismiss="modal" type="button">
                    <span class="close-label" v-translate>Cerrar</span>
                    <span class="os-icon os-icon-close"></span>
                </button>
                <div class="onboarding-side-by-side">
                    <div class="onboarding-media">
                        <img alt="" :src="appDomain + '/images/bigicon5.png'" width="200px">
                    </div>
                    <!-- Only while open: the Airtable embed otherwise loads on every page. -->
                    <iframe v-if="open" class="airtable-embed" src="https://airtable.com/embed/shrJ09NSuzh1QNlIR?backgroundColor=cyan" frameborder="0" onmousewheel="" width="100%" height="533" style="background: transparent; border: 1px solid #ccc;"></iframe>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
	import {siteName,appDomain} from './../../config/app';
    import {api} from "../../config/jwt-routes";
    export default {
        data() {
			return {
                open:false,
                loading:false,
                successMesaage:false,
                siteName: siteName,
                appDomain: appDomain,
                form:{
                    section:'',
                    type:'',
                    description:''
                }
                
			}
        },
        mounted(){
            $(this.$el).on('show.bs.modal', () => { this.open = true })
                .on('hidden.bs.modal', () => { this.open = false })
        },
        beforeDestroy(){
            $(this.$el).off('show.bs.modal hidden.bs.modal')
        },
        methods:{
            closeForm(){
                let vm = this
                var errors = vm.errors
                vm.successMesaage = false
                vm.form.section= ''
                vm.form.type= ''
                vm.form.description= ''
                setTimeout(function () {
                    errors.clear()
                }, 1)
            },
            formSubmit:async function() {
                let vm = this
                var errors = vm.errors;
                this.$validator.validateAll().then((result) => {
                    if (result){
                        vm.loading = true
                        axios.post(api.supportRequest, vm.form )
                        .then(async function (response) {
                            if(response.data.success){
                                toastr.success(response.data.msg);
                                vm.form.section= ''
                                vm.form.type= ''
                                vm.form.description= ''
                                setTimeout(function () {
                                    errors.clear()
                                }, 1)
                                vm.successMesaage = true
                                          
                            }else{
                                toastr.error(response.data.msg);
                            }
                            vm.loading=false                      
                        })
                        .catch(function (error) {
                            toastr.error(error);
                            vm.loading=false 
                            console.log(error);
                        })
                    }
                })
            }
        }
    }

</script>