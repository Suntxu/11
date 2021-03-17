define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'staffuse/regdomain/index',
                    multi_url: 'staffuse/regdomain/multi/flag/2',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'r.createtime',
                pageSize: 25,
                columns: [
                    [  
                        // { checkbox: true,footerFormatter: function (data) {
                        //         return '统计：';//在第一列开头写上总计、统计之类'RANGE'
                        //     } },
                        // { field: 'id', title: '用户ID',operate: false,},
                        { field: 'd.tit', title: '域名', operate: 'TEXT' },
                        { field: 'u.uid', title: '用户姓名', operate: 'LIKE' },
                        { field: 'd.money', title: '金额', operate: false,sortable:true },
                        { field: 'r.a_type', title: '注册类型',formatter: Table.api.formatter.status, searchList:{0:'普通',1:'拼团',2:'限量'}},
                        { field: 'r.createtime', title: '提交时间',operate: false,addclass: 'datetimerange',formatter: Table.api.formatter.datetime,},
                        // { field: 'a.nickname', title: '员工昵称', operate: false, },
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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