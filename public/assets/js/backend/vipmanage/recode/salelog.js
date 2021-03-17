define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/salelog/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            table.on('post-body.bs.table', function (e, json) {
                $.each(json,function(i,n){
                    $('#remark'+n.cid).on('click',function(){
                          layer.alert(n.txt,{title:'简介',widht:'50%'});
                    });
                });
            });
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'c.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'batch', title: '订单批次',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';
                            }},
                        { field: 'tit', title: '域名',operate:'TEXT',
                            formatter:Table.api.formatter.alink,flag:'text',align:'left',fwhere:[2,1,1,2,1,2,'0|>',1],finame:['istj','is_sift','wx_check','wx_check','qq_check','qq_check','special'],ys:['red','orange'],
                            pdtxt:['(推荐)','(精选)','/assets/img/domain/domain_wx_check.png','/assets/img/domain/domain_wx_check_no.png','/assets/img/domain/domain_qq_check.png','/assets/img/domain/domain_qq_check_no.png','/assets/img/domain/sale_domain.png'],
                            st:['font','font','img','img','img','img','img'],
                        },
                        {field: 'vtxt', title: '简介',operate:false},
                        {field: 'u.uid', title: '买家账号',},
                        {field: 'money', title: '交易金额',operate: 'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                            
                        {field: 's.uid', title: '卖家账号'},
                        {field: 'c.type', title: '订单类型',formatter: Table.api.formatter.status,searchList: {'0':'正常订单','1':'满减订单','2':'微信活动订单','9':'打包域名订单'}},
                        {field: 'is_sift', visible:false, title: '是否为精选订单',formatter: Table.api.formatter.status,searchList: {'0':'否','1':'是'}},
                        {field: 'wx_check', visible:false, title: '微信拦截',formatter: Table.api.formatter.status,searchList: {'0':'未知','1':'未拦截','2':'已拦截'}},
                        {field: 'qq_check', visible:false, title: 'QQ拦截',formatter: Table.api.formatter.status,searchList: {'0':'未知','1':'未拦截','2':'已拦截'}},
                        {field: 'icpholder', title: '备案商',formatter: Table.api.formatter.status,searchList: {'0':'未知','1':'阿里云','2':'腾讯云','3':'其他','4':'所有'}},
                        {field: 'icptrue', title: '备案类型',formatter: Table.api.formatter.status,searchList: {'0':'未知','1':'个人','2':'企业','3':'未备案','4':'存在'}},
                        {field: 'c.special', visible:false, title: '是否特价',formatter: Table.api.formatter.status,searchList: {'0':'未设置','1':'是','2':'否'}},
                        {field: 'stype', title: '域名类型',formatter: Table.api.formatter.status,searchList: {'0':'未设置','1':'老域名','2':'高收录','3':'高权重','4':'高PR','5':'高外链','6':'高反链'}},
                        {field: 'istj', visible:false, title: '是否推荐',formatter: Table.api.formatter.status,searchList: {'0':'默认','2':'推荐'}},
                        {field: 'attc', title: '特殊属性',formatter: Table.api.formatter.status,searchList: {'0':'未设置','1':'二级不死','2':'大站','3':'绿标'}},
                        {field: 'zcs', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        {field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'c.create_time', title: '支付时间',operate: 'INT', addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime },
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
//点击搜索选中的值 需要有搜索框
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
    $.post('/admin/vipmanage/recode/salelog/show',{id:id},function(res){
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