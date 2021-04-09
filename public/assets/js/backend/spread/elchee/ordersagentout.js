define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/ordersagentout/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'c.id',
                orderName: 'desc',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'c.bc', title: '订单批次',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'u.uid', title: '买家'},
                        { field: 'u1.uid', title: '分销用户',},
                        { field: 'c.status', title: '状态',defaultValue:1,searchList: {0:'未付款',1:'已付款',2:'待处理',9:'已取消'}},
                        { field: 'paytime', title: '付款时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'out_time', title: '外部付款时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
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
                        { field: 'c.rebate_type', title: '返款状态',searchList: {0:'未反款',1:'已通知',2:'已返款'}},
                        { field: 'tit', title: '域名',operate: 'TEXT', formatter:Table.api.formatter.alink,flag:'text',align:'left',fwhere:[1],finame:['is_sift'],ys:['orange'], pdtxt:['(精选)'], st:['font'],},
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
                        { field: 'zcs_money', title: '注册商价格',operate: 'BETWEEN',sortable:true,
                            footerFormatter: function (data) {
                                var field = 'zcsm';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'jm_id', title: '聚名卖家id',},
                        { field: 'u2.uid', title: '销售用户',},
                        { field: 'operate', title: __('Operate'), table: table, 
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name:'已通知',
                                    text: '已通知',
                                    title:'怀米淘订单-已通知',
                                    classname:'btn btn-xs btn-danger btn-ajax',
                                    confirm:'您确定对该条订单进行 【已通知】 操作吗？',
                                    visible: function(res){
                                        if(res.status == 1 && res.rebate_type == 0){
                                            return true;
                                        }
                                        return false;
                                    },
                                    url: function(res){
                                        return '/admin/spread/elchee/ordersagentout/upmodi?id='+res.cid+'&status=1';
                                    },
                                    success: function (data,ret) {
                                        layer.msg(ret.msg);
                                        if(ret.code==1){
                                            $('.btn-refresh').click();
                                        }
                                        return false;
                                    }

                                },{
                                    name:'已反款',
                                    text: '已反款',
                                    title:'怀米淘订单-已反款',
                                    classname:'btn btn-xs btn-warning btn-ajax',
                                    confirm:'您确定对该条订单进行 【已反款】 操作吗？',
                                    visible: function(res){
                                        if(res.status == 1 && res.rebate_type == 1){
                                            return true;
                                        }
                                        return false;
                                    },
                                    url: function(res){
                                        return '/admin/spread/elchee/ordersagentout/upmodi?id='+res.cid+'&status=2';
                                    },
                                    success: function (data,ret) {
                                        layer.msg(ret.msg);
                                        if(ret.code==1){
                                            $('.btn-refresh').click();
                                        }
                                        return false;
                                    }
                                }] 
                        }
                    ]
                ],
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recordsales: function () {
             // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'spread/elchee/ordersagentout/recordsales',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                // sortName: 'c.id',
                orderName: 'desc',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'tit', title: '域名',formatter: Table.api.formatter.search,},
                        { field: 'money', title: '价格',operate: false,sortable:true,operate: 'BETWEEN',},
                        { field: 'inserttime', title: '出售时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'shopid', title: '店铺id',sortable: true,},
                        { field: 'wxjc', title: '微信检测',searchList:{1:'未拦截',2:'已拦截',3:'未拦截(确认访问)',4:'未拦截(复制访问)'}},
                        { field: 'qqjc', title: 'QQ检测',searchList: {1:'绿色认证',2:'未拦截',3:'已拦截'}},
                        { field: 'jzls', title: '建站历史',sortable: true,operate: 'BETWEEN',},
                        { field: 'bdpj', title: '百度评价',sortable: true,},
                        { field: 'jzjls', title: '建站记录数',sortable: true,operate: 'BETWEEN',},
                        { field: 'bdqz', title: '百度权重',sortable: true,operate: 'BETWEEN',},
                        { field: 'pr', title: 'PR值',sortable: true,operate: 'BETWEEN',},
                        { field: 'bdwl', title: '百度外链',sortable: true,operate: 'BETWEEN',},
                        { field: 'bdsl', title: '百度收录',sortable: true,operate: 'BETWEEN',},
                        { field: 'sgwl', title: '搜狗外链',sortable: true,operate: 'BETWEEN',},
                        { field: 'sgsl', title: '搜狗收录',sortable: true,operate: 'BETWEEN',},
                        { field: 'sosl', title: '360收录',sortable: true,operate: 'BETWEEN',},
                        { field: 'smsl', title: '神马收录',sortable: true,operate: 'BETWEEN',},
                        { field: 'azwl', title: '网站外链',sortable: true,operate: 'BETWEEN',},
                        { field: 'bdrz', title: '百度认证',searchList: {0:'未查',1:'认证',2:'未认证'}},
                        { field: 'bdjc', title: '百度检测',searchList: {0:'未检测',1:'未知',2:'安全',3:'危险'}},
                        { field: 'qiang', title: '是否被墙',searchList: {0:'未查',1:'正常',2:'污染',3:'被抢'}},
                        { field: 'txt', title: '备注',operate: false,},

                    ]
                ],
            });
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