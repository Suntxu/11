define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/voucher/index',
                    add_url: 'spread/expand/voucher/add',
                    edit_url: 'spread/expand/voucher/edit',
                    // del_url: 'spread/channel/del',
                    // multi_url: 'spread/channel/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'status,createtime',
                sortOrder:'asc,desc',
                columns: [
                    [
                       { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类'RANGE'
                            } },
                        { field: 'id', title: 'ID', sortable: true,operate:false, },
                        { field: 'bh', title: '编号',},
                        { field: 'v.money', title: '面值',operate:'BETWEEN',
                            footerFormatter: function (data) {  
                                var field = 'mezje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'kyye', title: '可用余额',operate:'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'kyje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'sycp', title: '适用产品',operate:false},
                        { field: 'sid', title: '适用产品', visible: false, formatter: Table.api.formatter.status, searchList: Table.api.getcategory('voucher', 'category/getcategory','aaid','parent') },
                        { field: 'cjid_text', title: '使用场景', operate: false, },
                        { field: 'cjid', title: '使用场景', visible: false, formatter: Table.api.formatter.status, searchList: Table.api.getcategory('voucher', 'category/getcategory','aaid','child') },
                        { field: 'v.status', title: '审核状态', sortable:false, formatter: Table.api.formatter.status, searchList: { 0:'未审核', 1:'审核成功',2:'审核失败',3:'已禁用' } },
                        { field: 'sjstat', title: '使用状态',operate:false},
                         { field: 'v.createtime', title: '申请时间', operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'audittime', title: '生效时间', operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'sxsj', title: '失效时间', operate: false, addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'actime', title: '审核时间', operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'uid', title: '发放用户',formatter:Table.api.formatter.alink,url:'spread/expand/voucherrecord/index',fieldvaleu:'uid',fieldname:'uid',tit:'代金券发放记录',},
                        { field: 'nickname', title: '申请人',formatter:Table.api.formatter.alink,url:'spread/expand/voucherrecord/index',fieldvaleu:'nickname',fieldname:'nickname',tit:'代金券发放记录',},
                        { field: 'remark', title: '备注',operate:false},
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
        //     {
        //     name: 'ajax',
        //     text: __('发送Ajax'),
        //     title: __('发送Ajax'),
        //     classname: 'btn btn-xs btn-success btn-magic btn-ajax',
        //     icon: 'fa fa-magic',
        //     url: 'example/bootstraptable/detail',
        //     confirm: '确认发送',
        //     success: function (data, ret) {
        //         Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
        //         //如果需要阻止成功提示，则必须使用return false;
        //         //return false;
        //     },
        //     error: function (data, ret) {
        //         console.log(data, ret);
        //         Layer.alert(ret.msg);
        //         return false;
        //     }
        // },

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