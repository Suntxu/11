define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {};
    return Controller;
});
//评价列表未开放
$('#pj').click(function(){
    layer.msg('功能暂未开放');
});