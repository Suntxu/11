define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oldrecord/ownership/index',
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
                                        return '/admin/oldrecord/ownership/show?id='+res.id+'&year='+res.year;
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
                    show_url: 'oldrecord/ownership/show',
                }
            });
            var table = $("#show");
           
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
                    var year = $('#year').val();
                    if(year)
                        filter['group'] = year;

                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});


