define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/reserve/auction/index',
                    table: 'user',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'i.id',
                sortOrder: 'desc',
                escape:false,
                columns: [
                    [  
                        { field: 'i.tit', title: '域名',operate: 'TEXT',formatter:Table.api.formatter.alink,url:'/admin/domain/reserve/auctionlog/index/',fieldvaleu:'id',fieldname:'id',tit:'竞拍记录',
                            footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }
                        },
                        { field: 'cur_money', title: '当前价格',operate: 'BETWEEN',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'currMoney';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '当前总金额:'+total_sum.toFixed(2);
                            }
                        },
                        { field: 'start_time', title: '开始时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,
                            footerFormatter: function (data) {
                                var field = 'tnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '实付总金额:'+total_sum.toFixed(2);
                            }
                        },
                        { field: 'end_time', title: '结束时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,
                            footerFormatter: function (data) {
                                var field = 'zreta';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '返利总金额:'+total_sum.toFixed(2);
                            }
                        },
                        // { field: 'money', title: '冻结保证金%',operate: false,sortable:true},
                        { field: 'transferprice', title: '转入价格',operate: false,sortable:true,
                            footerFormatter: function (data) {
                                var field = 'outMoney';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '外部总金额:'+total_sum.toFixed(2);
                            }},
                        { field: 'realitypay', title: '实付金额',operate: false},
                        { field: 'reta', title:'返利',operate:false},
                        { field: 'i.type', title: '预定类型',formatter: Table.api.formatter.status, notit:true, searchList:{0:'预定',1:'预释放'},},
                        { field: 'uid', title: '领先用户'},
                        { field: 'i.outer_price', title: '外部价格',sortable:true,operate:false},
                        { field: 'special_condition', title: '后缀',searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'i.api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName')},
                        { field: 'i.ptime', title: '预计交割时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                        { field: 'i.status', title: '竞拍状态',formatter: Table.api.formatter.status, notit:true, searchList:{0:'进行中',1:'竞价成功',2:'竞价失败',3:'交割成功',4:'内部竞价'},},
                        { field: 'i.inner', title: '竞价类型',searchList:{0:'正常竞价',1:'内部竞价'},},
                        { field: 'group', title: '结束状态',formatter: Table.api.formatter.status, notit:true, searchList:{1:'未开始',2:'进行中',3:'已结束'},},
                        { field: 'spec',title:'是否返利用户',searchList:{1:'返利用户',2:'非返利用户'},visible:false},
                    ] 
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});
