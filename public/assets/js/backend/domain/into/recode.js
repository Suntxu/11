define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/into/recode/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortOrder:'desc',
                sortName:'b.subdate',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'd.domian', title: '域名',operate:'TEXT'},
                        {field: 'b.audit', title: '审核状态',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'等待处理',1:'审核成功',2:'审核失败',3:'已撤销',4:'正在审核'},align:'center',},
                        {field: 'b.reg_id', title: '目标注册商',operate: false,},
                        {field: 'b.targetuser', title: '目标账号',operate: false,},
                        {field: 'b.subdate', title: '提交时间',operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'b.finishdate', title: '审核时间',operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'b.email', title: '申请人',operate: false,},
                        {field: 'b.bath', title: '批次',},
                        {field: 'b.special', title: '转回域名类型',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'普通',1:'预释放',2:'0元转回'},},
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




