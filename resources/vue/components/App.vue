<template>
	<!-- Template for Dashboard -->
	<div v-if="isLoggedIn">
		<div class="all-wrapper with-side-panel solid-bg-all">
			<div class="layout-w">
				<app-nav-mobile :siteName="siteName" :notificationsUnread="notificationsUnread" :appDomain="appDomain" :auth="auth"></app-nav-mobile>
				<app-nav :siteName="siteName" :notificationsUnread="notificationsUnread" :appDomain="appDomain" :auth="auth"></app-nav>
				<div class="content-w">
					<app-top-bar :auth="auth" ></app-top-bar>
					 <ul v-if="urlParts.length" class="breadcrumb">		
						<li class="breadcrumb-item">
							<a href="/">Home</a>
						</li>				
						<li v-for="(part , index) in urlParts" class="breadcrumb-item" :key="index">
							<span>{{part}}</span>
						</li>				
					</ul>    
					<div class="content-i">
						<router-view :siteName="siteName" :configPlugins="configPlugins" :appDomain="appDomain" :auth="auth" :key="$route.fullPath"></router-view>
						<ModalSupport></ModalSupport>
						<ModalFormNotification :companies="companies" ></ModalFormNotification>						
						<ModalShowNotification  :notification="notification"></ModalShowNotification>
						<ModalDocumentsProofs :appDomain="appDomain" :companies="companies" ></ModalDocumentsProofs>
						<NotificationsListen  :siteName="siteName" :appDomain="appDomain" :auth="auth"></NotificationsListen>
						<!-- NEW NOTIFICATION -->
						<div class="floated-chat-w" style="bottom:20px!important">
							<div class="floated-chat-i">
								<div class="chat-close" onclick="$('.floated-chat-w').toggleClass('active')">
									<i class="os-icon os-icon-close"></i>
								</div>
								<div class="chat-head" style="cursor:pointer;padding: 10px 20px!important">
									<div class="user-w with-status status-green">	
										<div class="user-name" @click.prevent="showNotification" >
											<h6 class="user-title"><translate>Hola, </translate> {{auth.name}}</h6>
											<input id="notification-id" type="hidden" v-model="newNotifications.notification_id" >
											<p> <translate>Has recibido una</translate>&nbsp;<strong>{{newNotifications.notification_type}}</strong>&nbsp;<translate> por parte del usuario</translate>  <strong >{{newNotifications.notification_sender}}</strong>.&nbsp;<span>Puedes dar click <a href="" @click.prevent="showNotification" >aqui</a> para verla.</span></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div> 
	</div>
	<!-- Template for Login -->
	<div v-else class="auth-wrapper">
		<div class=" all-wrapper menu-side with-pattern">			
			<Login v-if="$route.name=='login'"></Login>
			<PasswordReset v-else-if="$route.name=='PasswordReset'"></PasswordReset>
			<ForgotPassword v-else-if="$route.name=='ForgotPassword'"></ForgotPassword>						
		</div>
	</div>
</template>

