define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/suffix/discounts/index',
                    add_url: 'activity/suffix/discounts/add',
                    edit_url: 'activity/suffix/discounts/edit',
                    del_url: 'activity/suffix/discounts/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'id',
                orderName:'desc',
                columns: [
                    [
                        {checkbox: true},
                        // formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('domain/manage/getDomainHz'),
                        {field: 'hz', title:'后缀',operate:'LIKE'},
                        {field: 'ymoney', title:'原始价格',sortable:true,operate:false},
                        {field: 'money', title:'优惠价格',sortable:true,operate:'BETWEEN'},
                        {field: 'num', title: '优惠数量',operate:false,sortable:true},
                        {field: 'status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'开启',1:'关闭'},custom:{'开启':'success','关闭':'danger'},},
                        {field: 'stime', title: '开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'etime', title: '结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: '创建时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        
                        {field: 'Operate', title: __('Operate'),operate:false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate },
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