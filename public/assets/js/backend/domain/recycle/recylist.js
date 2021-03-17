define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/recycle/recylist/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'t.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [   
                        {field: 'group', title: '编号',formatter:Table.api.formatter.alink,url:'/admin/domain/recycle/recydetail/index',fieldvaleu:'group',fieldname:'group',tit:'详情',},
                        {field: 'uid', title: '用户',},
                        {field: 't.money', title: '最终金额',sortable:true,operate:'BETWEEN'},
                        {field: 'amount', title: '域名总数量',sortable:true,operate:false},
                        {field: 'amount_ok', title: '可回收数量',sortable:true,operate:false},
                        {field: 'amount_no', title: '不可回收数量数量',sortable:true,operate:false},//formatter:Table.api.formatter.alinks,url:'/admin/domain/recycle/recydetail/index',fieldvaleu:['group','2'],fieldname:['group','d.status'],
                        {field: 't.status', title: '状态',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'检测中',1:'检测完成',2:'已提交回收',3:'已超时',4:'已取消',5:'已拒绝',6:'已接收'},},
                        {field: 't.create_time',title: '提交时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'audit_time',title: '检测完成时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
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
