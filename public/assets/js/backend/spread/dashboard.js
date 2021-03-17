define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
        }
    };

    return Controller;
});

function copeurl(id) {
    var Url = document.getElementById(id);
    Url.select(); // 选择对象
    document.execCommand("Copy"); // 执行浏览器复制命令
    alert('复制成功');
}