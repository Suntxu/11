define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/keyword/index',
                    add_url: 'webconfig/keyword/add',
                    edit_url: 'webconfig/keyword/edit',
                    del_url: 'webconfig/keyword/del', 
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'sort',
                orderName: 'desc',
                escape: false,
                columns: [
                    [  
                        { field: 'title', title: '关键词',align:'left'},
                        { field: 'type', title: '类型',searchList:{0:'域名简介',1:'域名',2:'店铺名称',3:'店铺QQ'} },
                        { field: 'status', title: '状态',searchList: {0:'已启用',1:'已禁用'}},
                        { field: 'create_time', title: '创建时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'sort', title: '排序号',operate:false,sortable:true },
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