define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/voucherrecord/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'c.createtime',
                columns: [
                    [  
                        // { checkbox: true,},
                        // { checkbox: true,},
                        { field: 'bh', title: '编号',operate:'LIKE',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类'RANGE'
                        } },
                        { field: 'uid', title: '发放用户',},
                        { field: 'nickname', title: '申请人',},
                        { field: 'addmoney', title: '面额',operate:'BETWEEN',footerFormatter: function (data) {  
                                var field = 'mezje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        { field: 'c.createtime', title: '使用日期',operate: 'INT',addclass: 'datetimerange',formatter:Table.api.formatter.datetime,},
                        // { field: 'mtype', title: '金额类型',sorttableL:false,formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('spread/proorder/getQDName') },
                        { field: 'c.type', title: '使用类型',sorttableL:false,formatter: Table.api.formatter.status,searchList: {'0':'系统发放','1':'域名注册',},},
                        { field: 'remark', title: '备注',operate:false},
                    ] 
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var bh = $("#bh").val();
                    if (bh != '')
                        filter.bh = bh;
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
