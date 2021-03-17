define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/Helptype/index',
                    add_url: 'webconfig/Helptype/add',
                    edit_url: 'webconfig/Helptype/edit',
                    del_url: 'webconfig/Helptype/del',
                    multi_url: 'webconfig/Helptype/multi_url',
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
                pagination: false,//不分页
                escape: false, //转义空格
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'name', title:'名称',align:'left',operate: false,formatter:Table.api.formatter.alink,url:'/admin/webconfig/Helptype/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        {field: 'xh', title: '序号',operate: false,sortable:false,},
                        {field: 'nr', title:'包含内容',operate: false,sortable:false,},
                        {field: 'sj', title: '更新时间',operate: false,sortable:false,},
                        {field: 'zt', title:'状态',operate: false,sortable:false,},
                        { field: 'operate', title: __('Operate'), addClass:'msg_del',table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
