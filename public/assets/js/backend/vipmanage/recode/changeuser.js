define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/changeuser/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'l.id',
                orderName:'desc',
                columns: [
                    [
                        {field: 'u.uid', title: '用户名',},
                        {field: 'yuid', title: '旧值',},
                        {field: 'l.uid', title: '新值'},
                        {field: 'l.type', title: '类型',formatter: Table.api.formatter.status,searchList: {0:'账户修改',1:'密码修改',2:'安全码修改',3:'更换手机号',4:'修改手机号(邮箱)'}},
                        {field: 'create_time', title: '修改时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,defaultValue:getTimeFrame()},
                        {field: 'uip', operate:false, title: 'IP地址',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ] 
                ],
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



