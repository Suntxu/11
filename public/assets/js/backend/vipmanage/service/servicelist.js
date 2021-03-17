define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/service/servicelist/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'l.createtime',
                escape: false,
                columns: [
                    [   
                        {field: 'nickname', title: '昵称'},
                        {field: 'uid', title: '怀米账号',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/service/users/index',fieldvaleu:'uid',fieldname:'u1.uid',tit:'绑定的用户',},
                        {field: 'tel', title: '手机号'},
                        {field: 'qq', title: 'QQ号',operate:false,},
                        {field: 'l.sex', title: '性别',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'女',1:'男'}},
                        {field: 'online', title: 'QQ在线状态',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'不在线',1:'在线'},},
                        {field: 'wx', title: '微信号',operate:false},
                        {field: 'img', title: '微信二维码',formatter: Table.api.formatter.image,operate:false},
                        {field: 'createtime', title: '创建时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,sortable:true},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var uid = $("#uid").val();
                    if (uid != '')
                        filter['u.uid'] = uid;

                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    console.log(params);
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


