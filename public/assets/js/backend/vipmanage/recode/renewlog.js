define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/renewlog/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.createtime',
                escape: false, //转义空格
                pageSize:25,
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'u.uid', title: '用户名',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'r.createtime', title: '任务提交时间',operate: 'INT',sortable:true, addclass: 'datetimerange', formatter: Table.api.formatter.datetime,defaultValue:getTimeFrame()},
                        {field: 'd.tit', title: '域名',operate: 'TEXT',},
                        {field: 'd.money', title: '单价',operate: false,sortable:true,footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                        }},
                       
                        {field: 'special_condition', title: '注册商', searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        {field: 'group', title: '后缀', visible:false, searchList: $.getJSON('domain/manage/getDomainHz'),},
                        {field: 'api_id', title: '接口商', searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'r.uip', title: '注册IP',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'r.uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ] 
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter['u.uid'] = gro;
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


