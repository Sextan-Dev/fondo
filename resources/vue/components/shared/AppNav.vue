<template>
    <div :class="auth.company_theme_main" class="menu-w  color-style-default menu-position-side menu-side-left menu-layout-compact sub-menu-style-over  selected-menu-color-light menu-activated-on-hover menu-has-selected-link">

        <div class="logo-w">
            <router-link :to="{name:'index'}" class="logo">
                <img class="logo-main" :src="appDomain+'/images/logo-impactr.png'" />
            </router-link>
        </div>
        <div class="logged-user-w avatar-inline">
            <div class="logged-user-i">
                <div v-if="auth.img" class="avatar-w">
                    <img alt="" :src="appDomain + auth.img" onerror="javascript:this.src='/images/user-grey.png'">
                </div>
                <div v-else class="avatar-w">
                    <img  :src="appDomain + '/images/user-grey.png'" />
                </div>
                <div class="logged-user-info-w">
                    <div class="logged-user-name">
                        {{auth.name}}
                    </div>
                    <div class="logged-user-role">
                        {{auth.group.name}}
                    </div>
                </div>
                <div class="logged-user-toggler-arrow" @click.prevent="logout">
                    <i class="icon-feather-power"></i>
                </div>
            </div>
        </div>    
        <div class="menu-actions">
            <div class="messages-notifications os-dropdown-trigger os-dropdown-position-right">
                <a href="#" data-target="#supportModal" data-toggle="modal" ><i class="icon-feather-life-buoy"></i></a>
            </div>
            <div class="messages-notifications os-dropdown-trigger os-dropdown-position-right">
                <a href="/"  >
                    <i class="icon-feather-home"></i>
                </a>
            </div>
            <!-- LIST NOTIFICATION UNREAD -->
            <NotificationsUnread :appDomain="appDomain" :notificationsUnread="notificationsUnread"></NotificationsUnread>
        
        </div>
        <ul class="main-menu">
            <template v-for="groupsMenu in auth.privileges.panelNavigation" >
                <template v-for="(groupMenu , groupName) in groupsMenu" >                                  
                    <li class="sub-header" :key="groupName">
                        <span>{{ groupName }}</span>
                    </li>
                    <li v-for="(menu, index) in groupMenu"   :class="menu.childs!=null  ? 'has-sub-menu' : ''" :key="groupName+'/'+index">
                        <router-link v-if="(menu.childs==null)" :name="menu.name" :to="{ name: menu.route}"  replace>                   
                            <div class="icon-w">
                                <div :class="menu.icon"></div>
                            </div>
                            <span>{{menu.name}}</span>                 
                        </router-link>
                        <a v-else href="#" >                   
                            <div class="icon-w">
                                <div :class="menu.icon"></div>
                            </div>
                            <span>{{menu.name}}</span>                 
                        </a>
                        <div v-if="menu.childs!== null" class="sub-menu-w">
                            <div class="sub-menu-header">
                                {{menu.name}}
                            </div>
                            <div class="sub-menu-icon">
                                <i :class="menu.icon"></i>
                            </div>
                            <div class="sub-menu-i">
                                <ul class="sub-menu">
                                    <li v-for="(subMenus, key)  in menu.childs" :key="groupName+'/'+index+'/'+key">
                                        <router-link :to="{ name: subMenus.route}" replace>
                                            {{subMenus.name}}
                                        </router-link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                </template>
            </template>
        </ul>
        <span class="copyright"><translate>Powered by</translate> <img :src="appDomain + '/images/logo_sextan_grey.png'"/></span>
    </div>
</template>

<script>
    import jwtToken from '../../helpers/jwt-token';
    import {EventBus} from './../../core/eventBus.js';
    import {api} from "../../config/jwt-routes";
    import NotificationsUnread from '../notifications/NotificationsUnread';
	export default {
        
		data() {
			return {
                loading:false,
                countUserTask: 0,
                routeName: null,
                routeIcon: null
			}
        },
        props:['auth','appDomain','siteName' , 'notificationsUnread'],
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
        components:{
            'NotificationsUnread' : NotificationsUnread
        },
        
        mounted: async function(){

            var vm = this;

            // CALL METHOD FOR DISPLAY MODAL NOTIFICATION
            await vm.$on('showNotificationUnread', function(notification){
				vm.$parent.$emit('showNotificationUnread', notification)
			});	
            
			vm.$parent.$emit('reloadNotificationUnread')
        }
	}
</script>