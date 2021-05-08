define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/loginlog/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'l.sj',
                pageSize: 25,
                escape: false, //转义空格
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'u.uid', title: '用户名',},
                        {field: 'l.sj', title: '登录时间',operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,defauleValue:getTimeFrame},
                        {field: 'l.country', title: '国家/地区',},
                        {field: 'l.city', title: '城市',},
                        {field: 'l.type', title: '登陆类型',searchList:{0:'正常登陆',1:'手机号(异地)',2:'邮箱',3:'身份证',4:'IP白名单'}},
                        {field: 'l.uip', title: '登录IP',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'l.uip',fieldname:'wd',tit:'Ip归属地查询',},
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


