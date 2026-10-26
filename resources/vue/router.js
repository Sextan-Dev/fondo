import Vue from 'vue';
import VueRouter from 'vue-router';
import jwtToken from './helpers/jwt-token';
import store from './store/index';
import routes from './routes';
import {siteName} from "./config/app";
import {api} from "./config/jwt-routes";
import auth from './store/modules/auth';
Vue.use(VueRouter);

const router = new VueRouter({
	mode: 'history',
	routes
});

router.beforeEach(async (to, from, next) => {

	setTimeout(() => {
        window.scrollTo(0, 0);
	}, 100);
	
	// IF REFRESH RENEW USERINFO 
	if (jwtToken.getToken() && !store.getters.isLoggedIn) {
		let {data: authUser} = await axios.post(api.userSession);
		await store.dispatch('setAuthUser', authUser);
	}

	// IF PAGE NOT FOUND REDIRECT 404 VIEW
	if (!to.matched.length) {
		if (store.getters.isLoggedIn && jwtToken.getToken()){
			next('/PageNotFound');
		}
	}

	if( store.getters.isLoggedIn && to.meta.requiresGuest  ){
		next({name:'index'})
	}

	//STORE FROM PARAMS
	localStorage.setItem("fromParams", JSON.stringify(from.params));
	localStorage.setItem("fromName", from.name);
	
	// IF COMPONENT NEED AUTH
	if (to.meta.requiresAuth) {
		if (store.getters.isLoggedIn && jwtToken.getToken()){
			return next();
		}else{
			return next({name:'login'});	
		}
	}else{
		// IF COMPONENT NOT NEED AUTH
		if (to.meta.requiresGuest) {
			return next();
		}
	}

	next();
});

export default router;