define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/user/index',
                    edit_url: 'spread/elchee/user/edit',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'p.id',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'u.uid', title: '用户',formatter:Table.api.formatter.alink,url:'/admin/spread/elchee/prouser/',fieldvaleu:'uid',fieldname:'u1.uid',tit:'推广用户', footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'ctime', title: '申请时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'p.status', title: '审核状态',formatter: Table.api.formatter.status,searchList: {'0':'未审核','1':'审核通过','2':'审核被拒绝'},notit:'true',},
                        { field: 'visitorCount', title: '访问量',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zfwl';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }
                        },
                         { field: 'regCount', title: '注册量',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zzcl';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }
                        },
                         { field: 'chzj', title: '充值量',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zczl';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'wait_reward', title: '待到账金额',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'wd';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'extracted_reward', title: '已到账金额',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'yy';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'zz', title: '总金额',operate:false,sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'rebate', title: '分销返点比',operate:false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'rebate',hz:true,affair:'onblur="updateRebate(this)"',},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter.nickname = id;
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

//ajax 点击返点比例
function updateRebate(self) {
    var oldhtml = self.value;
    var id = self.id;
    var field = self.title;
    $.ajax({
        'type': 'post',
        'data': {'id': id, field: field, val: oldhtml},
        'url': '/admin/spread/elchee/user/updateRebate',
        success: function (data) {
            layer.closeAll('loading');
            if(data.code == 1){
                layer.msg(data.msg);
            }
            self.value = data.val;
        },
        error: function () {
            alert('发送失败');
        },
        beforeSend: function () {
            layer.load();
        }
    });
}