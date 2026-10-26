<template>
	<div class="content-box" v-show="!loading">
		<div class="element-wrapper">

            <h6 class="element-header"> 
                <default-header></default-header>
                <button @click.prevent="$router.push({name:'UserForm'})" class="btn btn-sm btn-primary"> <i class="fa fa-plus mr-2"></i><translate>Nuevo Usuario</translate> </button>
            </h6> 

            <div class="element-box">
              
                <!-- DataTable -->
                <div class="table-responsive">
                    <table  class="table table-padded" id="UserList" width="100%">
                        <thead>
                            <tr>
                                <th class="text-center" v-translate>Avatar</th>                                
                                <th v-translate>Fecha de Ingreso</th>
                                <th v-translate>Usuario</th>
                                <th v-translate>Nombre</th>
                                <th v-translate>Email</th>
                                <th v-translate>Grupo</th>
                                <th v-translate>Fecha de creación</th>
                                <th  class="text-center" v-translate>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in users" :key="index">
                                <td class="text-center"><img style="max-width: 30px" :src="appDomain +'/'+ user.img" onerror="javascript:this.src='/images/user-grey.png'" /></td>
                                <td class="smaller lighter">{{user.date_admission||'-'}}</td>
                                <td><b>{{user.user}}</b></td>
                                <td>{{user.name}}</td>
                                <td class="lighter">{{user.email}}</td>
                                <td class="text-center">
                                    <a class="badge badge-primary-inverted" href="">{{user.group.name}}</a>
                                </td>
                                <td class="smaller lighter">{{user.created_at}}</td>
                                <td class="text-center row-actions" v-bind:item="user.id">                                                
                                    <a href="#" @click.prevent="$router.push({name:'UserForm',params: {id: user.id } })" ><i class="icon-feather-edit"></i></a>
                                    <a class="danger" href="#"  v-on:click="deleteItem(user.id)" ><i class="icon-feather-trash-2"></i></a>                           
                                </td>   
                            </tr>
                        </tbody>
                    </table>                    
                </div>
                <!-- ENDDataTable -->
            </div>
		</div>
	</div>
</template>
<script>
    import {api} from "../../../config/jwt-routes";

    export default {
        props:['appDomain','siteName', 'auth'],
        data(){
            return {
                users:[],
                loading : true,
                user:null,                    
            }
        },
        methods:{
            list: async function(){
                var vm = this;
                await axios.get(api.listUser)
                .then(function (response) {
                    if(response.data.success){
                        vm.users = response.data.users;
                    }else{
                        toastr.error(response.data.msg);
                    }                             
                })
                .catch(function (error) {
                    vm.users = [];
                    toastr.error(error);
                    console.log(error);
                })
            },
            deleteItem:  function(user){
                swal({
                    title: "<translate>Seguro ?</translate>",
                    html: "<translate>Estas a punto de eliminar al usuario, una vez realizada esta acción este usuario perdera su acceso al sistema.</translate>",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: '<translate>Confirmar</translate>',
                    cancelButtonText: '<translate>Cancelar</translate>',
                }).then((confirm) => {
                    if (confirm.value) {
                        var vm = this;
                        axios.post(api.deleteUser, {user:user})
                        .then(async function (response) {
                            if(response.data.success){
                                vm.users = $.grep(vm.users, function(user) {
                                    return vm.users.id != user;
                                });
                                await vm.list();
                                vm.datatable.row( $('.row-actions[item="'+vm.users.id+'"]').parents('tr:first') ).remove().draw();
                                swal({ html : response.data.msg, type: "success", timer: 1500 });
                            }else{
                                swal.close();
                                toastr.error(response.data.msg);
                            }                             
                        })
                        .catch(function (error) {
                            console.log(error);
                        })
                    } else {
                        swal({title : "<translate>Operación Cancelada</translate>", type : "error", timer:1200});
                    }
                });
            }
        },
        mounted: async function(){
            await this.list();            
            
            //DATATABLE
            this.datatable = $('#UserList').DataTable({
                processing:false,
                ajax: null
            }); 

            this.loading = false
        }
    }

</script>
