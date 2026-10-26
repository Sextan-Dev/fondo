<template>

    <span class="default-title">
        <i v-bind:class="pageIcon"></i> <span>{{pageName}}</span>
    </span>
          
</template>

<script>
	import { EventBus } from './../../core/eventBus.js';
    
	export default {
		data() {
			return {
                pageName: null,
                pageIcon: null
			}
        },
        mounted: function() {
            var vm = this;
			EventBus.$on("updateName", function(page) {
                if (page.name != '' && page.icon!='') {
                    vm.pageName = page.name;
                    vm.pageIcon = page.icon;
                }else{
                    console.log(page)
                }
            });
        }
	}
</script>

