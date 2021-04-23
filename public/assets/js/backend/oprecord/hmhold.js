define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/hmhold/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortOrder:'desc',
                sortName:'id',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'tit', title: '域名',operate:'TEXT'},
                        {field: 'group', title: '用户'},
                        {field: 'status', title: '状态',searchList:{0:'已提交',1:'hold成功',2:'hold失败'}},
                        {field: 'create_time', title: '提交时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: '备注',operate:false},
                        {field: 'special_condition', title: '操作人'},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                name:'查看详情',
                                text: '查看详情',
                                title:'查看详情',
                                classname:'btn btn-xs dialogit btn-warning',
                                icon:'fa fa-deaf',
                                url: function(res){
                                    return '/admin/vipmanage/recode/transfer?r.id='+res.taskid;
                                }
                            }]
                        }
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


