define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'spread/elchee/participator/index',
                    add_url: 'spread/elchee/participator/add',
                    // edit_url: 'activity/wxfx/domain/edit',
                    del_url: 'spread/elchee/participator/del',
                    // multi_url: 'news/Msg/notice',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'p.inserttime',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'tit', title:'域名',align:'left',operate:'TEXT'},
                        {field: 'money', title: '价格(元)',operate:'between',sortable:true},
                        {field: 'uid', title:'卖家账号',},
                        {field: 'type',title:'活动类型',operate:false},
                        {field: 'inserttime', title:'参与时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
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
//根据选择的不同 控制店铺或者用户列表
function show(self){
   if($(self).val() == 1){
        $('#shop').hide();
        $('#domain').show();
   }else{
        $('.sp_container').css('width','492px')
        $('#shop').show();
        $('#domain').hide();
   }
} 
// 监控 是否批量删除域名
$('.tan').click(function(){
    layer.prompt({
      formType: 0,
      title: '请输入正确的用户名',
      // area: ['800px', '350px'] //自定义文本域宽高
    }, function(value, index, elem){
        layer.closeAll();
        layer.load(1);
        $.post('/admin/spread/elchee/participator/delshop',{uid:value},function(data){
            layer.closeAll();
            layer.msg(data.msg);
            if(data.code == 0){
                $('.btn-refresh').click();
            }
        });

    });
})
