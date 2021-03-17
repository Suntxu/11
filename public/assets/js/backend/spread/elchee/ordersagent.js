define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/ordersagent/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'f.time',
                orderName: 'desc',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'c.bc', title: '订单批次',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'u.uid', title: '买家'},
                        { field: 'u1.uid', title: '分销用户',},
                        {field: 'c.type', title: '订单类型',formatter: Table.api.formatter.status,searchList: {'0':'正常订单','1':'满减订单','2':'微信活动订单','9':'打包域名订单'}},
                        // { field: 'orderid', title: '订单号'},
                        { field: 'paytime', title: '付款时间',addclass:'datetimerange',sortable: true,operate: 'RANGE',},
                        { field: 'c.money', title: '订单金额',operate: 'BETWEEN',sortable: true,
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'final_money', title: '实付金额',operate: 'BETWEEN',sortable: true,
                            footerFormatter: function (data) {
                                var field = 'sfzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        {field: 'tit', title: '域名',operate: 'TEXT', formatter:Table.api.formatter.alink,flag:'text',align:'left',fwhere:[1],finame:['is_sift'],ys:['orange'], pdtxt:['(精选)'], st:['font'],},
                        { field: 'sxf', title: '手续费',operate: false,sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zsxf';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'tmoney', title: '佣金',operate: false,sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zyj';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['c.bc'] = id;
                    //这里可以追加搜索条件
                    var uid = $("#uid").val();
                    if (uid != '')
                        filter['u.uid'] = uid;
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

/**
 * 查看打包域名
 */
function showPack(id){
    layer.load(1);
    $.post('/admin/vipmanage/recode/deallog/show',{id:id},function(res){
        layer.closeAll('loading');
        if(res.code == 1){
            layer.msg(res.msg);
            return false;
        }else{
            var tits = res.data.split(',');
            var html = '<table class="layui-table" style="width:200px;margin:10px 5px;" lay-size="sm">';
            for(var i=0; i<tits.length;i++){
                html += '<tr><td style="padding-left:8%;">'+tits[i]+'</td><tr>';
            }
            html+='</table>';
            layer.open({
                type: 1,
                title: '打包域名列表',
                width:200,
                closeBtn: 0,
                shadeClose: true,
                content: html,
            });
        }
    },'json');

}