define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oldrecord/transfershow/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                columns: [
                    [
                        {field: 'oper', title: '操作类型',operate:false,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'id', title: 'ID'},
                        {field: 'tit', title: '域名',operate:'TEXT'},
                        {field: 'TaskStatusCode', title: '任务状态',formatter: Table.api.formatter.status,searchList: {0:'执行中',2:'执行成功',3:'执行失败',9:'已退款'}},
                        {field: 'ErrorMsg', title: '错误信息',operate:false},
                        {field: 'spec', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        {field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'CreateTime', title: '完成时间',operate:'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true,footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    if(row[field] == '无统计'){
                                        return '无统计';
                                    }
                                    return parseFloat(row[field]);
                                }, 0);
                                if(total_sum == '无统计'){
                                    return '无特殊统计';
                                }
                                return '总金额：'+total_sum.toFixed(2);
                            }},
                        {field: 'rems', title: '特殊备注',operate:false,},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#tid").val();
                    if (gro != '')
                        filter['group'] = gro;
                    var type = $("#type").val();
                    if (type != '')
                        filter['special_condition'] = type;
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
