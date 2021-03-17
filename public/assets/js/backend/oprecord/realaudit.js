define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/realaudit/index',
                    table: 'attachment'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'r.createtime desc , r.status asc',
                columns: [
                    [
                        {field: 'uid', title: '用户名',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/realaudit/edit',fieldvaleu:'id',fieldname:'ids',tit:'身份审核',},
                        {field: 'r.renzheng', title:'认证类型', formatter: Table.api.formatter.status,searchList: {0:"个人认证",1:"企业认证"},},
                        {field: 'group', title:'实名名称',},
                        {field: 'r.status', title:'审核状态', sortable:true,formatter: Table.api.formatter.status,searchList: {1:'失败',2:"成功",9:'已删除'},},
                        {field: 'r.title', title: '标题'},
                        {field: 'r.createtime',title: '申请时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'checktime',title: '审核时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: '审核说明', operate:false},
                        {field: 'a.nickname', title: '操作人',},
                    ]
                ],
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },

    };
    return Controller;
});
