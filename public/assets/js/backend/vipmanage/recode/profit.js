define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/profit/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                orderName:'desc',
                escape: false, //转义空格
                // pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        { field: 'group', title: '用户名',},
                        { field: 'create_time', title: '交易时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'money', title: '盈利金额',sortable:true,operate: 'BETWEEN',footerFormatter: function (data) {
                                var field = 'ztotal';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }},
                        { field: 'type', title: '交易类型',sortable:true, addClass:'blogroll',formatter: Table.api.formatter.status,searchList: {0:'域名'},},
                        { field: 'subtype', title: '交易子类型',sortable:true,addClass:'blogroll_child',formatter: Table.api.formatter.status,searchList: {}},
                        { field: 'uip', title: '登录IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        { field: 'showurl', title: '链接',operate: false},
                    ]
                ],
            });
            
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
/**
 * 二级联动搜索
*/
$(document).on("change", ".blogroll", function () {
    child = {
                0:{0:'自动委托购买'},
            };
    var aa =  child[$(this).val()];
    var option = '<option value="">选择</option>';
    for( i in aa){
        option += '<option value="'+i+'">'+aa[i]+'</option>';
    }
    $('.blogroll_child').first().html(option);
});
