define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/record/recycle/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.create_time',
                orderName:'desc',
                columns: [
                    [
                        {field: 'name', title:'后缀',searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        {field: 'recycle_price', title: '回收基本价格',operate: false,sortable:true,},
                        {field: 'recycle_inc', title:'回收递增价格',operate: false,sortable:true},
                        {field: 'datemin', title: '最小时间限制(天)',operate: false,sortable:true,},
                        {field: 'datemax', title: '最大时间限制(天) ',operate: false,sortable:true,},
                        {field: 'max_money', title: '最大回收价格',operate: false,sortable:true,},
                        {field: 'r.status',title:'状态',formatter:Table.api.formatter.status,searchList:{0:'开启',1:'关闭'}},
                        {field: 'r.create_time', title: '修改时间',addclass: 'datetimerange',operate: 'INT',sortable:true,formatter: Table.api.formatter.datetime},
                        {field: 'nickname', title: '操作者',},
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    
    return Controller;
});
