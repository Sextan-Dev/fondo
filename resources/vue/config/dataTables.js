import $ from 'jquery';
 
export default function () {
    $.extend(true, $.fn.dataTable.defaults, {

        renderer: 'bootstrap'
    });
}