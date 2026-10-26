<template>
    <div class="messages-notifications os-dropdown-trigger os-dropdown-position-right">
        <i class="icon-bell" style="color:#047bf8"></i>
        <div class="new-messages-count" v-if="notificationsUnread.length>0">{{notificationsUnread.length}}</div>
        <div class="os-dropdown  message-list">
            <div class="icon-w" >
                <i class="icon-bell" ></i>
            </div>
            <ul>
                <template v-for="(notification , key) in notificationsUnread">
                    <li v-if="key<5" :key="key">
                        <a href="#" @click.prevent="$parent.$emit('showNotificationUnread', notification)">        
                            <div class="message-content">
                                <h6 class="message-from">{{notification.sender.name}}</h6>
                                <h6 class="message-title">{{notification.title}}</h6>
                            </div>
                        </a>
                    </li>
                </template>
                <li>
                    <a href="#" @click.prevent="$router.push({name:'TimeLineNotifications'})">        
                        <div class="message-content" v-translate>
                            Ver todas
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>
<script>

    import jwtToken from '../../helpers/jwt-token';;
    import {api} from "../../config/jwt-routes";
    export default {
        data() {
            return {
            }
        },
        props:['appDomain', 'notificationsUnread'],
        methods: {
        },
        mounted: async function(){
            let vm = this
        }
    }
</script>

