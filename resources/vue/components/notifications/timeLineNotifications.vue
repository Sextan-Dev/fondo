<template>
	<div class="content-box" >
		<div class="element-wrapper">
           
            <!-- HEADER & BUTTONS -->
            <h6 class="element-header">
                <span class="default-title">
                    <i class="icon-bell mr-3"></i>
                    <span v-translate>Notificaciones</span>
                </span> 
            </h6>
           
            <!-- DataTable -->
            <div class="element-box mt-3">
                <div class="row">
                    <div class="col-md-12 col-centered">
                        <vc-timeline @story="onStory($event)" :data="timeline" />
                    </div>
                </div>           
               <div class="row" v-if="moreNotifications && timeline.length>10">
                   <div class="col-centered col-md-12 mt-3">
                        <div  v-if="!loading" @click="showNotification(10)" class="text-center show-more">Mostrar mas</div>
                        <div v-else class="text-center show-more loading">Cargando...</div>
                   </div>
               </div>
            </div>
		</div>
	</div>
</template>

<script>
    import {api} from "../../config/jwt-routes";

	export default {
        data(){
            return { 
                timeline: [],
                loading : true, 
                moreNotifications:true, 
                offset : 0
            }
        },
        methods: {
            onStory (data) {
                let vm = this
                vm.$parent.$emit('showNotificationUnread', data)
            },
            // DISPLAY MODAL NOTIFICATION AND UPDATE COUNT NOTIFICATIONS UNREAD
			showNotification: async function( offset ){
                let vm = this
                vm.loading = true
                vm.offset = parseInt(vm.offset) + parseInt(offset)
				axios.post(api.selectNotifications ,{offset : vm.offset})
                .then( function (response) {
					if(response.data.success){
                        if(vm.offset == 0){
                            vm.timeline = response.data.timeline
                        }else{
                            _.each(response.data.timeline, function(notification , key){
                                let noty = _.findWhere( vm.timeline, {breakpoint : notification.breakpoint} )
                                
                                if(noty != undefined ){
                                    _.each(notification.history, function(story, index){
                                        noty.history.push(story)
                                    })
                                }else{
                                    vm.timeline.push(notification)
                                }
                            })

                            if(!response.data.timeline.length>0){
                                vm.moreNotifications = false
                            }
                        }
                        vm.loading = false;
                    } else {
						toastr.error(response.data.msg)
                    }
                   
                })
                .catch(function (error) {
					console.log(error)
                })
			},
        },
        mounted: async function(){
            var vm = this;
            
            await vm.showNotification( 0 )

            $('.vc-timeline').on( 'click', '.open-notification', function(){
                vm.$parent.$emit('showNotificationUnread', {id:  $(this).attr('noty-id')})
            })            
        }
    }
</script>

<style scoped>
    @import '../../../../node_modules/vuecidity/dist/lib/vuecidity.min.css'; 
</style>
<style>
    .content-notification{
        background:#f7f7f7;
        cursor:pointer;
        padding: 10px;
    }
    .show-more{
        padding: 10px;
        background: #f5f5f5;
        cursor: pointer;
        border-radius: 13px;
    }
    .text-center.show-more:hover {
        background: #ece9e9;
    }
    .text-center.show-more:active {
        background: #f5f5f5;
    }
    .show-more.loading{
        padding: 10px;
        background: #fafafa;
        cursor: pointer;
        border-radius: 13px;
        color: #cec6c6;
    }
    .content-notification .badge{
        white-space: normal !important;
    }
    .vc-button.vc-icon-button.vc-small .vc-icon {
        font-size: 18px;
        margin-top: -4px;
    }
</style>
