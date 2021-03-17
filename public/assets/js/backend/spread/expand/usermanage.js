define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/usermanage/index',
                    add_url: 'spread/expand/usermanage/add/flag/2',
                    edit_url: 'spread/expand/usermanage/edit/flag/2',
                    del_url: 'spread/expand/usermanage/del/flag/2', 
                    multi_url: 'spread/expand/usermanage/multi/flag/2',
                    table: 'user',
                }
            });

            var table = $("#table");
            var id = null;

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [  
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            } },
                        { field: 'id', title: '员工ID',operate: false,},
                        { field: 'username', title: '员工账号', operate: 'LIKE' },
                        { field: 'nickname', title: '员工昵称', operate: 'LIKE',formatter:Table.api.formatter.alink,url:'/admin/spread/expand/users/',fieldvaleu:'nickname',fieldname:'nickname',tit:'用户管理', },
                        { field: 'createtime', title: '注册时间',operate: 'RANGE',addclass: 'datetimerange',},
                        { field: 'user_un', title: '已注册用户',operate: false},
                        { field: 'user_pay', title: '已充值用户',operate: false},
                        { field: 'jine', title: '总金额',operate: false,formatter:Table.api.formatter.alinks,url:'/admin/spread/expand/proorder/',fieldvaleu:['id',1],fieldname:['topspreader','ifok'],tit:'充值记录',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        // { field: 'chenn', title: '渠道名称',  },
                        { field: 'status', title: '状态', formatter: Table.api.formatter.status,searchList: {'normal':'正常','hidden':'隐藏'},

                        },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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