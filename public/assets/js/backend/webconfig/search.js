define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/search/index',
                    add_url: 'webconfig/search/add',
                    edit_url: 'webconfig/search/edit',
                    del_url: 'webconfig/search/del',
                    multi_url: 'webconfig/search/multi_url',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
              table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'create_time',
                orderName:'desc',
                search:false,//隐藏搜索框
                escape: false, //转义空格
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'name', title:'名称',},
                        {field: 'condition', title: '条件',operate: false,sortable:false,},
                        // {field: 'djl', title:'点击量',operate: false,sortable:true,},
                        {field: 'seotit', title:'SEO标题',},
                        {field: 'type', title:'类型',formatter: Table.api.formatter.status,searchList: {0:'一口价页面',1:'快捷搜索(分类)'},},
                        {field: 'create_time', title: '创建时间',operate:'RANGE',sortable:true,addClass:'datetimerange',formatter: Table.api.formatter.datetime},
                        {field: 'status', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'已启用','2':'已禁用'},},
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
function hids(){

    var type = $('input[name="type"]:checked').val();
    if(type == 1){
        $('.condition_type1').show();
        $('.condition_type0').hide();
        $('.condition').val(''); //清空数据
    }else{
        $('.condition_type0').show();
    }
}
