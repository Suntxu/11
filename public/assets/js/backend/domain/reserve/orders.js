define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/reserve/orders/index',
                    // edit_url:'domain/reserve/domainreserve/edit',
                    table: 'user',
                }
            });
            var table = $("#table");
            // $('#multire').on('click',function () {
            //     var temp = table.bootstrapTable('getSelections');
            //     var ids = '';
            //     for(i in temp){
            //         ids += ','+temp[i].id;
            //     }
            //     $('#multire').attr('href','/admin/domain/reserve/domainreserve/multire?id='+ids.substr(1));
            // });
            // //在表格内容渲染完成后回调的事件
            // table.on('post-body.bs.table', function (e, json) {
            //     $("tbody tr[data-index]", this).each(function (i,n) {
            //         var stat = $(n).children().eq(7).text();
            //         stat = stat.trim();
            //         if(stat != '进行中' && stat != '已提交'){
            //             $("input[type=checkbox]",this).prop("disabled", true);
            //         }
            //     });
            // });
            // 初始化表格

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.time',
                orderName: 'desc',
                escape: false, //转义空格
                columns: [
                    [
                        // { checkbox: true},
                        { field: 'r.tit', title: '域名',operate:'TEXT',formatter:Table.api.formatter.alink,url:'/admin/domain/reserve/auctionlog/index/',fieldvaleu:'aid',fieldname:'id',tit:'竞拍记录',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'group', title: '后缀',searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'i.del_time', title: '域名删除时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,sortable:true,
                            footerFormatter: function (data) {
                                var field = 'bidTotal';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '得标:'+total_sum;
                            }},
                        { field: 'u.uid', title: '用户名',
                            footerFormatter: function (data) {
                                var field = 'ple';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '参与用户: '+total_sum.toFixed(0)+' 人';
                            }
                        },
                        { field: 'special_condition', title: '排除用户',operate:'TEXT',visible:false},
                        { field: 'r.money', title: '冻结金额',sortable:true,operate:'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'pay';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '冻结金额:'+total_sum.toFixed(2);
                            }},
                        { field: 'r.fmoney', title: '实付金额',sortable:true,operate:false,
                            footerFormatter: function (data) {
                                var field = 'spay';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '实付总金额:'+total_sum.toFixed(2);
                            }},
                        { field: 'r.status', title: '订单状态',formatter: Table.api.formatter.status,searchList: {'0':'进行中','1':'已预定','2':'竞拍中','3':'预定失败','7':'得标','8':'未得标','10':'外部得标'},notit:true,
                            footerFormatter: function (data) {
                                var field = 'elcheeTotal';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '怀米大使数量:'+total_sum;
                            }},
                        { field: 'r.into', title: '订单类型',formatter: Table.api.formatter.status,searchList: {'0':'正常订单','1':'闯入订单'},notit:true,footerFormatter: function (data) {
                                var field = 'noElchTotal';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '非怀米大使订单数量:'+total_sum;
                            }},
                        { field: 'r.pstatus', title: '交割状态',formatter: Table.api.formatter.status,searchList: {'0':'未支付','1':'未交割','2':'交割失败','3':'已交割','4':'违约'},notit:true},
                        { field: 'r.time', title: '订单创建时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,sortable:true },
                        { field: 'i.dtype', title: '域名类型',operate: 'RLIKE',searchList:$.getJSON('domain/manage/getDomainType'),
                            footerFormatter: function (data) {
                                var field = 'nopay';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '未支付余款:'+total_sum.toFixed(2);
                            }},
                        { field: 'r.channel', title:'预定通道',sortable:true,searchList:{1:'1号',2:'2号',3:'3号',4:'4号',5:'5号',6:'6号',7:'7号',8:'8号',9:'9号',10:'10号',11:'11号',12:'12号',13:'13号',14:'14号',15:'15号',98:'98号',99:'99号'}},
                        { field: 'r.api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        { field: 'nowpay', title:'未支付余款',operate:false,},
                        { field: 'r.yj', title:'佣金',operate:'BETWEEN',sortable:true,},
                        { field: 'ut.uid', title:'怀米大使'},
                        { field: 'uip', title: 'IP地址',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        // multire:function(){
        //     Form.api.bindevent($("form[role=form]"), function(data, ret){},function(data,ret){
        //         if(ret.code == 300){
        //             $('.auction').css('display','');
        //         }
        //     });
        // },
        
        edit: function () {
            Form.api.bindevent($("form[role=form]"), function(data, ret){},function(data,ret){
                if(ret.code == 300){
                    $('.auction').css('display','');
                }
            });
        },
    };
    return Controller;
});
