define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/canceluser/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortOrder:'desc',
                sortName:'c.id',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'group', title: '用户名',operate:'LIKE',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/setuser/index',fieldvaleu:'userid',fieldname:'id',tit:'用户设置',},
                        {field: 'c.status', title: '审核状态',formatter: Table.api.formatter.status,searchList:{0:'待审核',1:'注销失败',2:'注销成功'},notit:true},
                        {field: 'c.time', title: '提交时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'c.endtime', title: '审核时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'msg', title: '备注',operate:false},
                        {field: 'ip', title: 'IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'operate', title: __('Operate'),operate:false},
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

//自定义审核
function audit(id,status){
    if(status == 1){
        layer.prompt({title: '请输入失败原因'}, function(text, index){
            layer.close(index);
            sendAjax(id,1,text);
        });
    }else{
        sendAjax(id,6,'审核成功');
    }
}
//发送ajax
function sendAjax(id,status,remark){
    layer.load();
    $.post('/admin/vipmanage/canceluser/audit',{id:id,status:status,remark:remark},function(res){
        layer.closeAll('loading');
        layer.msg(res.msg);
        if(res.code == 1){
            setTimeout(function(){
                $('.btn-refresh').click();
            },1500);
        }
        return false;
    },'json');

}