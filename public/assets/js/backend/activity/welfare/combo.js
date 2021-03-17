define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'activity/welfare/combo/index',
                    add_url: 'activity/welfare/combo/add',
                    edit_url: 'activity/welfare/combo/edit',
                    // del_url: 'activity/welfare/combo/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'m.sort',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'w.title', title:'福利标题',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'm.title', title:'套餐标题',},
                        {field: 'm.suffix', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker'},
                        {field: 'domain_total', title:'域名数量(个)',operate:'BETWEEN',sortable:true},
                        {field: 'original_cost', title:'注册原价格',operate:'BETWEEN',sortable:true},
                        {field: 'discount_cost', title:'折扣价',operate:'BETWEEN',sortable:true},
                        {field: 'pack_total', title:'可购买包数量',operate:'BETWEEN',sortable:true},
                        {field: 'surplus_total', title:'包剩余数量',operate:'BETWEEN',sortable:true},
                        {field: 'snum', title:'已注册成功数量',operate:'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zsnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                        },formatter:Table.api.formatter.alink,url:'/admin/activity/welfare/orders',fieldvaleu:'id',fieldname:'mid',tit:'订单列表',},
                        {field: 'regnum', title:'注册中数量',operate:'BETWEEN',sortable:true,footerFormatter: function (data) {
                                var field = 'zregnum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                        }},
                        {field: 'start_time', title: '注册开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: '注册结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'm.status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'开启',1:'关闭'},custom:{'开启':'success','关闭':'danger'},},
                        {field: 'create_time', title: '创建时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        {field: 'm.sort', title: '序号',operate:false,sortable:true},

                        {field: 'Operate', title: __('Operate'),operate:false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
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
    //自动选择后缀、时间
    $('#welfare').on('change',function(){
        if(this.value){
            $('#hz').val($('#welfare option:selected').data('suffix'));
            $('#value2222').val($('#welfare option:selected').data('estart'));
            $('#value2223').val($('#welfare option:selected').data('eend'));
        }else{
            $('#hz').val('');
            $('#value2222').val('');
            $('#value2223').val('');
        }

    });

});