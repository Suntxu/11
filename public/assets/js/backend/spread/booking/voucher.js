define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/booking/voucher/index',
                    add_url: 'spread/booking/voucher/add',
                    edit_url: 'spread/booking/voucher/edit',
                    //del_url: 'spread/booking/voucher/del',
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
                        { field: 'id', title: 'ID', sortable: true,operate:false, },
                        { field: 'title', title: '套餐优惠标题',operate:'LIKE',},
                        { field: 'unit_price', title: '单个域名优惠()/个',sortable: true,operate:'LIKE',},
                        { field: 'num', title: '认领数量',sortable: true,operate:false,},
                        { field: 'suffix', title: '域名后缀',sortable:false,searchList: $.getJSON('spread/booking/configs/hou'),operate:'IN',addclass:'request_selectpicker',},
                        // { field: 'aid', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName')},
                        { field: 'status', title: '状态',  formatter:Table.api.formatter.status,searchList:{0:"禁用",1:"正常"},custom:{'正常':'green','禁用':'red',}},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
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