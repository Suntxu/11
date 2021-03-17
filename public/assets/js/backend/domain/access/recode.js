define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/access/recode/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortOrder:'asc,desc',
                sortName:'b.subdate desc,b.audit asc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'd.domain', title: '域名',operate:'TEXT'},
                        {field: 'b.audit', title: '审核状态',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'待审核',1:'任务执行成功',2:'审核失败',3:'任务执行中',4:'用户取消'},align:'center',},
                        {field: 'd.status', title: '转入状态',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'待审核',1:'转入中',2:'转入成功',3:'转入失败',4:'已取消'},sortable:true,},
                        {field: 'd.remark', title: '转入备注',operate:false,},
                        {field: 'b.reg_id', title: '目标注册商',formatter: Table.api.formatter.status,notit:'true',searchList: $.getJSON('category/getcategory?type=api&xz=parent')},
                        {field: 'b.api_id', title: '目标账号',operate:false,},
                        {field: 'b.subdate', title: '提交时间',sortable:true, operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'b.finishdate', title: '审核时间',sortable:true, operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'd.audittime', title: '执行时间',operate: false, addclass: 'datetimerange', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'u.uid', title: '申请人',},
                        {field: 'b.email', title: '申请账号',operate: false,},
                        {field: 'b.bath', title: '批次',},
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
        }
    };
    return Controller;
});



