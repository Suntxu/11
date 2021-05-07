define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/dinndan/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'r.sj desc,r.id desc',
                pageSize:25,
                escape: false, //转义空格
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        { field: 'uid', title: '用户名',operate:'TEXT',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/setuser/index',fieldvaleu:'userid',fieldname:'id',tit:'用户设置',},
                        { field: 'r.sj', title: '交易时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',defaultValue:getTimeFrame()},
                        { field: 'money', title: '交易金额',sortable:true,operate: 'BETWEEN',},
                        { field: 'product', title: '交易类型',sortable:true, addClass:'blogroll',formatter: Table.api.formatter.status,searchList: {'0':'域名','2':'充值','3':'手续费','4':'提现','5':'佣金提现','6':'违约金','7':'返利','8':'退款','1':'其他'},},
                        { field: 'subtype', title: '交易子类型',sortable:true,addClass:'blogroll_child',formatter: Table.api.formatter.status,searchList: {}},
                        { field: 'balance', title: '所剩余额',operate:false,},
                        { field: 'info', title: '包含信息',},
                        { field: 'r.uip', title: 'IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'r.uip',fieldname:'wd',tit:'Ip归属地查询',},
                        { field: 'showurl', title: '链接',operate: false},
                    ]
                ],
                 queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter['u.uid'] = gro;
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
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

/**
 * 二级联动搜索
*/
$(document).on("change", ".blogroll", function () {
    child = {
                0:{0:'一口价域名交易',1:'push域名',2:'域名注册',11:'域名续费',13:'域名预定',16:'域名赎回',18:'域名回收',20:'域名手动续费',21:'注册包购买'},
                1:{3:'转回原注册商',6:'发票申请',7:'实名认证',9:'其他',12:'消保店铺保证金'},
                2:{4:'用户充值',5:'后台充值'},
                3:{0:'一口价域名交易',},
                4:{8:'现金提现'},
                5:{10:'怀米大使'},
                6:{13:'域名预定',14:'域名拼团'},
                7:{15:'域名竞价'},
                8:{17:'注册域名退款'},
            };
    var aa =  child[$(this).val()];
    var option = '<option value="">选择</option>';
    for( i in aa){
        option += '<option value="'+i+'">'+aa[i]+'</option>';
    }
    $('.blogroll_child').first().html(option);
});
