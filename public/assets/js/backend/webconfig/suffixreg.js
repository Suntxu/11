define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/suffixreg/index',
                    add_url: 'webconfig/suffixreg/add',
                    edit_url: 'webconfig/suffixreg/edit',
                    del_url: 'webconfig/suffixreg/del',
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
                escape:false,
                search:false,//隐藏搜索框
                columns: [
                    [
                        {field: 'name', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        {field: 'zcs', title: '注册商',operate: false,},
                        {field: 'aid', title: '接口商',operate: false,},
                        {field: 'cost', title:'成本价格',operate: 'BETWEEN',sortable:true},
                        {field: 'ysje', title:'注册原始价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'ysje',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'money', title: '注册优惠价格',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'money',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'yhsj1', title: '注册优惠开始时间',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'yhsj2', title: '注册优惠结束时间',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'discounts', title: '优惠总数量',sortable:true, operate: false,},
                        {field: 'snum', title: '剩余优惠数量',operate: false,},
                        {field: 'regbrokerage', title: '注册返佣比例',operate: false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'regbrokerage',hz:true,affair:'onblur="updateMoeny(this)"',},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
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

//获取注册商
function getZcs(self){
    var hz = $(self).val();
    var html = '<option value="">请选择</option>';
    $.ajax({
        url:'webconfig/suffixreg/getZcsOption',
        type:'post',
        data:{hz:hz},
        dataType:'json',
        success:function(data){
            if(data.code ==1){
                html += '<option value="">'+data.msg+'</option>';
            }else{
                $.each(data.res,function(i,n){
                    html += '<option value="'+i+'">'+n+'</option>';
                });
            }
            $('#zcs').html(html);
        }
    });

}
//获取api
function getApi(self){
    var id = $(self).val();
    var html = '<option value="">请选择</option>';
    $.ajax({
        url:'domain/store/save/getApi',
        type:'post',
        data:{id:id},
        dataType:'json',
        success:function(data){

            if(data.code ==1){
                html += '<option value="">'+data.msg+'</option>';
            }else{
                $.each(data.res,function(i,n){
                    html += '<option value="'+n.id+'">'+n.tit+'</option>';
                });
                $('#ref').html(html);
            }
        }
    });

}

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