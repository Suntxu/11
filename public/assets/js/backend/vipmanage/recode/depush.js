define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/depush/index',
                    table: 'user',
                }
            });

            var table = $("#table");
              //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $.each(json,function(i,n){
                     var tit = n['r.tit'].replace(/,$/,'');
                     tit = tit.split(',');
                     var html = '<table class="layui-table" style="width:200px;margin:10px 5px;" lay-size="sm">';
                     for(var i=0; i<tit.length;i++){
                        html += '<tr><td style="padding-left:8%;">'+tit[i]+'</td><tr>';
                     }
                     html+='</table>';
                    $('#show'+n.id).on('click',function(){
                          layer.open({
                          type: 1,
                          title: 'push域名列表',
                          width:200,
                          closeBtn: 0,
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
                sortName:'r.pushtime',
                escape: false, //转义空格
                pageSize:25,
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'r.id', title: 'PUSHID',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'tit', title: '域名',operate:'FIND_IN_SET'},
                        {field: 'domainLen',title: '域名个数',operate:false},    
                        {field: 'u.uid', title: '发起者',},
                        {field: 'u1.uid', title: '目标账户'},
                        {field: 'r.money', title: '交易金额',operate: 'BETWEEN',footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                            
                        {field: 'r.status', title: 'push状态',formatter: Table.api.formatter.status,searchList: {0:'push中',1:'对方已拒绝',2:'对方已接收push',3:'已撤销'}},
                        // {field: 'r.type', title: 'push类型',formatter: Table.api.formatter.status,notit:true,searchList: {0:'普通',1:'回收'}},

                        {field: 'pushtime', title: '发起时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'jytime', title: '交易时间',operate: 'INT', addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        {field: 'r.remark', title: '备注',operate: false,},
                    ]
                ],
                
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                       filter['u.uid'] = gro;
                    var id = $("#id").val();
                    if (id != '')
                       filter['r.id'] = id;
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

