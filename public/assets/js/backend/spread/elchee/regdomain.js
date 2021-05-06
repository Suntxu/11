define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/regdomain/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'd.createtime',
                orderName:'desc',
                columns: [
                    [
                    
                        { field: 'd.tit', title: '域名',operate:'TEXT', footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'u.uid', title: '注册用户'},
                        { field: 'u1.uid', title: '推广人',},
                        { field: 'd.createtime', title: '注册时间',addclass:'datetimerange',sortable: true,operate: 'INT',defaultValue:getTimeFrame()},
                        { field: 'd.money', title: '注册金额',operate: 'BETWEEN',sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'r.a_type', title: '注册类型',formatter: Table.api.formatter.status, searchList:{0:'普通',1:'拼团',2:'限量'}},
                        { field: 'group', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        { field: 'api_id', title: '接口商',searchList:$.getJSON('webconfig/regapi/getRegisterUserName'),},
                    ]
                ],

                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['c.bc'] = id;
                    //这里可以追加搜索条件
                    var uid = $("#uid").val();
                    if (uid != '')
                        filter['u.uid'] = uid;
                    var taskid = $("#taskid").val();
                    if (taskid != '')
                        filter['d.taskid'] = taskid;
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
