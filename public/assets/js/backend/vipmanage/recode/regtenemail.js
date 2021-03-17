define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/regtenemail/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.time',
                orderName:'desc',
                columns: [
                    [
                        {field: 'u.uid', title: '用户名'},
                        {field: 'r.email', title: '邮箱'},
                        {field: 'r.time', title: '发送时间',operate: 'INT',sortable:true, addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'group', title: '注册商',formatter: Table.api.formatter.status, searchList: Table.api.getcategory('api','category/getcategory','aaid','parent')},
                        {field: 'api_id', title: '接口商',formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('webconfig/regapi/getRegisterUserName'),},
                    ] 
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter['u.uid'] = gro;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
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


