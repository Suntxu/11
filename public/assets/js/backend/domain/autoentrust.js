define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/autoentrust/index',
                    table: 'user',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                escape: false, //转义空格
                sortName:'a.id',
                orderName:'desc',
                columns: [
                    [
                        { field: 'title', title: '标题',operate:'LIKE'},
                        { field: 'uid', title: '用户',},
                        { field: 'money', title: '单价/均价',operate:false,width:120},
                        { field: 'count', title: '已购买/总数量',operate:false},
                        { field: 'show', title: '订单详情',operate:false,formatter:Table.api.formatter.alink,url:'/admin/vipmanage/recode/deallog',fieldvaleu:'id',fieldname:'eid',tit:'订单详情',},
                        { field: 'ztotal', title: '总扣除/剩余/还原保证金',operate:false},
                        { field: 'sale_type', title: '在售类型',formatter: Table.api.formatter.status,searchList: {0:'一口价域名',1:'打包一口价域名'},},
                        { field: 'suffix', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},

                        { field: 'a.expire', title: '域名到期时间',formatter: Table.api.formatter.status,searchList: {0:'不限',1:'1~3个月',2:'3~6个月',3:'6~12个月',4:'12个月以上'},},
                        { field: 'a.status', title: '状态',formatter: Table.api.formatter.status,searchList: {0:'进行中',1:'已完成',2:'已取消',3:'已过期'},notit:true},
                        { field: 'create_time', title: '创建时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                        { field: 'end_time', title: '结束时间',addclass:'datetimerange',operate:'INT',},
                        { field: 'finish_time', title: '完成时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                        { field: 'uip', title: 'IP地址',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ]
                ],
                
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#eid").val();
                    if (id != '')
                        filter['a.id'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});


