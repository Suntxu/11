define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/orderfx/msg/index',
                    add_url: '/admin/orderfx/msg/add',
                    // edit_url: '/admin/orderfx/msg/edit',
                    del_url: '/admin/orderfx/msg/del',
                    multi_url: 'orderfx/Msg/multi_url',
                    table: 'user',
                }
            });

            var table = $("#table");
            
           
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'create_time',
                columns: [
                    [
                     // visible: false,
                        {checkbox: true},
                        {field: 'tit', title:'消息标题',operate: 'LIKE',formatter:Table.api.formatter.alink,url:'/admin/orderfx/msg/show',fieldvaleu:'id',fieldname:'ids',tit:'消息详情'},
                        {field: 'type', title: '消息类型',searchList: $.getJSON('category/getcategory?type=sms&xz=parent')},
                        {field: 'create_time', title: '发布时间',addclass:'datetimerange',operate:'RANGE', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'all', title: '发送类型',sortable:true, formatter: Table.api.formatter.status,searchList:{0:'部分用户',1:'全部用户'} },
                        {field: 'yd', title: '已读',operate: false,formatter:Table.api.formatter.alinks,url:'/admin/orderfx/msglist/index',fieldvaleu:['1','id'],fieldname:['status','id'],tit:'已读人员列表',},
                        {field: 'wd', title: '未读',operate: false,formatter:Table.api.formatter.alinks,url:'/admin/orderfx/msglist/index',fieldvaleu:['0','id'],fieldname:['status','id'],tit:'未读人员列表',},
                        {field: 'zrs', title: '总人数',operate: false,formatter:Table.api.formatter.alink,url:'/admin/orderfx/msglist/index',fieldvaleu:'id',fieldname:'id',tit:'人员列表',},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate },
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

function xz(stat){
    if(stat == 0){
        document.getElementById('list').removeAttribute('disabled');
    }else{
        document.getElementById('list').setAttribute('disabled','true');
    }
}
