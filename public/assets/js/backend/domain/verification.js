define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/verification/index',
                    add_url: 'domain/verification/add',
                    table: 'attachment'
                }
            });
            var table = $("#table");
            table.on('post-body.bs.table', function (e, json) {
                $.each(json,function(i,n){
                    $('#remark'+n.id).on('click',function(){
                          layer.alert(n.vremakr,{title:'备注',widht:'50%'});
                    });
                });
            });
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'create_time',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [ 
                        {field: 'tit',title: '域名',operate:'TEXT'},
                        {field: 'zcs',title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent')},
                        {field: 'gtype', title:'违规类型', formatter: Table.api.formatter.status,searchList: {0:"自查",1:"用户举报"},},
                        {field: 'otype',  title:'处罚动作', formatter: Table.api.formatter.status,searchList: {0:"警告整改",1:"申请hold"},},
                        {field: 'remark', title:'处罚原因',operate:'LIKE',align:'left'},
                        {field: 'create_time',title: '处罚时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'status',  title:'状态', formatter: Table.api.formatter.status,searchList: {0:"处罚中",1:"已解除"},notit:true},
                        {field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name:'解除',
                                    text: '解除',
                                    title:'解除',
                                    classname:'btn btn-xs btn-success btn-ajax',
                                    icon:'fa fa-deaf',
                                    confirm:'确定要进行此操作吗?',
                                    url: function(res){
                                        return '/admin/domain/verification/modi?id='+res.id;
                                    },
                                    visible:function(res){
                                        if(res.op == 0){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        $('.btn-refresh').click();
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }
                                }],
                          },

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
            },
        }

    };
    return Controller;
});