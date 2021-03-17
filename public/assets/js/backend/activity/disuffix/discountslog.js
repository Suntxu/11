define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'activity/disuffix/discountslog/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'o.id',
                orderName:'desc',
                columns: [
                    [
                    
                        {field: 'title', title:'标题',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'o.lid', title:'活动ID',visible:false,},
                        {field: 'name', title:'后缀',searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker'},
                        // { field: 'h.aid', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName')},
                        {field: 'claim_num', title:'领取数量',operate:'BETWEEN', sortable:true,
                            footerFormatter: function (data) {
                                var field = 'cnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }},
                        {field: 'reg_num', title:'注册数量',operate:'BETWEEN', sortable:true,
                            footerFormatter: function (data) {
                                var field = 'rnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }
                        },
                        {field: 'show', title: '详情',operate:false,formatter:Table.api.formatter.alink,url:'/admin/vipmanage/recode/reglog',fieldvaleu:'id',fieldname:'a_id',tit:'详情',},
                        {field: 'price', title:'价格',operate:'BETWEEN', sortable:true,},
                        {field: 'o.colony', title:'活动群体',sortable:true,formatter:Table.api.formatter.status,searchList:{1:'新用户',2:'老用户'}},
                        {field: 'o.status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'未使用',1:'冻结中',2:'已用完','-1':'已过期'},custom:{'未使用':'success','冻结中':'warning','已用完':'danger','已过期':'danger'},},
                        {field: 'o.created_at', title: '创建时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,},
                        {field: 'updated_at', title: '更新时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true, },
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