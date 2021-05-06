define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'total/actionsearchrecord/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'type',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'type', title: '搜索类型',searchList:{0:'域名简介',1:'域名',2:'店铺名字',3:'店铺QQ'}},
                        {field: 'jcount', title: '今日',operate:false,sortable:true},
                        {field: 'wcount', title: '本周',operate:false,sortable:true},
                        {field: 'mcount', title: '本月',operate:false,sortable:true},
                        {field: 'zcount', title: '总数量',operate:false,sortable:true},
                        { field: 'operate', title: __('Operate'), table: table, 
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name:'详情',
                                    text: '详情',
                                    title:'详情',
                                    classname:'btn btn-xs dialogit btn-warning',
                                    icon:'fa fa-deaf',
                                    url: function(res){
                                        return '/admin/total/actionsearchrecord/details?r.type='+res.stype;
                                    }
                                }] 
                            }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        details: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/actionsearchrecord/details',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.id',
                escape: false, //转义空格
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'r.userid', title: '用户ID',sortable:true},
                        // {field: 'u.uid', title: '用户',},
                        {field: 'r.type', title: '类型',searchList:{0:'域名简介',1:'域名',2:'店铺名字',3:'店铺QQ'}},
                        {field: 'r.data', title: '关键词',operate:'like'},
                        {field: 'total', title: '结果数',operate:false,sortable:true},
                        {field: 'r.create_time', title: '访问时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'ip', title: '登录IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'l.uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ]
                ],
                
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        pagesearch: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/actionsearchrecord/pageSearch',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.id',
                escape: false, //转义空格
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'r.userid', title: '用户ID',sortable:true},
                        // {field: 'u.uid', title: '用户',},
                        {field: 'r.type', title: '类型',searchList:{4:'一口价域名',5:'打包一口价',6:'精选域名',7:'店铺列表'}},
                        {field: 'r.data', title: '关键词',operate:'like',align:'left',width:800, cellStyle:function(){
                            return {
                                'css':{'word-break':'break-all'}
                            };
                        }},
                        {field: 'total', title: '结果数',operate:false,sortable:true},
                        {field: 'r.create_time', title: '访问时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'ip', title: '登录IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'l.uip',fieldname:'wd',tit:'Ip归属地查询',},
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


