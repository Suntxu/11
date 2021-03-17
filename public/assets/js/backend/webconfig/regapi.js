define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/regapi/index',
                    add_url: 'webconfig/regapi/add',
                    edit_url: 'webconfig/regapi/edit',
                    // del_url: 'webconfig/regapi/del',
                    multi_url: 'webconfig/regapi/multi_url',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'status asc,createtime desc',
                escape:false,
                // commonSearch:false, //隐藏搜索
                search:false,//隐藏搜索框
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID',operate: false},
                        {field: 'tempid', title: '接口账号',operate: false},
                        {field: 'tit', title:'标题(后台)',formatter:Table.api.formatter.alink,url:'/admin/webconfig/regapi/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        {field: 'showtit',title:'标题(前台)'},
                        {field: 'emailau', title: '邮箱认证',formatter: Table.api.formatter.status, searchList:{0:"不需要",1:"需要"} },
                        {field: 'ifreal', title: '实名',formatter: Table.api.formatter.status, searchList:{1:"不需要",2:"需要"} },
                        {field: 'xf_lock', title: '续费状态',formatter: Table.api.formatter.status, searchList:{0:"正常",1:'禁止'} },
                        {field: 'regid', title: '注册商', searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        {field: 'status', title: '状态',formatter: Table.api.formatter.status, searchList:{1:"启用",2:"禁用"} },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
