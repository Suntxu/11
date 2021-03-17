define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/disuffix/dismate/index',
                    add_url: 'activity/disuffix/dismate/add',
                    edit_url: 'activity/disuffix/dismate/edit',
                    // del_url: 'activity/disuffix/dismate/del',
                    table: 'user',
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
                        {checkbox: true},
                        {field: 'id', title:'ID',operate:false},
                        {field: 'title', title:'标题',formatter:Table.api.formatter.alink,url:'/admin/activity/disuffix/disuffix',fieldvaleu:'id',fieldname:'h.lid',tit:'详情',},
                        {field: 'show', title:'订单详情',formatter:Table.api.formatter.alink,url:'/admin/activity/disuffix/discountslog',fieldvaleu:'id',fieldname:'o.lid',tit:'详情',},
                        {field: 'colony', title:'活动群体',sortable:true,formatter:Table.api.formatter.status,searchList:{0:'所有用户',1:'新用户',2:'老用户'}},
                        {field: 'type', title:'多次购买',sortable:true,formatter:Table.api.formatter.status,searchList:{0:'不允许',1:'允许'}},
                        {field: 'status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'停用',1:'启用'},custom:{'启用':'success','停用':'danger'},},
                        {field: 'group', title: '是否过期',formatter:Table.api.formatter.status,searchList:{1:'未过期',2:'已过期'},custom:{'未过期':'success','已过期':'danger'},},
                        {field: 'start_time', title: '开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: '结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'created_at', title: '创建时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,},
                        {field: 'Operate', title: __('Operate'),operate:false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,

                              formatter: function (value, row, index) {
                                    var that = $.extend({}, this);
                                    if(row.flag === 0){
                                        $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                    }else{
                                        $(table).data("operate-edit", true);
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