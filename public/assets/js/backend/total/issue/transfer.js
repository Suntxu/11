define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/issue/transfer/index',
                    add_url: 'total/issue/transfer/add',
                    del_url: 'total/issue/transfer/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                escape:false,
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'group', title: '用户',},
                        { field: 'create_time', title: '添加时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        { field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: '详情',
                                    text: '详情',
                                    title: '详情',
                                    classname: 'btn btn-xs add_info btn-primary  btn-dialog',
                                    url: function(data){
                                        return 'total/issue/transfer/show?uid='+data.group;
                                    },
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }
                                }
                            ],
                        }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
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
/* 自动触发show.html第一个标签 */
$(function(){
    $('#clickpz').click();
});


