define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/reserve/multop/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            $('#multire').on('click',function () {
                var temp = table.bootstrapTable('getSelections');
                if(temp.length > 500){
                    layer.alert('选中域名提交最大支持500个域名',{icon:2});
                    return false;
                }
                var ids = '';
                for(i in temp){
                    ids += ','+temp[i].pid;
                }
                $('#multire').attr('href','/admin/domain/reserve/multop/multire?status=1&ids='+ids.substr(1));
            });


            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pageList: [20, 50, 100,200],
                columns: [
                    [
                        { checkbox: true, title: '选择' },
                        { field: 'increase', title: '',operate:false},
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'reg_time', title: '注册时间',addclass:'datetimerange',operate:false,formatter: Table.api.formatter.datetime,},
                        { field: 'del_time', title: '删除时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,},
                        { field: 'regname', title: '注册商',searchList: $.getJSON('domain/reserve/multop/getZcs')},
                        { field: 'apiname', title: '接口商',operate:false},
                        { field: 'subtime', title: '提交时间',addclass:'datetimerange',operate:false,formatter: Table.api.formatter.datetime,},
                        { field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                    name:'提交',
                                    text: '提交',
                                    title:'提交',
                                    classname:'btn btn-xs btn-ajax btn-primary',
                                    icon:'fa fa-paypal',
                                    confirm:'确定要对该域名进行 【提交】 </font>操作吗?',
                                    url:function(res){
                                        return '/admin/domain/reserve/multop/modi?flag=1&id='+res.pid;
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }
                                    
                                },{
                                    name:'失败',
                                    text: '失败',
                                    title:'失败',
                                    classname:'btn btn-xs btn-danger btn-ajax',
                                    icon:'fa fa-paypal',
                                    confirm:'确定要对该域名进行 【预定失败】 操作吗?',
                                    url: function(res){
                                        return '/admin/domain/reserve/multop/modi?flag=4&id='+res.pid;
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }
                                }],
                        }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        execdomain: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/reserve/multop/execdomain',
                    table: 'user',
                }
            });
            var table = $("#table");
            $('#multire').on('click',function () {
                var temp = table.bootstrapTable('getSelections');
                if(temp.length > 500){
                    layer.alert('选中域名提交最大支持500个域名',{icon:2});
                    return false;
                }
                var ids = '';
                for(i in temp){
                    ids += ','+temp[i].pid;
                }
                $('#multire').attr('href','/admin/domain/reserve/multop/multire?status=2&ids='+ids.substr(1));
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pageList: [20, 50, 100,200],
                columns: [
                    [
                        { checkbox: true, title: '选择' },
                        { field: 'increase', title: '',operate:false},
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'del_time', title: '删除时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                        { field: 'regname', title: '注册商',searchList: $.getJSON('domain/reserve/multop/getZcs')},
                        { field: 'apiname', title: '接口商',operate:false},
                        { field: 'operate', title: __('Operate'),  
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                    name:'成功',
                                    text: '成功',
                                    title:'成功',
                                    classname:'btn btn-xs btn-warning btn-ajax',
                                    icon:'fa fa-deaf',
                                    confirm:'确定要对该域名进行 【预定成功】 操作吗?',
                                    url: function(res){
                                        return '/admin/domain/reserve/multop/modi?flag=2&id='+res.pid;
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }

                                },{
                                    name:'失败',
                                    text: '失败',
                                    title:'失败',
                                    classname:'btn btn-xs btn-danger btn-ajax',
                                    icon:'fa fa-paypal',
                                    confirm:'确定要对该域名进行 【预定失败】 操作吗?',
                                    url: function(res){
                                        return '/admin/domain/reserve/multop/modi?flag=3&id='+res.pid;
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }
                                }],
                        }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        multire: function () {
            Form.api.bindevent($("form[role=form]"));
        },

    };
    return Controller;
});

function getApp(self){
    
    var id = $(self).val();
    var zinfo = JSON.parse($('#zinfo').val());
    if(!zinfo[id]){
        $('#ref').html("<option value=''>请选择</option>");
        return true;
    }
    var option = '<option value="">选择</option>';
    for( i in zinfo[id]['api']){
        option += '<option value="'+i+'">'+zinfo[id]['api'][i]+'</option>';
    }
    $('#ref').html(option);

}
function temp(){
    layer.load(1);
    $.get('/admin/domain/reserve/multop/temp',{},function(res){
        layer.closeAll('loading');
        layer.msg(res.msg);
        setTimeout(function(){
            $('.btn-refresh').click();
        },1000);
    });
}