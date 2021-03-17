define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/booking/users/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                columns: [
                    [
                        { field: 'id', title: 'ID', sortable: true,operate:false, },
                        // { field: 'sid', title: '域名编号',sortable: true,operate:false,},
                        { field: 'name', title: '用户名',operate:false,},
                        { field: 'claim_num', title: '认领数量',sortable: true,operate:'LIKE',},
                        { field: 'reg_num', title: '注册数量', sortable:true,operate: 'BETWEEN',},
                        { field: 'status', title: '状态',operate:false},
                        { field: 'pay_price', title: '支付金额',  operate:false},
                        { field: 'default_price', title: '违约金额',  operate:false},
                        { field: 'refund_price', title: '退款金额',  operate:false},
                        { field: 'finished_at', title: '完成时间',  operate:false},
                        { field: 'created_at', title: '加入时间',  operate:false},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != ''){
                        filter['tid'] = id;
                    }
                    //这里可以追加搜索条件
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