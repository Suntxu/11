define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/booking/configs/index',
                    add_url: 'spread/booking/configs/add',
                    edit_url: 'spread/booking/configs/edit?ids=',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',

                // escape:false,
                // // commonSearch:false, //隐藏搜索
                // search:false,//隐藏搜索框
                columns: [
                    [
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类'RANGE'
                            } },
                        { field: 'id', title: 'ID',operate:false, },
                        { field: 'suffix', title: '活动域名',operate:'LIKE',},
                        // { field: 'aid', title: '接口商', searchList: $.getJSON('webconfig/regapi/getRegisterUserName')},
                        { field: 'hd_title', title: '活动标题',operate:'LIKE',},
                        { field: 'ztai', title: '活动状态',operate:false,formatter:Table.api.formatter.status,searchList:{0:'注册中',1:'进行中',2:'已结束',3:'未开始'},custom:{'注册中':'grey','进行中':'green','已结束':'red','未开始':'yellow',},},
                        { field: 'reg_at', title: '活动注册时间',operate:false,sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        { field: 'start_at', title: '活动开始时间',operate:false,sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        { field: 'end_at', title: '活动结束时间',  operate:false,sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        { field: 'dur_active', title: '时长',  operate:false,sortable: true,},
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