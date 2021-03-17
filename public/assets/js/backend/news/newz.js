define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/news/newz/index',
                    add_url: '/admin/news/newz/add',
                    edit_url: '/admin/news/newz/edit',
                    del_url: '/admin/news/newz/del',
                    // multi_url: 'news/Msg/notice',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'n.sj',
                orderName:'desc',
                // escape: false,
                showJumpto: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'tit', title:'资讯信息',align:'left',operate: 'LIKE',sortable:true,formatter:Table.api.formatter.alink,url:'/admin/news/newz/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        // {field: 'tit', title:'资讯信息',align:'left',operate: 'LIKE',},
                        {field: 'n.zt', title: '审核',formatter: Table.api.formatter.status, searchList:{1:'通过审核',2:'正在审核',3:'审核被拒'}},
                        {field: 'djl', title: '访问量',operate: false,sortable:true},
                        {field: 'type1id', title: '所属分类',searchList: $.getJSON('webconfig/newtype/getTypeList')},
                        // {field: 'group', title: '标签',searchList: $.getJSON('news/Nlabel/getLabelList')},
                        {field: 'n.sj',title: '添加时间',addclass:'datetimerange',operate:'INT', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                name: '预览',
                                text: '预览',
                                title: '预览',
                                extend:'target="_blank"',
                                classname: 'btn btn-xs',
                                icon: 'fa fa-magic',
                                url: function(row){
                                    return row.alink+'help/news/detail?id='+row.id;
                                }
                            }],
                        },
                    ]
                ],
                // queryParams: function (params) {
                //     var filter = JSON.parse(params.filter);
                //     var op = JSON.parse(params.op);
                //     //这里可以追加搜索条件
                //     var lid = $("#lid").val();
                //     if (lid != '')
                //         filter['group'] = lid;
                //     params.filter = JSON.stringify(filter);
                //     params.op = JSON.stringify(op);
                //     return params;
                // }
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


