define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'advertising/ad/index',
                    add_url: 'advertising/ad/add',
                    edit_url: 'advertising/ad/edit',
                    del_url: 'advertising/ad/del',
                    table: 'user',
                }
            });

            var table = $("#table");
            
           
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'createtime',
                commonSearch:false, //隐藏搜索
                search:false,//隐藏搜索框
                pagination: false,//不分页
                escape: false, //转义空格
                columns: [
                    [
                        {checkbox: true},
                        {field: 'name', title:'名称',align:'left',operate: 'LIKE',sortable:true,formatter:Table.api.formatter.alink,url:'/admin/advertising/adlist/index',fieldvaleu:'id',fieldname:'bh',tit:'广告列表'},
                        {field: 'status', title: '状态',formatter: Table.api.formatter.status, searchList:{'normal':'正常','hidden':'隐藏'}},
                        {field: 'weig', title: '权重',operate: false,},
                        {field: 'keywords', title: '编号',operate: false,},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate },
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


