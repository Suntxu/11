define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'total/goodaccount/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'type',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'account', title: '靓号',},
                        {field: 'type', title: '类型',searchList:{0:'店铺'}},
                        {field: 'status', title: '状态',searchList:{0:'未使用',1:'已使用'}},
                        {field: 'operate', title: __('Operate'), table: table, 
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name:'查看使用历史',
                                    text: '查看使用历史',
                                    title:'靓号使用历史',
                                    classname:'btn btn-xs dialogit btn-warning',
                                    icon:'fa fa-deaf',
                                    url: function(res){
                                        return '/admin/total/goodaccount/history?account='+res.account+'&type='+res.a_type;
                                    }
                                }] 
                            }
                    ]
                ],
            });//00125
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        history: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/goodaccount/history',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'id',
                escape: false, //转义空格
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'userid', title: '用户ID'},
                        {field: 'account', title: '靓号'},
                        {field: 'type', title: '靓号类型',searchList:{0:'店铺'}},
                        {field: 'optype', title: '操作',searchList:{0:'使用',1:'过期释放',2:'手动删除释放'}},
                        {field: 'create_time', title: '操作时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: '靓号到期时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                    ]
                ],
                
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


