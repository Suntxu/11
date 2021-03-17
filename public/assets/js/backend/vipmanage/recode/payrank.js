define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/payrank/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'sj',
                escape:false,
                pageSize:25,
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        { field: 'ddbh', title: '订单编号',operate:'LIKE',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'uid', title: '会员账号',},
                        { field: 'd.sj', title: '交易时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',},
                        { field: 'd.money1', title: '金额',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '记录总金额：'+total_sum.toFixed(2);
                            }
                        },
                        { field: 'ifok', title: '状态',
                            footerFormatter: function (data) {
                                var field = 'suc';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '成功总金额：'+total_sum.toFixed(2);
                            }
                        ,sortable:true, formatter: Table.api.formatter.status,searchList: {'0':'失败','1':'成功'},},
                        { field: 'bz', title: '交易方式',operate:'LIKE',formatter: Table.api.formatter.status,searchList: {'微信支付':'微信支付','支付宝':'支付宝','人工充值':'人工充值','财付通':'财付通','快钱':'快钱','汇潮支付':'汇潮支付','盛付通':'盛付通'}},
                        { field: 'wxddbh', title: '交易号',},
                        { field: 'd.remark', title: '备注',operate:'LIKE'},
                        { field: 'uip', title: 'IP地址',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        { field: 'op', title: '操作',operate:false},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter['u.uid'] = gro;
                    var id = $("#id").val();
                    if (id != '')
                        filter['d.id'] = id;
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

// 补单
function bd(self){

    layer.prompt({title: $(self).data('title'), formType: 0}, function(pass, index){
        layer.close(index);
        layer.load();
        $.post($(self).data('url'),{id:$(self).data('id'),'number':pass},function(data){
            layer.closeAll('loading');
            layer.msg(data.msg);
            if(data.code == 0){
                $('.btn-refresh').click();
            }
        })
    });
}