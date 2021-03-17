define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/Redpackrecord/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'l.status asc,l.ctime desc,l.utime desc',
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'u.uid', title: '领取人',formatter:Table.api.formatter.alink,url:'/admin/spread/elchee/orders',fieldvaleu:'u.uid',fieldname:'uid',tit:'订单列表',},
                        { field: 'l.bc', title: '订单批次',},
                        { field: 'c.title', title: '红包名称', operate: 'LIKE',
                            footerFormatter: function (data) {
                                var wsy = data.reduce(function (sum, row) {
                                    return parseFloat(row['wsy']);
                                }, 0);
                                var ysy = data.reduce(function (sum, row) {
                                    return parseFloat(row['ysy']);
                                }, 0);
                                 var ysx = data.reduce(function (sum, row) {
                                    return parseFloat(row['ysx']);
                                }, 0);
                                return '已使用折扣总金额:<span style="color:green">'+ysy+'</span>元  已失效折扣总金额:<span style="color:red">'+ysx+'</span>元  未使用折扣总金额:<span style="color:gray">'+wsy+'</span>元';
                            }
                        },
                        { field: 'c.satisfy_amount', title: '满足金额',operate: 'THOUSANDS',sortable: true},
                        { field: 'c.rebate_amount', title: '折扣金额',operate: 'THOUSANDS',sortable: true,},
                        { field: 'c.use_shop', title: '指定店铺',operate: false},
                        { field: 'c.term', title: '有效期(天)',ortable: true},
                        { field: 'c.use_type', title: '使用类型', formatter: Table.api.formatter.status,searchList: {'1':'一口价'}},
                        { field: 'l.use_type', title: '使用场景', formatter: Table.api.formatter.status,searchList: {'0':'不限','1':'满减'}},
                        { field: 'c.type', title: '用户类型', formatter: Table.api.formatter.status,searchList: {'0':'所有用户'}},
                        { field: 'special_status', title: '红包状态', formatter: Table.api.formatter.status,searchList: {'0':'未使用','1':'已使用','2':'已失效'},}, 
                        { field: 'l.ctime', title: '领取时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,ortable: true},
                        { field: 'l.utime', title: '使用时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,ortable: true},
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
