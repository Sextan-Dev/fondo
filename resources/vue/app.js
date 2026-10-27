///////////////////////////////////
////// IMPORT BASE PLUGINS ////////
///////////////////////////////////

import $ from 'jquery';
import Vue from 'vue';
import safeHtml from './helpers/safe-html.mjs';
Vue.directive('safe-html', safeHtml);
import axios from 'axios';
import _ from 'underscore'; 
import veeLang from 'vee-validate/dist/locale/es';
import VeeValidate, { Validator } from 'vee-validate';
import GetTextPlugin from 'vue-gettext';
import translations from './lang/translations.json';
import router from './router';
import store from './store/index';
import App from './components/App.vue';
import jwtToken from './helpers/jwt-token'; 
import base from './helpers/base';
import vSelect from 'vue-select';
import RichTextEditor from './components/shared/RichTextEditor.vue';
import ElementUI from 'element-ui';
import 'element-ui/lib/theme-chalk/index.css';
import locale from 'element-ui/lib/locale/lang/es'	
import Pusher from "pusher-js"
import Echo from "laravel-echo"
import Vuecidity from 'vuecidity'
// import Editor from '@tinymce/tinymce-vue'
Vue.use(Vuecidity)
// CONFIG lARAVEL ECHO
window.Pusher = Pusher;
window.Echo = window.Laravel.pusherKey ? new Echo({
    broadcaster: 'pusher',
    key: window.Laravel.pusherKey,
    cluster: window.Laravel.pusherCluster || 'us2',
    forceTLS: true
}) : null;

Vue.use(ElementUI, { locale })

//** SET VUE PLUGINs **//
Vue.use(GetTextPlugin, {
	availableLanguages: {
	  es_ES: 'Español',
	  fr_FR: 'Français'
	},
	defaultLanguage: 'es_ES',
	silent: true,
	languageVmMixin: {
	  computed: {
		currentKebabCase: function () {
		  return this.current.toLowerCase().replace('_', '-')
		},
	  },
	},
	translations: translations
})
Validator.localize('es', veeLang);
Vue.use(VeeValidate);
Vue.config.language = 'es_ES'
//** SET & IMPORT GLOBAL PLUGIN VARIABLEs **//
window.$ = window.jQuery = $;
window._ = window.underscore = _;
window.axios = axios; 
window.toastr = require('toastr');
window.swal = require('sweetalert2');
window.dragula = require('dragula');
window.JSZip = require('jszip');
window.moment = require('moment');

window.moment.locale(Vue.config.language);
Vue.component('v-select', vSelect)

// Vue.component('editor', Editor)
Vue.component('editor', RichTextEditor)


///////////////////////////////////
////// IMPORT EXTRA PLUGINS ///////
///////////////////////////////////
require('bootstrap');
require('popper.js');
require('chart.js');
require('bootstrap-daterangepicker'); 
require('bootstrap-datepicker'); 
require('editable-table'); 
require('datatables.net-bs4');
require('datatables.net-responsive-bs4'); 
require('datatables.net-scroller'); 
require('datatables.net-fixedheader'); 
require('datatables.net-fixedcolumns');
require('datatables.net-buttons-bs4'); 
require('datatables.net-buttons/js/buttons.html5.js');
require('datatables.net-buttons/js/buttons.print.js'); 
require('perfect-scrollbar'); 
require('tether'); 
require('select2'); 
require('dragula'); 
require("downloadjs");

///////////////////////////////////
////// IMPORT CUSTOM JS ///////////
///////////////////////////////////
require('./core/main.js');
import defaultHeader from './components/shared/defaultHeader.vue';
Vue.component('default-header', defaultHeader)

//////////////////////
////// CONFIG ////////
//////////////////////

//** AXIOS CONFIG **//

// Request
axios.interceptors.request.use(
	config => { 
		config.headers['X-CSRF-TOKEN'] = window.Laravel.csrfToken;
		config.headers['X-Requested-With'] = 'XMLHttpRequest';

		if (jwtToken.getToken()) {
			config.headers['Authorization'] = 'Bearer ' + jwtToken.getToken();

		}	
		return config;
	}, error => {
		return Promise.reject(error);
});
//Response
axios.interceptors.response.use(
	response => {	
		return response;
}, error => {

	if (jwtToken.isInvalid(error)) {
		const wasLoggedIn = store.getters.isLoggedIn;
		jwtToken.clearSession();
		store.dispatch('unsetAuthUser')
		.then(() => {
			// While booting, router.beforeEach picks the destination; only kick a live session out here.
			if (wasLoggedIn) {
				router.push({name: 'login'}).catch(() => {});
			}
		});
	}
	return Promise.reject(error);
});


//** DATATABLE CONFIG **//
$.extend( true, $.fn.dataTable.defaults, { 
	ajax: {
		beforeSend: function (request) {
			request.setRequestHeader("X-CSRF-TOKEN", window.Laravel.csrfToken);
			request.setRequestHeader("X-Requested-With", 'XMLHttpRequest');
			request.setRequestHeader("Authorization", 'Bearer ' + jwtToken.getToken());
		}
	},
	lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
	serverSide: false,
	autoWidth:false,
	deferRender:true,	
	processing:true,
	responsive:true,
	dom:'lBfrtip',
	buttons: [
		{
			extend: 'collection',
			text: '<i class="icon-feather-download"></i> ',
			className: 'btn btn-table',
			buttons: [
				{
					extend: 'copy',
					text: 'Copy'
				},
				{
					extend: 'excel',
					text: 'Excel'
				},
				{
					extend: 'pdf',
					text: 'PDF'
				},
				{
					extend: 'csv',
					text: 'CSV'
				},
			]
		},{
			extend: 'print',
			text: '<i class="icon-feather-printer"></i>',
			className: 'btn btn-table',
			exportOptions: {
				columns: ':visible'
			}
		}
	],
	"language": {
		"sProcessing":     '<i class="fa fa-spinner fa-4x fa-spin"></i>',
		"sSearch":         '<i class="fa fa-search"></i>&nbsp;',
		"lengthMenu":     "_MENU_",
		"info":           "_TOTAL_ Elements",
		"infoEmpty":      "_TOTAL_ Elements",
		"sLoadingRecords": '<i class="fa fa-spinner fa-2x fa-spin"></i>',
		"oPaginate": {
			"sFirst":      "",
			"sPrevious":   "<i class='fa fa-chevron-left'></i>",
			"sNext":       "<i class='fa fa-chevron-right'></i>",
			"sLast":       ""
		}
	}
});

// Interceptor Response Ajax
// $(document).ajaxSuccess(function(event , request, jqXRH, data) {
// 	// Refresh Token
// 	if(request.getResponseHeader('refreshtoken')!=undefined){			
// 		jwtToken.setToken(request.getResponseHeader('refreshtoken'));
// 	}
// });
 
//////////////////////
//// MAIN APP VUE ////
//////////////////////
Vue.use(base)
//Vue.use(tableDynamic)

//** GLOBAL EVENT BUS **//
export const EventBus = new Vue();

//** MOUNT VUE APP **//
Vue.component('app', App);
const app = new Vue({
	router,
	store,
	render: h => h(App)
}).$mount('#app'); 

