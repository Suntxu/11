define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/recycle/recydetail/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'d.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [   
                        {field: 'group', title: 'ID(编号)',},
                        {field: 'uid', title: '用户',},
                        {field: 'tit', title: '域名',operate:'TEXT'},
                        {field: 'hz', title: '后缀',visible:false, searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        {field: 'd.money', title: '金额',sortable:true,operate:'BETWEEN'},
                        {field: 'd.status', title: '回收状态',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'检测中',1:'可回收',2:'不可回收',3:'已取消',4:'回收被拒绝',5:'回收已接收'},},
                        {field: 'd.dstatus', title: '域名状态',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'未检测',1:'正常',2:'被hold',3:'被墙'},},
                        // {field: 'wx_check', title: '微信状态',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'未知',1:'未拦截',2:'拦截'},},

                        {field: 'dqsj',title: '到期时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: '备注',operate:false},
                        
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
