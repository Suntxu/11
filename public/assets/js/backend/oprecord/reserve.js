define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oprecord/reserve/index',
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
                escape: false, //转义空格
                columns: [
                    [
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'r.tit', title: '域名',operate:'TEXT'},
                        { field: 'group', title: '后缀',searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'uid', title: '用户名',
                            footerFormatter: function (data) {
                                var field = 'ple';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '参与用户: '+total_sum.toFixed(0)+' 人';
                            }
                        },
                        { field: 'r.money', title: '冻结金额',sortable:true,operate:'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'pay';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '冻结金额:'+total_sum.toFixed(0);
                            }},
                        { field: 'r.money', title: '实付金额',sortable:true,operate:false,
                            footerFormatter: function (data) {
                                var field = 'spay';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '实付总金额:'+total_sum.toFixed(0);
                            }},
                        { field: 'r.status', title: '订单状态',formatter: Table.api.formatter.status,searchList: {'1':'已预定','2':'竞拍中','3':'预定失败','5':'批量失败进行中','6':'批量成功进行中','7':'得标','8':'未得标','9':'已提交','10':'外部得标'},notit:true},
                        { field: 'r.pstatus', title: '交割状态',formatter: Table.api.formatter.status,searchList: {'0':'未支付','1':'未交割','2':'交割失败','3':'已交割','4':'违约'},notit:true},
                        { field: 'r.time', title: '订单创建时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,sortable:true },
                        { field: 'endtime', title: '订单结束时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,operate:false,
                            footerFormatter: function (data) {
                                var field = 'nopay';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '未支付余款:'+total_sum.toFixed(0);
                            }
                        },
                        
                        { field: 'nowpay', title:'未支付余款',operate:false},
                        { field: 'a.nickname', title:'操作人'},
                        // { field: 'uip', title: 'IP地址',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        
        multire:function(){
            Form.api.bindevent($("form[role=form]"), function(data, ret){},function(data,ret){
                if(ret.code == 300){
                    $('.auction').css('display','');
                }
            });
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"), function(data, ret){},function(data,ret){
                if(ret.code == 300){
                    $('.auction').css('display','');
                }
            });
        },
    };
    return Controller;
});
