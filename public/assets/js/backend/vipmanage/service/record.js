define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/service/record/index',
                    edit_url: 'vipmanage/service/record/edit',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'l.time',
                escape: false,
                columns: [
                    [   
                        {field: 'u.uid', title: '用户名'},
                        {field: 'u1.uid', title: '客服',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/service/servicelist/index',fieldvaleu:'u1.uid',fieldname:'uid',tit:'客服信息',},
                        {field: 'u2.uid', title: '被替换的客服',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/service/servicelist/index',fieldvaleu:'u2.uid',fieldname:'uid',tit:'客服信息',},
                        {field: 'l.type', title: '绑定类型',formatter: Table.api.formatter.status,searchList: {1:"怀米大使默认绑定",2:"会员中心第一次绑定",3:"更换绑定"},},
                        {field: 'l.time', title: '绑定时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,sortable:true},
                        {field: 'l.status', title: '审核状态',notit:true,formatter: Table.api.formatter.status,searchList: {0:'审核中',1:'审核成功',2:'审核失败'},},
                        {field: 'audit_time', title: '审核时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,sortable:true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate },
                    ]
                ],
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


