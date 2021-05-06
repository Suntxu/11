define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/reglog/index',
                    table: 'user',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'d.id',
                orderName:'desc',
                escape: false, //转义空格
                pageSize:25,
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'u.uid', title: '用户名',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'spec', title: '排除用户',operate: 'TEXT',visible:false,},

                        {field: 'd.tit', title: '域名',operate: 'TEXT',},

                        {field: 'd.money', title: '单价',operate: 'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                        }},
                        {field: 'cos_price', title: '成本价',operate: false,sortable:true,footerFormatter: function (data) {
                                var field = 'czje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        {field: 'r.createtime', title: '任务提交时间',operate: 'INT', sortable:true, addclass: 'datetimerange', formatter: Table.api.formatter.datetime,defaultValue:getTimeFrame()},

                        {field: 'group', title: '注册完成时间',operate: 'INT',sortable:true, addclass: 'datetimerange', formatter: Table.api.formatter.datetime,},

                        {field: 'special_condition', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        {field: 'd.hz', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        { field: 'r.a_type', title: '注册类型',formatter: Table.api.formatter.status, searchList:{0:'普通',1:'拼团',2:'限量',3:'注册包'}},

                        {field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'r.uip', title: '注册IP',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'r.uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ] 
                ],


                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var aid = $("#aid").val();
                    if (aid != ''){
                        filter['r.a_id'] = aid;
                    }
                    //这里可以追加搜索条件
                    var h_id = $("#h_id").val();
                    if (h_id != ''){
                        filter['r.a_id'] = h_id;
                        op['r.a_id'] = 'IN';
                    }

                    var a_type = $("#a_type").val();
                    if (a_type != ''){
                        filter['r.a_type'] = a_type;
                    }

                    var l_id = $("#l_id").val();
                    if (l_id != ''){
                        filter['r.l_id'] = l_id;
                    }

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



