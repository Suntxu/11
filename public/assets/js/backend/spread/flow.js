define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/flow/index',
                    table: 'user',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'f.time',
                escape:false,
                columns: [
                    [
                        { field: 'u.uid', title: '怀米大使',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'u1.uid', title: '用户',formatter:Table.api.formatter.alink,url:'/admin/spread/flow/getUserInfo',fieldvaleu:'buyeruserid',fieldname:'id',tit:'用户基本信息', },
                        { field: 'infoid', title: '关键词', operate:'like',},
                        { field: 'f.type', title: '佣金类型', formatter: Table.api.formatter.status,searchList: {0:'推广系统',1:'怀米大使',2:'分销系统'}},
                        { field: 'f.status', title: '提取状态', formatter: Table.api.formatter.status,searchList: {0:'未申请',1:'提取中',2:'提取成功'}},
                        { field: 'f.yjtype', title: '佣金来源', formatter: Table.api.formatter.status,searchList: {0:'域名交易',1:'域名注册',2:'拼团返点',3:'域名预定返点'}},
                        { field: 'f.source', title: '订单来源', formatter: Table.api.formatter.status,searchList: {0:'怀米网',1:'外部订单'}},
                        { field: 'paymoney', title: '付款金额',operate:'BETWEEN',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        
                        { field: 'yj', title: '佣金提点',operate:'BETWEEN',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'cji';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'extra_sxf', title: '分销提点手续费',operate:'BETWEEN',sortable:true,},
                        { field: 'f.time', title: '插入时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'f.apptime', title: '申请提现时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'f.updatetime', title: '提现时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'uip', title: '用户操作IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        { field: 'no', title: '涉及信息',operate:false },
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['f.flow_id'] = id;
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
