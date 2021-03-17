define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/channel/index',
                    add_url: 'spread/expand/channel/add',
                    edit_url: 'spread/expand/channel/edit',
                    del_url: 'spread/expand/channel/del',
                    multi_url: 'spread/expand/channel/multi',
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
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            } },
                        { field: 'id', title: 'ID', sortable: true,operate:false, },
                        { field: 'name', title: '渠道名称',},
                        // { field: 'id', title: '渠道名称', visible: false, formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('spread_channel','id,name','status=normal', 'spread/channel/getQDName') },
                        { field: 'category_text', title: '渠道类型', operate: false, },
                        { field: 'category_id', title: '渠道类型', visible: false, searchList: $.getJSON('category/getcategory?type=spread&xz=parent')},
                        { field: 'cost', title: '费用/每月', operate: 'BETWEEN', sortable: true },
                        { field: 'status', title: '渠道状态', formatter: Table.api.formatter.status, searchList: { 'normal': __('Normal'), 'hidden': __('Hidden') } },
                        { field: 'ddzje', title: '充值总金额', operate: false,formatter:Table.api.formatter.alinks,url:'/admin/spread/expand/proorder/',fieldvaleu:['id',1],fieldname:['cel','ifok'],tit:'充值记录',
                            footerFormatter: function (data) {
                                var field = 'czje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                         // formatter:Table.api.formatter.alinks,url:'/admin/spread/proindent/',fieldvaleu:['id',1],fieldname:['cel','c.status'],tit:'推广订单',
                        { field: 'czzje', title: '订单交易金额', operate: false,
                            footerFormatter: function (data) {
                                var field = 'dzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'sfje', title: '订单交易实付金额', operate: false,
                            footerFormatter: function (data) {
                                var field = 'sfzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'domainje', title: '域名注册金额', operate: false,
                            footerFormatter: function (data) {
                                var field = 'domainzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                       
                        { field: 'reguser', title: '注册人数', operate: false,},
                        { field: 'uv', title: 'UV', operate: false,},
                        { field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
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