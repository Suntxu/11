define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'advertising/adlist/index',
                    add_url: 'advertising/adlist/add',
                    edit_url: 'advertising/adlist/edit',
                    del_url: 'advertising/adlist/del',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'cid asc,xh asc',
                commonSearch:false, //隐藏搜索
                search:false,//隐藏搜索框
                pagination: false,//不分页
                escape: false, //转义空格
                columns: [
                    [
                        {checkbox: true},
                        {field: 'tit', title:'广告信息',align:'left',operate: 'LIKE',sortable:true,formatter:Table.api.formatter.alink,url:'/admin/advertising/adlist/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        {field: 'name', title: '所属分类',operate: false,algin:'left'},
                        {field: 'sm', title: '广告说明',operate: false,},
                        {field: 'xh', title: '序号',operate: false,sortable: true},
                        {field: 'hits', title: '点击量',operate: false,formatter:Table.api.formatter.alink,url:'/admin/advertising/adlist/visitlog',fieldvaleu:'id',fieldname:'ids',tit:'点击记录',},
                        {field: 'type1', title: '类型',formatter: Table.api.formatter.status, searchList:{0:'图片',1:'代码',2:'文字',3:'动画'}},
                        {field: 'zt', title: '状态',formatter: Table.api.formatter.status, searchList:{1:'展示中',2:'队列中'}},
                        {field: 'sj', title:'编辑时间', formatter: Table.api.formatter.datetime, operate: 'INT', addclass: 'datetimerange', sortable: true},
                        {field: 'dqsj', title:'到期时间', formatter: Table.api.formatter.datetime, operate: 'INT', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate },
                    ]
                ],
                queryParams: function (params) {
                    // var filter = JSON.parse(params.filter);
                    // var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var filter = new Object;
                    var bh = $("#bh").val();
                    if (bh != ''){
                        filter.cid = bh;
                    }
                    params.filter = JSON.stringify(filter);
                    // params.op = JSON.stringify(op);
                    return params;
                }
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        visitlog: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'advertising/adlist/visitlog',
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
                        {field: 'atit', title:'广告名',operate:false},
                        {field: 'source', title:'来源页',},
                        {field: 'create_time', title: '访问时间',formatter: Table.api.formatter.datetime,operate: 'INT',addclass: 'datetimerange', sortable: true},
                        {field: 'ip', title: 'IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'Ip归属地查询',},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != ''){
                        filter.aid = id;
                    }
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


