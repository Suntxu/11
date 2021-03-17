define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/suffix/discountslog/index',
                    add_url: 'activity/suffix/discountslog/add',
                    edit_url: 'activity/suffix/discountslog/edit',
                    del_url: 'activity/suffix/discountslog/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'s.id',
                orderName:'desc',
                columns: [
                    [
                        {checkbox: true},
                        // formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('domain/manage/getDomainHz'),
                        {field: 'hz', title:'后缀',operate:'LIKE'},
                        {field: 'money', title:'优惠价格',sortable:true,operate:'BETWEEN'},
                        {field: 'num', title: '优惠总数量',operate:false,sortable:true},
                        {field: 'stime', title: '开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'etime', title: '结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'uid', title:'用户'},
                        {field: 'niun', title:'已使用/优惠数量',operate:false,sortable:true},
                        {field: 's.status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'正常',1:'禁止'},custom:{'正常':'success','禁止':'danger'},},
                       
                        {field: 'ctime', title: '领取时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        
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