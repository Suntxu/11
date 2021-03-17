define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/proorder/index',
                    // add_url: 'spread/proorder/add',
                    // edit_url: 'spread/proorder/edit',
                    // del_url: 'user/proorder/del',
                    // multi_url: 'spread/proorder/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { field: 'ddbh', title: '订单编号',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'userid', title: '用户ID', operate: false },
                        { field: 'uid', title: '用户名称', operate: false },
                        { field: 'u.sj', title: '用户注册时间', addclass: 'datetimerange',operate:'RANGE'},
                        { field: 'd.sj', title: '交易时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',},
                        { field: 'd.money1', title: '金额',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'ifok', title: '状态', formatter: Table.api.formatter.status,searchList: {'0':'失败','1':'成功'},},
                        { field: 'ydmc', title: '渠道名称',operate:false},
                        { field: 'd.channel', title: '渠道名称', visible: false, searchList: $.getJSON('category/getSelectName') },
                        { field: 'username', title: '推广员工',operate:'LIKE' },
                        
                        // { field: 'chenn', title: '渠道名称',  },
                        // { field: 'zt', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'正常','3':'冻结','2':'邮箱未激活'},},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var channel = $("#cel").val();
                    var userid = $("#userid").val();
                    var topspreader = $("#topspreader").val();
                    var ifok = $("#ifok").val();
                    if (ifok != '')
                        filter['d.ifok'] = ifok;
                    if (userid != '')
                        filter['d.userid'] = userid;
                    if (channel != '')
                        filter['d.channel'] = channel;
                    if(topspreader != ''){
                        filter['d.topspreader']=topspreader;
                    }
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
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});