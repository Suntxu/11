define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/withdraw/index',
                    edit_url: 'spread/elchee/withdraw/edit',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'l.status asc,l.ctime desc',
                columns: [
                    [
                        { field: 'uid', title: '用户名称', },
                        { field: 'money', title: '提现金额',operate: false,sortable: true,},
                        { field: 'on', title: '流水信息',operate: false,sortable: true,formatter:Table.api.formatter.alink,url:'/admin/spread/elchee/flow',fieldvaleu:'id',fieldname:'id',tit:'交易订单',},
                        { field: 'status', title: '提取状态',formatter: Table.api.formatter.status,searchList: {'0':'等待审核','1':'提取成功','2':'提取失败'},},
                        { field: 'l.ctime', title: '申请时间',addclass:'datetimerange',sortable: true,operate: 'RANGE',formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['l.id'] = id;
                    var status = $("#status").val();
                    if (status != '')
                        filter['l.status'] = status;
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
$(document).on("click", ".selectl", function () {
    var id = $(this).attr('id');
    var m = id.substr(id.length-1,1);
    if(m == 6){
        $('#l\\.ctime').val('');
    }else{
        $('.ranges ul').children('li').get(m).click();
    }
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    });
});