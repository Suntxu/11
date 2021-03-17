define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/booking/offered/index',
                    //add_url: 'spread/booking/offered/add',
                    edit_url: 'spread/booking/offered/edit',
                    //del_url: 'spread/booking/offered/del',
                    // multi_url: 'spread/channel/multi',
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
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类'RANGE'
                            } },
                        { field: 'id', title: 'ID',operate:false,sortable:true, },
                        { field: 'tid', title: '参团ID',operate:'LIKE',},
                        { field: 'b.uid', title: '用户名称',operate:false,},
                        { field: 'claim_num', title: '认领数量', sortable:true, operate:'LIKE',},
                        { field: 'reg_num', title: '注册数量', sortable:true,  operate:'LIKE',},
                        { field: 'status', title: '状态',   formatter:Table.api.formatter.status,searchList:{'-1':"活动失败",0:"认领成功",1:"未注册完",2:"已完成"},custom:{'活动失败':'red','认领成功':'green','未注册完':'yellow','已完成':'green'}},
                        { field: 'pay_price', title: '支付金额',sortable:true,  operate:false,},
                        { field: 'default_price', title: '违约金', sortable:true, operate:false,},
                        { field: 'refund_price', title: '退款金额', sortable:true, operate:false,},
                        { field: 'finished_at', title: '完成时间', sortable:true, operate:false,},
                        { field: 'created_at', title: '创建时间', sortable:true, operate:'INT',addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: '',
                                title:'禁用',
                                icon: 'fa fa-cut',
                                classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                url: 'spread/expand/voucher/disable',
                                confirm: '确认要禁用吗',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                    Layer.msg('操作成功');
                                },
                                field:'status',
                                val:'1',
                                wh:'!=',
                            }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
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