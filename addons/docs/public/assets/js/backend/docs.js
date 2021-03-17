define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'docs/index',
                    add_url: 'docs/add?rel=1',
                    edit_url: 'docs/edit?rel=1',
                    del_url: 'docs/del?rel=1',
                    multi_url: '',
                    table: 'docs',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'relative',
                sortName: 'order',
                sortOrder: 'asc',
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'name', title: __('Name')},
                        {field: 'title', title: __('Title')},
                        {field: 'type', title: __('Type')},
                        {field: 'relative', title: __('Relative'), align: 'left', formatter: Controller.api.formatter.url},
                        {field: 'order', title: __('Order')},
                        {field: 'isnew', title: __('Isnew')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate
                        }
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
                $("textarea[name='row[source]']").on("change", function () {
                    require(['../addons/docs/js/parser'], function (Parser) {
                        var parser = new HyperDown;
                        html = parser.makeHtml($("textarea[name='row[source]']").val());
                        $("#preview").html(html);
                    });
                });
                $("textarea[name='row[source]']").trigger("change");
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                url: function (value, row, index) {
                    return '<div class="input-group input-group-sm" style="width:250px;"><input type="text" class="form-control input-sm" value="' + value + '"><span class="input-group-btn input-group-sm"><a href="' + row.relativeurl + '" target="_blank" class="btn btn-default btn-sm"><i class="fa fa-link"></i></a></span></div>';
                },
            }
        }
    };
    return Controller;
});