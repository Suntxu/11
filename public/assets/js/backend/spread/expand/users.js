define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/users/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'u.sj',
                orderName:'desc',
                columns: [
                    [
                        { field: 'id', title: '用户ID', sortable: true,operate: false,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'uid', title: '用户名称',formatter:Table.api.formatter.alink,url:'/admin/spread/expand/users/record/',fieldvaleu:'uid',fieldname:'uid',tit:'记录查询', },
                        { field: 'sj', title: '用户册时间',addclass:'datetimerange',operate: 'RANGE',},
                        { field: 'zt', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'正常','3':'冻结','2':'邮箱未激活'},},
                        { field: 'mot', title: '手机',},
                        { field: 'nc', title: '邮箱',},
                        { field: 'je', title: '充值总金额',operate:false,sortable:true,formatter:Table.api.formatter.alinks,url:'/admin/spread/expand/proorder/',fieldvaleu:['id',1],fieldname:['userid','ifok'],tit:'推广订单',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'ydmc', title: '渠道名称',operate:false},
                        { field: 'channel', title: '渠道名称', visible: false, searchList: $.getJSON('category/getSelectName')},
                        { field: 'nickname', title: '推广员工',operate:'LIKE'},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter.nickname = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    console.log(params);
                    return params;
                }
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
       
    };
    return Controller;
});
