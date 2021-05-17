define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/reserve/auctionlog/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'r.id',
                sortOrder: 'desc',
                escape:false,
                columns: [
                    [
                        { field: 'tit', title: '域名',operate: 'TEXT'},
                        { field: 'uid', title: '用户',},
                        { field: 'time', title: '出价时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'r.money', title: '加价金额',operate: 'BETWEEN',sortable:true},
                        { field: 'res_money', title: '当前底价',operate: 'BETWEEN',sortable:true},
                        { field: 'r.otype', title: '预定类型',formatter: Table.api.formatter.status, notit:true, searchList:{0:'预定',1:'预释放'},},
                        { field: 'i.status', title: '竞拍状态',formatter: Table.api.formatter.status, notit:true, searchList:{0:'进行中',1:'竞价成功',2:'竞价失败',3:'交割成功',4:'内部竞价'},},
                        { field: 'i.inner', title: '竞价类型',searchList:{0:'正常竞价',1:'内部竞价'},},
                        { field: 'group', title: '竞拍进度',formatter: Table.api.formatter.status, notit:true, searchList:{1:'未开始',2:'进行中',3:'已结束'},},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['r.auction_id'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    console.log(params);
                    return params;
                }
            }); 
            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});

