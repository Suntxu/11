define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/tradeddomain/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            var id = null;
              //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $.each(json,function(i,n){
                    if(n['pack']){
                        var tit = n['pack'].split(',');
                        var html = '<table class="layui-table" style="width:200px;margin:10px 5px;" lay-size="sm">';
                        for(var i=0; i<tit.length;i++){
                            html += '<tr><td style="padding-left:8%;">'+tit[i]+'</td></tr>'
                        }
                        html += '</table>';
                        $('#show'+n.id).on('click',function(){
                            layer.open({
                                type: 1,
                                title: '打包域名列表',
                                width:200,
                                closeBtn: 0,
                                shadeClose: true,
                                skin: 'yourclass',
                                content: html,
                            });
                        });
                    }
                });
            });
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'c.paytime',
                escape:false,
                pageSize:20,
                columns: [
                    [  
                        // { checkbox: true,},
                        { field: 'c.bc', title: '批次',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'c.tit', title: '域名',operate:'TEXT',},
                        { field: 'group', title: '后缀',visible:false, searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'c.pack', title: '交易类型',visible:false, formatter: Table.api.formatter.status, searchList:{'not exists':'一口价域名','exists':'打包域名'}},
                        { field: 'pack_num', title: '域名数量',operate:false,},
                        { field: 's.uid', title: '卖家账号',},
                        // { field: 'c.dttype', title: '交易类型',addClass:'ztsea',formatter: Table.api.formatter.status, notit:true, searchList:{1:'一口价',2:'合作方一口价'}},
                        { field: 'c.paytime', title: '交易时间',operate: 'RANGE',addclass: 'datetimerange selll',defaultValue:getTimeFrame()},
                        { field: 'u.uid', title: '买家账号',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'people';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '购买人数:'+total_sum.toFixed(0);
                            }
                        },
                        { field: 'c.money', title: '价格(元)',operate: 'BETWEEN',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'sxf', title: '手续费',operate: 'BETWEEN',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zsfx';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '手续费:'+total_sum.toFixed(0);
                            }
                        },
                        { field: 'u1.uid', title: '店铺关联账号',},
                        // { field: 'group', title: '备案质保',visible:false,formatter: Table.api.formatter.status, searchList:{1:'非质保订单',2:'质保订单',3:'质保期内订单'}},
                        // { field: 'c.qetime', title: '质保到期时间',sortable:true,operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        // { field: 'c.status', title: '交易状态',formatter: Table.api.formatter.status, searchList:{1:'已完成'}},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ] 
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var suid = $("#suid").val();
                    if (suid != '')
                        filter['s.uid'] = suid;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});
