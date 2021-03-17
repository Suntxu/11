define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'spread/elchee/redpacket/index',
                    add_url: 'spread/elchee/redpacket/add/flag/2',
                    edit_url: 'spread/elchee/redpacket/edit/flag/2',
                    del_url: 'spread/elchee/redpacket/del/flag/2', 
                    multi_url: 'spread/elchee/redpacket/multi/flag/2',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'ctime desc, term desc',
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'admin_id', title: '发布人',operate: false,},
                        { field: 'title', title: '标题', operate: 'LIKE' },
                        { field: 'satisfy_amount', title: '满足金额',operate: false,  },
                        { field: 'rebate_amount', title: '折扣金额',operate: false},
                        { field: 'use_shop', title: '指定用户',operate: false},
                        { field: 'term', title: '有效期(天)',operate: false},
                        { field: 'use_type', title: '使用类型', formatter: Table.api.formatter.status,searchList: {'1':'一口价'}},
                        { field: 'use_goods', title: '使用类型商品', formatter: Table.api.formatter.status,searchList: {'0':'不限','1':'满减'}},
                        { field: 'type', title: '用户类型', formatter: Table.api.formatter.status,searchList: {'0':'所有用户'}},
                        { field: 'status', title: '红包状态', formatter: Table.api.formatter.status,searchList: {'0':'已下架','1':'已启用'}},
                        { field: 'ctime', title: '生成时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        { field: 'intime', title: '领取间隔(小时)',operate: false,},
                        { field: 'number', title: '限制次数',operate: false,},
                         { field: 'stime', title: '领取(开始)时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        { field: 'etime', title: '截止时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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