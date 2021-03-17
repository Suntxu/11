define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/suffix/index',
                    add_url: 'webconfig/suffix/add',
                    edit_url: 'webconfig/suffix/edit',
                    del_url: 'webconfig/suffix/del',
                    multi_url: 'webconfig/suffix/multi_url',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'xh',
                orderName:'desc',
                commonSearch:false, //隐藏搜索
                search:false,//隐藏搜索框
                columns: [
                    [
                        {checkbox: true},
                        {field: 'name1', title:'后缀',operate: false,formatter:Table.api.formatter.alink,url:'/admin/webconfig/suffix/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        {field: 'xh', title: '序号',operate: false,sortable:true,},
                        {field: 'ysje', title:'注册原始价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'ysje',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'money', title: '注册优惠价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'money',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'yhsj1', title: '注册优惠时间1',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'yhsj2', title: '注册优惠时间2',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'discounts', title: '优惠总数量',sortable:true, operate: false,},
                        {field: 'snum', title: '剩余优惠数量',operate: false,},
                        {field: 'xfmoney', title: '续费原始价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'xfmoney',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'xfxfyg', title: '续费优惠价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'xfxfyg',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'xfsj1', title: '续费优惠时间1',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'xfsj2', title: '续费优惠时间2',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'regbrokerage', title: '注册返佣比例',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'regbrokerage',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'tit', title: '注册接口',operate: false,},
                        {field: 'cost', title:'成本价格',operate: false,sortable:true},
                        // {field: 'res_pirce', title:'预定价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'res_pirce',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'sj', title: '编辑时间',operate: false,sortable:true,},
                        // {field: 'aregister', title:'注册接口',operate: false,formatter:Table.api.formatter.alink,url:'/admin/webconfig/suffixreg/index',fieldvaleu:'name1',fieldname:'hz',tit:'添加注册商'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
$(function(){
    $('#clickpz').click();
    $("#pl_money").bind("input propertychange",function(event){
        $(".pl_money2").val($(this).val());
    });
    $(".pl_status").bind("click",function(event){
        console.log($(this).val());
        if($(this).val() == 1){
            $(".pl_status_0").removeAttr("checked");
            $(".pl_status_1").prop("checked",true);
        }else if($(this).val() == 0){
            $(".pl_status_1").removeAttr("checked");
            $(".pl_status_0").prop("checked",true);
        }

    });
});
//ajax 点击修改价格
function updateMoeny(self){
   var oldhtml = self.value;
   var id = self.id;
   var field = self.title;
    $.ajax({
        'type':'post',
        'data':{'id':id,field:field,val:oldhtml},
        'url':'/admin/webconfig/suffix/updateMoney',
        success:function(data){
            layer.closeAll('loading');
            self.value = data;
        },
        error:function(){
            alert('发送失败');
        },
        beforeSend:function(){
            layer.load();
        }
    });
}

$('.xianshi').click(function(){
    $('.xianshi').removeClass('active');
    $('.xianshi').each(function(i,n){
        $($(n).attr('href')).css('display','none');
    });
    id = $(this).attr('href');
    $(id).css('display','');
    $(this).addClass('active');
});
/**
 * 回收设置 禁止状态
 */
function statusModi(self){
    if(self.value == 1){
        $('.hs_set').attr('readonly',true);        
    }else{
        $('.hs_set').attr('readonly',false);
    }

}