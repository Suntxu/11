define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/registrar/index',
                    add_url: 'webconfig/registrar/add',
                    edit_url: 'webconfig/registrar/edit',
                    del_url: 'webconfig/registrar/del',
                    multi_url: 'webconfig/registrar/multi_url',
                    table: 'user',
                }
            });

            var table = $("#table");
            
           
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'xh',
                commonSearch:false, //隐藏搜索
                search:false,//隐藏搜索框
                columns: [
                    [
                        {checkbox: true},
                        {field: 'name1', title:'注册商',operate: false,formatter:Table.api.formatter.alink,url:'/admin/webconfig/registrar/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        {field: 'name3', title: '自定义名称',operate: false},
                        {field: 'xh', title: '序号',operate: false,sortable:true,},
                        {field: 'sj', title: '编辑时间',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
