define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/pageset/dispage/index',
                    add_url: 'activity/pageset/dispage/add',
                    edit_url: 'activity/pageset/dispage/edit',
                    del_url: 'activity/pageset/dispage/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
              table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'sort',
                orderName:'desc',
                search:false,//隐藏搜索框
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'title', title:'标题',},
                        {field: 'money', title:'金额',operate:'BETWEEN'},
                        {field: 'link', title:'连接',},
                        {field: 'type', title:'设置类型',searchList:$.getJSON('category/getcategory?type=pageset&xz=parent')},
                        {field: 'create_time', title: '创建时间',operate:'RANGE',sortable:true,addClass:'datetimerange',formatter: Table.api.formatter.datetime},
                        {field: 'status', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'已启用','2':'已禁用'},},
                        {field: 'sort', title:'序号',operate:false},
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

