define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'activity/welfare/orders/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'o.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'm.title', title:'套餐标题',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'o.id', title:'订单ID',},
                        {field: 'o.title', title:'订单标题',},
                        {field: 'uid', title:'用户',},
                        // {field: 'hz', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),},
                        {field: 'o.api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'o.start_time', title: '开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'o.end_time', title: '结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'o.discount_cost', title:'折扣价',operate:'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zdis';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return '总价格:'+total_sum;
                            }},
                        {field: 'o.cost', title:'成本价',operate:'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zcost';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return '总成本价:'+total_sum;
                            }},
                        {field: 'o.domain_total', title:'域名数量',operate:'BETWEEN',sortable:true},
                        {field: 'success_num', title:'注册成功数量',operate:'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'snum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }},
                        {field: 'reging_num', title:'注册中数量',operate:'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'rnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }},
                        {field: 'o.create_time', title: '购买时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        // {field: 'uip', title: '注册IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'o.status', title: '状态',searchList:{0:'正常',1:'已暂停',2:'已退款'}},
                        {field: 'group', title: '进度',visible:false,searchList:{1:'未完成',2:'已完成',3:'已结束'}},
                        {field: 'remark', title:'备注',operate:false},
                        {field: 'operate',width:170,title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '详情',
                                    text: '详情',
                                    title: '详情',
                                    classname: 'btn btn-xs btn-success btn-magic dialogit',
                                    url: function(res){
                                       return '/admin/vipmanage/recode/reglog/index?l_id='+res.id+'&a_type=3';
                                    },
                                },{
                                    name: '修改',
                                    text: '修改',
                                    title: '修改结束时间',
                                    classname: 'btn btn-xs btn-info',
                                    extend:function(res){
                                        return 'onclick="opEndtime('+res.id+')"';
                                    },
                                    visible:function(res){
                                        if(res.domain_total == (res.success_num + res.reging_num)){
                                            return false;
                                        }
                                        return true;
                                    },
                                },{
                                    name: '正常',
                                    text: '正常',
                                    title: '正常',
                                    classname: 'btn btn-xs btn-info btn-ajax',
                                    confirm:'是否确定开启该订单?',
                                    visible:function(res){
                                        var timestamp = (new Date()).getTime();
                                        if(res.op == 2 && res.end_time > (timestamp / 1000) ){
                                            return true;
                                        }
                                        return false;
                                    },
                                    success:function(res){
                                        setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },2000);
                                        
                                    },
                                    url: function(res){
                                       return '/admin/activity/welfare/orders/modiStatus?id='+res.id+'&status=1';
                                    },

                                },{
                                    name: '暂停',
                                    text: '暂停',
                                    title: '暂停',
                                    classname: 'btn btn-xs btn-warning',
                                    extend:function(res){
                                        return 'onclick="opOrder('+res.id+',2)"';
                                    },
                                    visible:function(res){
                                        var timestamp = (new Date()).getTime();
                                        if(res.op == 1 && res.end_time > (timestamp / 1000)){
                                            return true;
                                        }
                                        return false;
                                    },
                                },{
                                    name: '退款',
                                    text: '退款',
                                    title: '退款',
                                    classname: 'btn btn-xs btn-danger',
                                    extend:function(res){
                                        return 'onclick="opOrder('+res.id+',3)"';
                                    },
                                    visible:function(res){
                                        var timestamp = (new Date()).getTime();
                                        if((res.op == 1 || res.op == 2) && res.end_time > (timestamp / 1000)){
                                            return true;
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
                    var mid = $("#mid").val();
                    if (mid != '')
                        filter['m.id'] = mid;

                    var oid = $("#id").val();
                    if (oid != '')
                        filter['o.id'] = oid;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            $(window).resize(function() {
                table.bootstrapTable('resetView', {
                    height: $(window).height() - 100
                });
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});



//操作订单
function opOrder(id,status){
    if(status == 2){
        var msg = '暂停订单,请输入备注';
        var url = '/admin/activity/welfare/orders/modiStatus';
    }else{
        var msg = '订单退款,请输入备注';
        var url = '/admin/activity/welfare/orders/refund';
    }

    layer.prompt({title:msg,formType:2},function(text,index){
        layer.close(index);
        ajax(id,status,text,url);

    });

}

//修改结束时间
function opEndtime(id){

    layer.prompt({title:'请输入新的结束时间,格式<font color="red">yyyy-mm-dd hh:ii:ss</font>,例:2020-10-10 00:00:00',formType:3},function(text,index){
        layer.close(index);
        ajax(id,'',text,'/admin/activity/welfare/orders/modiEndtime');

    });


}

//公共处理方法
function ajax(id,status,remark,url){
    
    layer.load(0);
    $.post(url,{id:id,status:status,remark:remark},function(res){
        layer.closeAll('loading');
        layer.msg(res.msg);
        if(res.code == 1){
            setTimeout(function(){
                $('.btn-refresh').click();
            },1500);
        }

    },'json');

}




