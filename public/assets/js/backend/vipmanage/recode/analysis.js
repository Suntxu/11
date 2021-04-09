define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/analysis/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            var Type = {'A':'A','CNAME':'CNAME','AAAA':'AAAA','NS':'NS','MX':'MX','SRV':'SRV','TXT':'TXT','CAA':'CAA','显性URL':'显性URL','隐性URL':'隐性URL'}; //解析类型
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'time',
                orderName:'desc',
                escape: false, //是否对内容进行转义
                columns: [
                    [
                        // {checkbox:true, title:'选择'},
                        {field: 'tit', title: '域名',operate:'TEXT'},
                        {field: 'RR', title: '主机记录',},
                        {field: 'Type', title : '记录类型',searchList:Type},
                        {field: 'Value', title: '记录值',},
                        {field: 'Line', title: '解析线路',searchList:{'default':'默认','unicom':'联通','telecom':'电信','mobile':'移动','edu':'中国教育网','oversea':'境外','baidu':'百度','biying':'必应','google':'谷歌'}},
                        {field: 'Status', title: '状态',searchList:{'Enable':'启用','Disable':'停止'},notit:true,sortable:true,},
                        {field: 'time', title: '解析时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},

                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});
