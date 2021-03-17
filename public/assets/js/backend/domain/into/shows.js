define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/into/shows/index',
                    table: 'user',
                }
            });
            var table = $("#table");
             // 在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function (i,n) {
                    if ($("td:eq(3)",this).text().trim() != '正在审核') {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                    // //设置ID
                    // n.setAttribute('id',json[i].id);
                });
               
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortOrder:'asc,desc',
                sortName:'',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'domian', title: '域名',},
                        {field: 'name' , title: '注册商',operate:false},
                        {field: 'tit', title: '注册接口',operate:false},
                        {field: 'zcsj', title: '注册时间',operate:'RANGE',sortable:true,},
                        {field: 'dqsj', title: '到期时间',operate:'RANGE',sortable:true,},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter.bid = gro;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
          
            // 导出功能
            $('#dc').click(function(){
                location.href = '/admin/domain/into/shows/download?pid='+$('#userid').val();
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

