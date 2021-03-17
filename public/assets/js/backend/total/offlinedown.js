define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/offlinedown/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'e.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [   
                        { field: 'e.name', title: '文件名',},
                        { field: 'num', title: '行数',operate:false},
                        { field: 'e.createtime', title: '提交时间',addclass: 'datetimerange',operate:'INT',sortable:true,formatter: Table.api.formatter.datetime},
                        { field: 'e.status', title: '状态',formatter: Table.api.formatter.status, searchList: {0:'生成中',1:'已生成'},notit:true,},
                        { field: 'e.endtime', title: '生成时间',addclass: 'datetimerange',operate:'INT',sortable:true,formatter: Table.api.formatter.datetime},
                        { field: 'nickname', title: '操作人',},
                        { field: 'down', title: '下载',operate:false,},
                    ]
                ],
                
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
