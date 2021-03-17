define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/materiallog/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'l.ctime',
                columns: [
                    [
                    
                        { field: 'u.uid', title: '推广人',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'yj', title: '总佣金',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zyj';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'ctime', title: '访问时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'u1.uid', title: '用户名'},
                        { field: 'czje', title: '充值金额',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'l.type', title: '推广类型', formatter: Table.api.formatter.status,searchList: {'1':'未注册','2':'已注册'}, footerFormatter: function (data) {
                               
                                var yzc = data.reduce(function (sum, row) {
                                    return parseFloat(row['yzc']);
                                }, 0);
                                var wzc = data.reduce(function (sum, row) {
                                    return parseFloat(row['wzc']);
                                }, 0);
                                return '未注册访问量：<span style="color:orange;margin-right:4px;">'+wzc+'</span>已注册访问量：<span style="color:green">'+yzc+'</span>';
                            } },
                        { field: 'ip', title: '访客IP',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'IP查询',},
                        { field: 'title', title: '素材',},
                        { field: 'm.type', title: '素材类型', formatter: Table.api.formatter.status,searchList: {0:'普通',1:'专属'}},
                        { field: 'group', title: '落地页'},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['mid'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    console.log(params);
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

