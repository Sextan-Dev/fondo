//** GLOBAL METHODS APPLIED TO ALL VUEs **//
import jwtToken from './../helpers/jwt-token';
import {appDomain} from './../config/app';
import {api} from "./../config/jwt-routes";
const base = {
    install(Vue, options) {
        // Bound once: inside `updated` every component update added another document handler.
        $(document).on('click','[data-toggle="tooltip"] , .tip' , function() {
            $(this).tooltip('hide');
            $(this).tooltip('disable');
        });
        Vue.mixin({
            mounted: function () {

                //AUTO REQUIRED CLASS
                $('[aria-required=true]').parents('.form-group').find('label:first').addClass('required');
                
            },
            updated: function () {
            
                //TIP & TOOLTIP
                $('[data-toggle="tooltip"] , .tip').tooltip({
                    trigger : 'hover focus',
                    container: '.content-box'
                });
                $('[data-toggle="popover"]').popover();
                
            },
            methods: {
                formatPrice(value) {
                    if(_.isNumber( Number(value) )){
                        let val = (value/1).toFixed(2)
                        return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    }else{
                        return 0.00
                    }
                },
                logout:async function(slug){
                    var vm = this
                    vm.$store.dispatch('unsetAuthUser')
                    .then(async () => {
                        await axios.post(api.logout)
                        .then(function (response){
                            if(!response.data.success){
                                toastr.error(response.data.msg)
                            }else{     
                                jwtToken.removeToken();                                     
                                vm.$router.push({name: 'login'});                                 
                            }
                        })
                        .catch(function(error){
                            console.log(error)
                        })  
                       
                    });
                    
                },
                
                msg:function(){
                    console.log('mixin');
                },
                // FUNCTION GLOBAL DELETE ALL FILES
                deleteFile(path, index , object ){
                    swal({
                        title: "<translate>Seguro ?</translate>",
                        html: "<translate>Estas a punto de eliminar este archivo, deseas continuar?</translate>",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: '<translate>Confirmar</translate>',
                        cancelButtonText: '<translate>Cancelar</translate>',
                    }).then((confirm) => {
                        if (confirm.value) {
                            var vm = this;
                            axios.post(api.deleteFile, {path:path})
                            .then(async function (response) {
                                if(response.data.success){
                                    object.splice(index, 1)                                
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
                },
                blend_colors(col1, col2, steps) {
                    var colors = [];
                    for (var step=0;step<steps;step++) {
                        var color1 = col1 || '#000000';
                        var color2 = col2 || '#ffffff';
                        var percentage = step/steps;
                        // 1: validate input, make sure we have provided a valid hex
                        if (color1.length != 4 && color1.length != 7)
                            console.log('color1 must be provided as hexes');
                        if (color2.length != 4 && color2.length != 7)
                            console.log('color2 must be provided as hexes');    
                        if (percentage > 1 || percentage < 0)
                            console.log('percentage must be between 0 and 1');
                        // output to canvas for proof
                        var cvs = document.createElement('canvas');
                        var ctx = cvs.getContext('2d');
                        // color1 on the left
                        ctx.fillStyle = color1;
                        ctx.fillRect(0, 0, 30, 25);
                        // color2 on the right
                        ctx.fillStyle = color2;
                        ctx.fillRect(60, 0, 30, 25);
                        // 2: check to see if we need to convert 3 char hex to 6 char hex, else slice off hash
                        if (color1.length == 4)
                            color1 = color1[1] + color1[1] + color1[2] + color1[2] + color1[3] + color1[3];
                        else
                            color1 = color1.substring(1);
                        if (color2.length == 4)
                            color2 = color2[1] + color2[1] + color2[2] + color2[2] + color2[3] + color2[3];
                        else
                            color2 = color2.substring(1);   
                        // 3: we have valid input, convert colors to rgb
                        color1 = [parseInt(color1[0] + color1[1], 16), parseInt(color1[2] + color1[3], 16), parseInt(color1[4] + color1[5], 16)];
                        color2 = [parseInt(color2[0] + color2[1], 16), parseInt(color2[2] + color2[3], 16), parseInt(color2[4] + color2[5], 16)];
                        // 4: blend
                        var color3 = [ 
                            (1 - percentage) * color1[0] + percentage * color2[0], 
                            (1 - percentage) * color1[1] + percentage * color2[1], 
                            (1 - percentage) * color1[2] + percentage * color2[2]
                        ];
                        // 5: convert to hex
                        var h1 = Math.round(color3[0]).toString(16);
                        if (h1.length == 1) {h1 = '0' + h1;}
                        var h2 = Math.round(color3[1]).toString(16);
                        if (h2.length == 1) {h3 = '0' + h3;}
                        var h3 = Math.round(color3[2]).toString(16);
                        if (h3.length == 1) {h3 = '0' + h3;}
                        color3 = '#' + h1 + h2 + h3;
                        // color3 in the middle
                        ctx.fillStyle = color3;
                        ctx.fillRect(30, 0, 30, 25);
                        // return hex
                        colors.push(color3);
                    }
                    return colors;
                },
            },
            data: function () {
                return {}
            }
        })
    }
}
export default base;