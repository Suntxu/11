define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'staffuse/total/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'create_time',
                orderName: 'desc',
                columns: [
                    [
                        { field: 'ip', title: '访客ip', operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'充值记录',},
                        { field: 'top', title: '渠道名称',formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('category/getSelectName') },
                        { field: 'special_condition', title: '员工名称',},                        { field: 'alink', title: '落地页',operate:'LIKE'},
                        { field: 'create_time', title: '访问时间', addclass: 'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#gro").val();
                    if (gro != '')
                        filter.group = gro;
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
