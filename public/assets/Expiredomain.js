define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/expiredomain/index',
                    table: 'attachment'
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'n.dqsj',
                orderName:'desc',
                escape: false, //转义空格
                exportDataType:'all',
                columns: [
                    [ 
                        {field: 'uid', title: '用户名',},
                        {field: 'mot', title: '电话',},
                        {field: 'hz', title: '后缀',formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('domain/manage/getDomainHz'),},
                        {field: 'num',operate:false, title: '域名数量',},
                        {field: 'dqsj',title: '到期时间',addclass: 'datetimerange',operate:'RANGE', visible:false,},
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});

