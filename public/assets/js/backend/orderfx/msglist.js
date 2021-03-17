define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/orderfx/msglist/index',
                    // edit_url: '/admin/orderfx/msg/edit',
                    del_url: '/admin/orderfx/msglist/del',
                    // multi_url: 'orderfx/Msg/multi_url',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'm.id',
                sortName:'read_time',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'status', title: '读取状态',formatter: Table.api.formatter.status,searchList:{0:'未读',1:'已读'}},
                        {field: 'u.uid', title: '用户'},
                        {field: 'read_time', title: '读取时间',addclass:'datetimerange',operate:'RANGE', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate },
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var status = $("input[name='status']").val();
                    var cid = $("input[name='id']").val();
                    if(status != ''){
                        filter.status=status;
                    }
                    if(cid != ''){
                        filter.cid=cid;
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
function xz(stat){
    if(stat == 0){
        document.getElementById('list').removeAttribute('disabled');
    }else{
        document.getElementById('list').setAttribute('disabled','true');
    }
}

