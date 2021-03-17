define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'spread/elchee/material/index',
                    add_url: 'spread/elchee/material/add',
                    edit_url: 'spread/elchee/material/edit',
                    // del_url: 'spread/elchee/material/del', 
                    multi_url: 'spread/elchee/material',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'orderby desc,createtime desc',
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'title', title: '标题',align:'left',formatter:Table.api.formatter.alink,url:'/admin/spread/elchee/materiallog',fieldvaleu:'id',fieldname:'mid',tit:'访问统计',},
                        { field: 'link', title: '链接地址', },
                        { field: 'imgpath',title: '图片',formatter: Table.api.formatter.image,operate:false},
                        { field: 'type', title: '类型', formatter: Table.api.formatter.status,searchList: {0:'普通',1:'专属'}},
                        { field: 'status', title: '状态', formatter: Table.api.formatter.status,searchList: {0:'已启用',1:'已禁用'}},
                        { field: 'createtime', title: '创建时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'orderby', title: '排序号',operate:false,sortable:true },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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