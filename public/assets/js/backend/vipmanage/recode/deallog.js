define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/deallog/index',
                    table: 'user',
                }
            });

            var table = $("#table");
           
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'c.paytime',
                escape: false, //转义空格
                pageSize:25,
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'bc', title: '订单批次',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'group', title: '域名',operate: 'TEXT', formatter:Table.api.formatter.alink,flag:'text',align:'left',fwhere:[1],finame:['is_sift'],ys:['orange'], pdtxt:['(精选)'], st:['font'],},
                        { field: 'special_condition', title: '后缀',visible:false, searchList: $.getJSON('domain/manage/getDomainHz'),},
                        {field: 'u.uid', title: '买家账号',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/setuser/index',fieldvaleu:'userid',fieldname:'id',tit:'用户设置',},
                        {field: 'money', title: '交易金额',operate: 'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        {field: 'final_money', title: '实付金额',operate: 'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zje1';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        {field: 'sxf', title: '手续费',operate: 'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'sxfzje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        {field: 'tmoney', title: '佣金',operate: 'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'tmon';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        {field: 's.uid', title: '卖家账号',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/setuser/index',fieldvaleu:'selleruserid',fieldname:'id',tit:'用户设置',},
                        {field: 'p.flag', title: '店铺类型',formatter: Table.api.formatter.status, notit:true, searchList:{0:'普通店铺',1:'怀米网店铺',2:'消保店铺'},},
                        {field: 'c.status', title: '交易状态',formatter: Table.api.formatter.status,searchList: {'0':'支付中','1':'支付成功'}},
                        {field: 'c.type', title: '订单类型',formatter: Table.api.formatter.status,searchList: {'0':'正常订单','1':'满减订单','2':'微信活动订单','9':'打包域名订单'}},
                        {field: 'c.sptype', title: '订单来源',searchList: {'0':'官网','1':'推广员','2':'怀米大使','3':'分销系统'}},
                        {field: 'is_sift', visible:false, title: '是否为精选订单',formatter: Table.api.formatter.status,searchList: {'0':'否','1':'是'}},
                        {field: 'c.sj', title: '订单创建时间',operate: 'RANGE', addclass: 'datetimerange',sortable:true,defaultValue:getTimeFrame() },
                        {field: 'paytime', title: '订单支付时间',operate: 'RANGE', addclass: 'datetimerange',sortable:true,},
                        {field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '买家用户中心',
                                    text: '买家用户中心',
                                    title: '买家用户中心',
                                    classname: 'btn btn-xs  btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: '/admin/vipmanage/usersop/Jump?flag=new',
                                    error: function (data,ret) {
                                        if(ret.code==0){
                                            window.open(ret.weburl+'api/apioperate/goadmin?sign='+ret.token+'&uid='+ret.uid+'&time='+ret.time+'&admin_id='+ret.admin_id);
                                        }else{
                                            layer.msg(ret.msg);
                                        }
                                        return false;
                                    },
                                }],
                          },

                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                       filter['u.uid'] = gro;
                    var status = $("#status").val();
                    if (status != '')
                       filter['c.status'] = status;
                    var bc = $("#bc").val();
                    if (bc != '')
                       filter['c.bc'] = bc;
                   var eid = $("#eid").val();
                    if (eid != '')
                       filter['c.entrust_id'] = eid;
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

/**
 * 点击搜索选中的值 需要有搜索框
 */
function clickSerach(selp,field){
    var value = $(selp).text();
    $('#'+field).val(value);
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    });
}

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

