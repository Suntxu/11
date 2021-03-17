define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/access/shows/index',
                    table: 'user',
                }
            });
            var table = $("#table");
             // 在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function (i,n) {
                    if ($("td:eq(2)",this).text().trim() != '待审核') {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
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
                        {checkbox : true},
                        {field: 'domain', title: '域名',},
                        {field: 'status', title: '转入状态',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'待审核',1:'转入中',2:'转入成功',3:'转入失败',4:'已取消'},sortable:true,},
                        {field: 'remark', title: '转入备注',operate:false,},
                        {field: 'audittime', title: '执行时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime,sortable:true,},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter.aid = gro;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            //选中耨个时间 获取选择的按钮
            $('#shcg').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                    $.each(temp,function(i,n){
                        id.push(n.id);
                    });
                    setStat(1,id,$('#userid').val());
                });
             //选中耨个时间 获取选择的按钮
            $('#shsb').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                    $.each(temp,function(i,n){
                        id.push(n.id);
                    });
                    setStat(2,id,$('#userid').val());
                });
            // 导出功能
            $('#dc').click(function(){
                location.href = '/admin/domain/access/shows/download?pid='+$('#userid').val();
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

function setStat(status,id){
    if(status == 2){
         layer.prompt({title: '请输入错误备注', formType: 2}, function(text, index){
            layer.close(index);
            layer.load(1);
            ajx(text,id,2); 
          });
    }else if(status == 1){
        layer.confirm('是否执行此操作', {
          btn: ['执行','不执行'] //按钮
        }, function(){
            layer.load(1);
           ajx('任务提交成功',id,1);
        }, function(){
            layer.msg('已放弃本次操作');
        });
    }
}
function ajx(remark = '',id,status){
    $.ajax({
        url:'/admin/domain/access/shows/UpdateS',
        type:'post',
        data:{action:'setStat',id:id,status:status,remark:remark},
        success:function(data){
            layer.closeAll('loading');
            layer.msg(data);
        },
        complete:function(){
            // location.href = 'domain_into_list.php?<?=$nowwd?>';
            $('.btn-refresh').click();
        },
        error:function(){
            layer.msg('发送失败');
        },

    });
}