<template>
    <div class="menu-mobile menu-activated-on-click color-scheme-default">
        <div class="mm-logo-buttons-w">
            <router-link :to="{name:'index'}" class="mm-logo">
                <img class="logo-main" :src="appDomain+'/images/logo-impactr.png'" />
            </router-link>
            <div class="mm-buttons">            
                <div class="mobile-menu-trigger">
                    <div class="os-icon os-icon-hamburger-menu-1"></div>
                </div>
            </div>
        </div>
        <div class="menu-and-user mobile">

            <div class="menu-actions">
                <div class="messages-notifications os-dropdown-trigger os-dropdown-position-right">
                    <a href="#" class="mobile-menu-trigger" data-target="#supportModal" data-toggle="modal" ><i class="icon-feather-life-buoy"></i></a>                    
                </div>
                <div class="messages-notifications os-dropdown-trigger os-dropdown-position-right">
                    <router-link :to="{name:'index'}" :class="'mobile-menu-trigger'" >
                        <i class="icon-feather-home"></i>
                    </router-link>
                </div>
                <div class="messages-notifications os-dropdown-trigger os-dropdown-position-right">
                    <router-link :to="{name:'TaskList',params:{user:auth.id}}">
                        <i class="icon-feather-layers"></i>
                        <div class="new-messages-count" v-if="countUserTask>0">{{countUserTask}}</div>
                    </router-link>
                </div>
            </div>

            <ul class="main-menu">
               <template v-for="groupsMenu in auth.privileges.panelNavigation">
                   <template v-for="(groupMenu , key) in groupsMenu" >
                        <li  v-for="(menu, index)  in groupMenu" :key="key+index" :class="menu.childs!==null  ? 'has-sub-menu' : ''">
                            <router-link :to="{ name: menu.route}" v-if="(menu.childs==null)" class="mobile-menu-trigger" replace>
                                <div class="icon-w">
                                    <div :class="menu.icon"></div>
                                </div>
                                <span>{{menu.name}}</span>
                            </router-link>
                            <a v-else href="#" class="mobile-menu-trigger">                   
                                <div class="icon-w">
                                    <div :class="menu.icon"></div>
                                </div>
                                <span>{{menu.name}}</span>                 
                            </a>
                            <ul v-if="menu.childs!== null" class="sub-menu">
                                <li v-for="(subMenus, key) in menu.childs" :key="key">
                                    <router-link :to="{ name: subMenus.route}" replace class="mobile-menu-trigger">
                                        {{subMenus.name}}
                                    </router-link>
                                </li>                  
                            </ul>
                        </li>
                    </template>
                </template>
            </ul>
        </div>
    </div>

</template>

<script>

    import jwtToken from '../../helpers/jwt-token';
    import {EventBus} from './../../core/eventBus.js';
    import {api} from "../../config/jwt-routes";
    
	export default {
		data() {
			return {
                countUserTask: 0,
			}
        },
        props:['auth','appDomain','siteName'],
        watch: {
               '$route': function(to,from) {
                //CLASS SELECTED
                $('.main-menu a[href]').removeAttr('selected');
                var active = $('.main-menu a[href="'+to.path+'"]');
                if (active.length == 0 && to.path.split('/')[1]==from.path.split('/')[1]) {
                    active = $('.main-menu a[href="'+from.path+'"]');
                }
                active.attr('selected','selected');
                //EMIT ICON & NAME
                var _icon = active.find('.icon-w div').attr('class');
                var _pageName = active.find('span').html();
                
                EventBus.$emit("updateName", {name : _pageName, icon : _icon} ) ;
            }
        },
      
		methods: {
           
           
            // userData
            userData:async function(){
                var vm = this;
                await axios.post(api.selectTask,{project:'', user:vm.auth.id,groupBy:'status'})
                .then(function (response) {
                    if(response.data.success){
                        vm.countUserTask = 0;
                        if (response.data.data['pending']) {
                            vm.countUserTask += response.data.data['pending'].length;
                        }
                        if (response.data.data['approved']) {
                            vm.countUserTask += response.data.data['approved'].length;
                        }
                        if (response.data.data['working']) {
                            vm.countUserTask += response.data.data['working'].length;
                        }
                    } else {
                        toastr.error(response.data.msg);
                    }
                })
                .catch(function (error) {
                    console.log(error);
                })
            },
        },
        mounted:async function(){
            var vm = this;
            
            //SET DATA
            await vm.userData();

            // UDPATE USER DATA
            EventBus.$on("updateUserTask", await function() {
                setTimeout(function(){vm.userData()},300);
            });
        }
	}
        
</script>