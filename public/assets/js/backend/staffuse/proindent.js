define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'staffuse/proindent/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                escape: false, //转义空格
                pk: 'c.id',
                sortName: 'id',
                columns: [
                    [
                        { field: 'bc', title: '订单批次',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            } },
                        {field: 'tit', title: '域名',operate: 'TEXT', formatter:Table.api.formatter.alink,flag:'text',align:'left',fwhere:[1],finame:['is_sift'],ys:['orange'], pdtxt:['(精选)'], st:['font'],},
                        { field: 'uid', title: '用户名称', operate: false },
                        { field: 'paytime', title: '交易时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',},
                        { field: 'money', title: '金额',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                       { field: 'final_money', title: '实付金额',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'sfzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'tmoney', title: '佣金',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'yjzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        {field: 'c.type', title: '订单类型',formatter: Table.api.formatter.status,searchList: {'0':'正常订单','1':'满减订单','2':'微信活动订单','9':'打包域名订单'}},
                        { field: 'ydmc', title: '渠道名称',operate:false   },
                        { field: 'c.channel', title: '渠道名称', visible: false, formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('category/getSelectName') },
                        { field: 'c.status', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'已付款','2':'未付款'},},
                       
                        // { field: 'zt', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'正常','3':'冻结','2':'邮箱未激活'},},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
             queryParams: function (params) {
                var filter = JSON.parse(params.filter);
                var op = JSON.parse(params.op);
                //这里可以追加搜索条件
                var userid = $("#parm").val();
                var stauts = $("#stauts").val();
              
                if (stauts != '')
                    filter.stauts = stauts;
                if (userid != '')
                    filter.userid = userid;
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