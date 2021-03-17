define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/booking/manage/index',
                    edit_url: 'spread/booking/manage/edit',
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
                        { field: 'uid', title: '用户名称',sortable:false,operate:false},
                        { field: 'team_no', title: '团队编号',sortable: false,operate:'LIKE',},
                        { field: 'num', title: '总认领数量',
                            sortable:true,operate: 'BETWEEN',
                        },
                        { field: 'claim_num', title: '认领数量',
                            sortable:true,operate: 'BETWEEN',
                            },
                        { field: 'unit_price', title: '活动单价',sortable: true,operate:'LIKE',},
                        { field: 'reg_num', title: '注册数量',sortable:true ,operate:'LIKE',},
                        { field: 'suffix', title: '拼团域名后缀',searchList: Table.api.getSelectDate('spread/booking/configs/houzhui'),operate:'IN',addclass:'request_selectpicker',},
                        { field: 'number', title: '参团人数',align:'left',formatter:Table.api.formatter.alink,url:'/admin/spread/booking/users',fieldvaleu:'id',fieldname:'mid',tit:'点击查看',},
                        { field: 'status', title: '审核状态',formatter:Table.api.formatter.status,searchList:{0:'待审核','-1':'审核失败',1:'组队中',2:'认领完成',3:'认领失败',4:'部分完成',5:'完成'},custom:{'待审核':'grey','审核失败':'red','组队中':'yellow','认领完成':'green','认领失败':'red','部分完成':'green','完成':'green'},},
                        { field: 'start_at', title: '开始时间',operate:false,sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'end_at', title: '结束时间',  operate:false,sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'finished_at', title: '完成时间',  operate:false,sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'rebate_price', title: '返利金额',  operate:false,sortable: true,},
                        { field: 'created_at', title: '创建时间',  operate:'INT',sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
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