<script>
	import {trim} from './../core/helpers.js';
	import AppTopBar from './shared/AppTopBar.vue';
	import AppNav from './shared/AppNav.vue';
	import AppNavMobile from './shared/AppNavMobile.vue';
	import {mapGetters,mapState} from 'vuex'; 
	import {siteName,appDomain} from './../config/app';
	import ModalSupport from './shared/ModalSupport';
	import ModalFormNotification from './notifications/ModalFormNotification';
	import ModalShowNotification from './notifications/ModalShowNotification';
	import ModalDocumentsProofs from './shared/ModalDocumentsProofs';
	import NotificationsListen from './notifications/NotificationsListen';
	import Login from './login/Login';		
	import ForgotPassword from './login/forgotPassword.vue';
	import PasswordReset from './login/passwordReset.vue';	
	import {api} from "../config/jwt-routes";
	export default {
		data(){
			return {
				siteName:siteName,
				appDomain:appDomain,
				companies:[],
				newNotifications:{
					notification_id:null,
					notification_type:null,
					notification_sender:null,
				},
				notificationsUnread:{},
				notification:{
					title:null,
					message:null,
					sender:{
						name:null
					}
				},
				configPlugins: {
					tinymce:{
						statusbar:false,
						branding:false,
						height:300
					}
				}
			}
		},
		computed: {
			...mapGetters([				
				'isLoggedIn'			
			]),
			...mapState({
                auth: state => state.auth.user
            }),
			urlParts: function () {
				
				// path, not fullPath: a query string (?project=12) is not a level of the page
				var part = trim(this.$route.path, '/');

				if(part == ''){
					return []
				}else{
					var urlParts = part.split('/');
				}
				
				return urlParts
			}
		},
		
		components: {
			'app-top-bar': AppTopBar,
			'app-nav': AppNav,
			'app-nav-mobile': AppNavMobile,
			'ModalSupport' : ModalSupport,
			'ModalFormNotification' : ModalFormNotification,
			'ModalShowNotification' : ModalShowNotification,
			'NotificationsListen' : NotificationsListen,
			'ModalDocumentsProofs': ModalDocumentsProofs,
			Login,
			ForgotPassword,
			PasswordReset
		},

		methods:{
			// DISPLAY MODAL NOTIFICATION AND UPDATE COUNT NOTIFICATIONS UNREAD
			showNotification: async function(){
				let vm = this
				if($('.floated-chat-w').hasClass('active')){
					$('.floated-chat-w').toggleClass('active')
				}
				let id = vm.newNotifications.notification_id
				axios.post(api.showNotification, {'id':id})
                .then( function (response) {
					if(response.data.success){
						vm.notification = response.data.notification
						if(vm.notification.type == 'route'){
							vm.notification.route = JSON.parse(vm.notification.url)
						}
						$('#showNotificationModal').modal()						
						// REMOVE NOTIFICATION OF LIST NOTIFICATION UNREAD
						vm.notificationsUnread.forEach( function(item, key){
							if( item.id == id && item.type!='auth'){
								vm.notificationsUnread.splice(key,1)
							}
						})
                    } else {
						toastr.error(response.data.msg)
                    }
                })
                .catch(function (error) {
					console.log(error)
                })
			},
			// SET NOTIFICATIONS UNREAD, THIS VAR IS A PROPERTY
			listNotificationsUnread: async function(){
				var vm = this;			
                await axios.post(api.listNotificationsUnread)
                .then(function (response) {
                    if(response.data.success){
                       vm.notificationsUnread = response.data.notifications
                    }
                })
                .catch(function (error) {
                    console.log(error)
                })
			},
			
			companiesList:async function() {
                let vm = this
                axios.post( api.listCompanies )
                .then(function(response){
                    if(response.data.success){                    
                       vm.companies = response.data.companies
                    }else{
						toastr.error(response.data.msg)
						console.log(response.data)
                    }
                })
                .catch(function(error){
					toastr.error(response.data.msg)
					console.log(response.data)
                })
			},
		},


		mounted:async function() {
			let vm = this
			// UPDATE SELECT COMPANIES HEADER
			await vm.$on('updateCompanies', function(value){
				vm.companiesList()
			})
			// DISPLAY PREVIEW NOTIFICATION IN FOOTER
			await vm.$on('showPreviewNotification', async function(message){
				vm.newNotifications.notification_id = message.id
				vm.newNotifications.notification_type = message.type
				vm.newNotifications.notification_sender = message.sender
				await vm.listNotificationsUnread()
                $('.floated-chat-w').toggleClass('active')
			})

			// EXECUTE METHOD FOR DISPLAY MODAL NOTIFICATION
			await vm.$on('showNotificationUnread', function(notification){
				vm.newNotifications.notification_id = notification.id
				vm.showNotification()
			})

			// RELOAD NOTIFICATION UNREAD LIST
			await vm.$on('reloadNotificationUnread', async function(){
				await vm.listNotificationsUnread()
			})
			
			await vm.$on('readNotification', async function(id){
				vm.notificationsUnread.forEach( function(item, key){
					if( item.id == id){
						vm.notificationsUnread.splice(key,1)
					}
				})
			})
			
			await vm.companiesList()

		}
	}		
</script>
<style>
	    /* Estilos para todos los dropdowns de el-select */
	.el-select-dropdown {
        /* never wider than the screen: on phones 400px pushed the options past its edge */
        min-width: min(400px, calc(100vw - 16px)) !important;
        max-width: min(600px, calc(100vw - 16px)) !important;
    }

    .el-select-dropdown__item {
        white-space: normal !important;
        word-wrap: break-word !important;
        height: auto !important;
        line-height: 1.4 !important;
        padding: 8px 20px !important;
        min-height: 40px !important;
    }

    .el-select-dropdown__item span {
        white-space: normal !important;
        word-wrap: break-word !important;
        display: block !important;
    }
</style>