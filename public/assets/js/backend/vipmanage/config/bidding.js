define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/config/bidding/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'c.id',
                escape: false, //转义空格
                columns: [
                    [   
                        {field: 'uid', title: '用户名'},
                        {field: 'inner_1', title: '内外部差价第一名',operate:'BETWEEN',sortable:true},
                        {field: 'inner_2', title: '内外部差价第二名',operate:'BETWEEN',sortable:true},
                        {field: 'pre_66', title: '阿里云预释放外部价格',operate:'BETWEEN',sortable:true},
                        {field: 'pre_1000', title: '怀米网预释放外部价格',operate:'BETWEEN',sortable:true},
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


