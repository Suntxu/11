define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/news/help/index',
                    add_url: '/admin/news/help/add',
                    edit_url: '/admin/news/help/edit',
                    del_url: '/admin/news/help/del',
                    // multi_url: 'news/Msg/notice',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'h.sj desc, orderby asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'tit', title:'帮助信息',align:'left',operate: 'LIKE',sortable:true,formatter:Table.api.formatter.alink,url:'/admin/news/help/edit',fieldvaleu:'id',fieldname:'ids',tit:'编辑'},
                        {field: 'h.zt', title: '审核',formatter: Table.api.formatter.status, searchList:{1:'通过审核',2:'正在审核',3:'审核被拒'}},
                        {field: 'djl', title: '关注',operate: false,},
                        {field: 'h.sj',title: '最后更新',addclass:'datetimerange',operate:'RANGE', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'ty1id', title: '所属分类',searchList: $.getJSON('webconfig/helptype/getTypeList')},
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
                                        return row.alink+'help/index/details?hid='+row.id;
                                    }
                                }],
                          },
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


