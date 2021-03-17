define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/entrust/index',
                    edit_url: 'domain/entrust/edit',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'e.create_time',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'group', title:'编号'},
                        {field: 'e.tit', title: '域名简介'},
                        {field: 'uid', title: '用户',},
                        {field: 'e.money', title: '报价金额',sortable:true,operate:'BETWEEN'},
                        {field: 'qq', title: '联系QQ',operate:false},
                        {field: 'mot', title: '联系电话',operate:false},
                        {field: 'email', title: '联系邮箱',operate:false},
                        {field: 'e.status', title: '状态',formatter: Table.api.formatter.status,notit:'true',searchList: {0:'待受理',1:'已受理',2:'成功',3:'失败'},},
                        {field: 'e.create_time',title: '申请时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
/**
 * 
 */
function hiddenYs(id,self,str=''){
    if(self.value == str){
        $('#'+id).css('display','');
        $('#remark1').val('联系不上卖家');
    }else{
        $('#'+id).css('display','none');
        $('#'+id+'1').val('');
    }
}
