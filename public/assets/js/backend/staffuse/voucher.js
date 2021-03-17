define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'staffuse/voucher/index',
                    add_url: 'staffuse/voucher/add',
                    edit_url: 'staffuse/voucher/edit',
                    del_url: 'staffuse/voucher/del',
                    // multi_url: 'spread/channel/multi',
                    table: 'user',
                }
            });

            var table = $("#table");
             //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function () {
                    if ($("td:eq(6)", this).text() == ' 审核成功') {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                });
            });
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
                        // { field: 'id', title: 'ID', sortable: true,operate:false, },
                        { field: 'bh', title: '编号',},
                        { field: 'money', title: '面值',operate:'BETWEEN',
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
                        { field: 'status', title: '审核状态', sortable:false, formatter: Table.api.formatter.status, searchList: { 0:'未审核', 1:'审核成功',2:'审核失败',3:'已禁用'} },
                        { field: 'sjstat', title: '使用状态',operate:false},
                        { field: 'createtime', title: '申请时间', operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'audittime', title: '生效时间', operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'sxsj', title: '失效时间', operate: false, addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'uid', title: '发放用户',formatter:Table.api.formatter.alink,url:'staffuse/voucherrecord/index',fieldvaleu:'uid',fieldname:'uid',tit:'代金券发放记录',},
                        { field: 'actime', title: '审核时间', operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        // { field: 'remark', title: '备注',operate:false},
                        { field: 'audit_remark', title: '审核备注',operate:false},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            formatter: function (value, row, index) {
                                if (row.status == '审核成功') {
                                    return '';
                                }
                                // if(row.status == '审核失败' || row.status == '已禁用'){
                                //     $(table).data('operate-edit',null);
                                // }
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }

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