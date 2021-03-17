define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/service/complaint/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'l.createtime',
                escape: false,
                columns: [
                    [   
                        {field: 'u.uid', title: '用户名'},
                        {field: 'u1.uid', title: '客服',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/service/servicelist/index',fieldvaleu:'u1.uid',fieldname:'uid',tit:'客服信息',},
                        {field: 'title', title: '标题',operate:false,align:'left'},
                        {field: 'content', title: '内容',operate:false,align:'left'},
                        {field: 'l.createtime', title: '投诉时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,sortable:true},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var uid = $("#uid").val();
                    if (uid != '')
                        filter['u1.uid'] = uid;

                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
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

