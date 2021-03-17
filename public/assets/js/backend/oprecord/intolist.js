define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/intolist/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortOrder:'desc',
                sortName:'b.id',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'bath', title: '批次',},
                        {field: 'b.audit', title: '审核状态',formatter: Table.api.formatter.status,notit:'true',searchList:{1:'等待处理',2:'审核失败',3:'已撤销',4:'审核成功'},},
                        {field: 'name', title: '目标注册商',operate: false,},
                        {field: 'targetuser', title: '目标账号',formatter:Table.api.formatter.onclk,fieldname:'targetuser',affair:'onclick="clickcopy(this)"',},
                        {field: 'email', title: '用户名'},
                        {field: 'moneynum', title: '手续费',operate:false,sortable:true,},
                        {field: 'subdate', title: '提交时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'finishdate', title: '审核时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'b.special', title: '转回域名类型',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'普通',1:'预释放',2:'0元转回'},},
                        {field: 'a.nickname', title: '操作人',},
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

function clickcopy(self){
    $('#target').val($(self).text());
    $('#target').select();
    if (document.execCommand('copy')) {
        document.execCommand('copy');
        layer.msg('目标账号复制成功');
    }else{
        layer.msg('浏览器不支持,请手动复制');
    }
}

