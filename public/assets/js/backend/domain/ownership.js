define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/ownership/index',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'r.id', title: '任务ID',},
                        {field: 'uid', title: '用户名',},
                        {field: 'group', title: '任务状态',formatter: Table.api.formatter.status,searchList: {0:'执行中',1:'执行完成'}},
                        {field: 'r.createtime', title: '开始时间',operate: 'INT', addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'temp',operate:false,title: '域名模板',formatter:Table.api.formatter.alink,url:'/admin/domain/ownership/reinfo',fieldvaleu:'remark',fieldname:'id',tit:'域名模板信息',},
                        {field: 'total', title: '未完成数量/总数量',operate:false,footerFormatter: function (data) {
                                var field = 'num';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }},
                        {field: 'uip', title: '用户操作IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '详情',
                                    text: '详情',
                                    title: '详情',
                                    classname: 'btn btn-xs btn-success btn-magic dialogit',
                                    icon: 'fa fa-magic',
                                    url: function(res){
                                        return '/admin/domain/ownership/show?id='+res.id;
                                    },
                                },{

                                    name:'待处理',
                                    text: '待处理',
                                    title:'待处理',
                                    classname:'btn btn-xs btn-ajax btn-primary',
                                    icon:'fa fa-paypal',
                                    url:function(res){
                                        return '/admin/domain/ownership/modiStatus?id='+res.id;
                                    },
                                    visible:function(res){
                                        if(res.status == 0 && (typeof  res['r.id'] == 'number') ){
                                            return true;
                                        }
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
                                    name:'成功',
                                    text: '成功',
                                    title:'成功',
                                    classname:'btn btn-xs btn-warning btn-ajax',
                                    icon:'fa fa-deaf',
                                    confirm:'确定要进行此操作吗?',
                                    url: function(res){
                                        return '/admin/domain/ownership/edit/ids/'+res.id;
                                    },
                                    visible:function(res){
                                        if(res.status == 0){
                                            return true;
                                        }
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
                                    classname:'btn btn-xs btn-danger',
                                    icon:'fa fa-paypal',
                                    visible:function(res){
                                        if(res.status == 0){
                                            return true;
                                        }
                                    },
                                    extend:function(res){
                                        return 'onclick="audit(\''+res.id+'\')"';
                                    },
                                }],
                          },
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        show:function(){

           // 初始化表格参数配置
            Table.api.init({
                extend: {
                    show_url: 'domain/ownership/show',
                }
            });
            var table = $("#show");

            $('#multire').on('click',function () {
                var temp = table.bootstrapTable('getSelections');
                var ids = '';
                for(i in temp){
                    ids += ','+temp[i].id;
                }
                var taskid = $('#ids').val();
                $('#multire').attr('href','/admin/domain/ownership/single?taskid='+taskid+'&ids='+ids.substr(1))+'&';
            });
            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function (i,n) {
                    var stat = $(n).children().eq(2).text();
                    stat = stat.trim();
                    if(stat != '执行中'){
                        $("input[type=checkbox]",this).prop("disabled", true);
                    }
                });
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.show_url,
                pk: 'id',
                sortName:'TaskStatusCode',
                orderName:'asc',
                escape: false, //转义空格
                columns: [
                    [
                        {checkbox : true},
                        {field: 'tit', title: '域名',operate:'TEXT'},
                        {field: 'TaskStatusCode', title: '任务状态',formatter: Table.api.formatter.status,searchList: {0:'执行中',2:'执行成功',3:'执行失败'}},
                        {field: 'ErrorMsg', title: '错误信息',operate:false},
                        {field: 'CreateTime', title: '完成时间',operate:'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true,},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var ids = $("#ids").val();
                    if (ids != '')
                        filter['taskid'] = ids;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            // 导出功能
            $('#dc').click(function(){
                location.href = '/admin/domain/ownership/download?tid='+$('#ids').val();
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        single:function(){
            Form.api.bindevent($("form[role=form]"));
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

function audit(id){
    layer.prompt({title: '请输入错误备注', formType: 2}, function(text, index){
        layer.close(index);
        layer.load();
        $.post('/admin/domain/ownership/edit/ids/'+id,{remark:text},function(res){
            layer.closeAll('loading');
            layer.msg(res.msg);
            if(res.code == 1){
                window.setTimeout(function(){
                    $('.btn-refresh').click();
                },1500);
            }
        },'json');
    });
}

function hiden(self){
    zva = $(self).val();
    if(zva == 1){
        $('#remark').css('display','none');
        $('input[name="row[remark]"]').val('');
    }else{
        $('#remark').css('display','block');
    }

}