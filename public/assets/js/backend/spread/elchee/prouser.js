define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/prouser/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'r.stime',
                columns: [
                    [
                        { field: 'u1.uid', title: '怀米大使',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'u.uid', title: '用户', },
                        { field: 'cji', title: '充值总金额',operate:false,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'r.stime', title: '关联时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'r.etime', title: '失效时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['u1.uid'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    console.log(params);
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
