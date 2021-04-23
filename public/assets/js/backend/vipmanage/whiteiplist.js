define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/Whiteiplist/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'u.id',
                orderName: 'desc',
                escape: false,
                showJumpto: true,
                columns: [
                    [
                        // { checkbox: true},
                        { field: 'add_ip', title: '添加时IP'},
                        { field: 'white_ip', title: '白名单IP'},
                        { field: 'du.uid', title: '用户名称',},
                        { field: 'status', title: '状态',searchList:{1:'使用中',2:'禁用'},formatter:function(value){
                                if(value == 1){
                                    return '使用中';
                                }else if(value == 2){
                                    return '禁用';
                                }
                            }},
                        { field: 'add_time', title: '添加时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'update_time', title: '修改时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
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









