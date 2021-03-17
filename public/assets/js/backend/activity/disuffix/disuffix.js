define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'activity/disuffix/disuffix/index',
                    add_url: 'activity/disuffix/disuffix/add',
                    edit_url: 'activity/disuffix/disuffix/edit',
                    del_url: 'activity/disuffix/disuffix/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'h.lid desc,h.sort desc',
                columns: [
                    [
                    // formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('domain/manage/getDomainHz'),
                        {checkbox: true},
                        {field: 'name', title:'后缀',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            },operate:'LIKE',formatter:Table.api.formatter.alinks,url:'/admin/activity/disuffix/discountslog',fieldvaleu:['lid','name'],fieldname:['o.lid','name'],tit:'详情',},
                        // {field: 'aid', title:'接口商',operate:false},
                        {field: 'title', title:'活动标题'},
                        {field: 'h.lid', title:'活动ID',visible:false,},
                        {field: 'new_colony', title:'新用户价格',operate:'BETWEEN', sortable:true,},
                        {field: 'new_num', title:'新用户领取数量',operate:'BETWEEN', sortable:true,},
                        {field: 'old_colony', title:'老用户价格',operate:'BETWEEN', sortable:true,},
                        {field: 'old_num', title:'老用户领取数量',operate:'BETWEEN', sortable:true,},
                        {field: 'cost_price', title:'原价',operate:'BETWEEN', sortable:true,},
                        {field: 'num', title:'已领取数量',operate:false, sortable:true,
                            footerFormatter: function (data) {
                                var field = 'znum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }
                        },
                        {field: 'rnum', title:'已注册数量',operate:false, sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zrnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }
                        },
                        {field: 'start_time', title: '开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: '结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'show', title: '详情',operate:false,formatter:Table.api.formatter.alink,url:'/admin/vipmanage/recode/reglog',fieldvaleu:'id',fieldname:'h_id',tit:'详情',},
                        {field: 'h.status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'停用',1:'启用'},custom:{'启用':'success','停用':'danger'},},
                        {field: 'Operate', title: __('Operate'),operate:false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            formatter: function (value, row, index) {
                                    var that = $.extend({}, this);
                                    if(row.flag === 0){
                                        $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                    }
                                    that.table = table; 
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                        },
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
