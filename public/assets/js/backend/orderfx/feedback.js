define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'orderfx/feedback/index',
                    edit_url:'orderfx/feedback/edit',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
             // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'create_time',
                escape: false, //转义空格
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'uid', title: '用户名',sortable: true,},
                        { field: 'uqq', title: 'QQ号', sortable: true,},
                        { field: 'nickname', title: '昵称',sortable: true,},
                        { field: 'money', title: '奖励金额',operate: false,sortable:true,operate: 'BETWEEN',},
                        { field: 'desc', title: '反馈内容',operate:false,},
                        { field: 'type', title: '反馈类型',searchList: {0:'功能改进',1:'在线提问',2:'其他'},},
                        { field: 'status', title: '阅读状态',searchList:{0:"未阅读",1:"已阅读"}},
                        { field: 'cnstatus', title: '采纳状态',searchList:{0:"未采纳",1:"已采纳"}},
                        { field: 'create_time', title: '提交时间',operate: 'RANGE',addclass: 'datetimerange',formatter:Table.api.formatter.datetime,},
                        {field: 'operate', title: __('Operate'), table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate,},
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
            }
        }
    };
    return Controller;
});

function zhanshi(id){
    if(id == 0){
       $('#money').hide();
    }else{
       $('#money').show();
    }
}