define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/record/suffix/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            table.on('post-body.bs.table', function (e, json) {
                $.each(json,function(i,n){
                    var html = '<table class="layui-table" style="width:200px;height:125px;margin:10px 5px 10px 20px;" lay-size="sm"><tr><th>数量</th><th>价格</th><tr>';
                    html += '<tr><td>'+n.num+'</td><td>'+n.money+'</td></tr>';
                    html += '<tr><td>'+n.num1+'</td><td>'+n.money1+'</td></tr>';
                    html += '<tr><td>'+n.num2+'</td><td>'+n.money2+'</td></tr>';
                    html += '<tr style="margin-bottom:10px;"><td colspan=2>限制优惠总数量：'+n.discounts+'</td></tr>';                         
                    html+='</table>';
                    $('#show'+n.id).on('click',function(){
                          layer.open({
                          type: 1,
                          title: '优惠数量',
                          width:200,
                          shadeClose: true,
                          skin: 'yourclass',
                          content: html,
                        });
                    });
                });
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'h.create_time',
                orderName:'desc',
                escape:false,
                columns: [
                    [
                        {field: 'name1', title:'后缀',searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        {field: 'xh', title: '序号',operate: false,sortable:true,},
                        {field: 'zt',title:'状态',formatter:Table.api.formatter.status,searchList:{1:'开启',2:'关闭'}},
                        {field: 'ysje', title:'注册原始价格',operate: false,sortable:true},
                        {field: 'money', title: '注册优惠价格',operate: false,sortable:true,},
                        {field: 'yhsj1', title: '注册优惠时间1',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'yhsj2', title: '注册优惠时间2',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'discount', title: '优惠数量',operate: false,},
                        {field: 'xfmoney', title: '续费原始价格',operate: false,sortable:true,},
                        {field: 'xfxfyg', title: '续费优惠价格',operate: false,sortable:true,},
                        {field: 'xfsj1', title: '续费优惠时间1',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'xfsj2', title: '续费优惠时间2',operate: false,formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'regbrokerage', title: '注册返佣比例',operate: false,sortable:true},
                        {field: 'tit', title: '注册接口',operate: false,},
                        {field: 'cost', title:'成本价格',operate: false,sortable:true},
                        {field: 'res_pirce', title:'预定价格',operate: false,sortable:true,},
                        {field: 'recycle_price', title:'回收价格',operate: false,sortable:true,},
                        {field: 'h.create_time', title: '修改时间',addclass: 'datetimerange',operate: 'INT',sortable:true,formatter: Table.api.formatter.datetime},
                        {field: 'nickname', title: '操作者',},
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});
