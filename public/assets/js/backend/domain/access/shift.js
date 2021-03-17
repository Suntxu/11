define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/access/shift/index',
                    table: 'user',
                }
            });
            var table = $("#table");
             // 在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function (i,n) {
                    if ($("td:eq(2)",this).text().indexOf('待审核') == -1 ) {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                });
               
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortOrder:'asc,desc',
                sortName:'subdate desc,audit asc',
                escape: false, //转义空格
                columns: [
                    [
                        {checkbox : true},
                        {field: 'bath', title: '批次',},
                        {field: 'audit', title: '审核状态',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'待审核',1:'任务执行成功',2:'审核失败',3:'任务执行中',4:'用户取消'}},
                        // {field: 'status_remark', title: '审核情况',operate:false},
                        {field: 'b.reg_id', title: '目标注册商',formatter: Table.api.formatter.status,notit:'true',searchList:$.getJSON('category/getcategory?type=api&xz=parent'),},
                        {field: 'b.api_id', title: '目标账号',operate:false,},
                        {field: 'u.uid', title: '申请人',},
                        {field: 'email', title: '申请账号',},
                        {field: 'subdate', title: '提交时间',sortable:true,operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'finishdate', title: '审核时间',sortable:true,operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'manmage', title: '操作',operate:false},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['b.id'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
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
    if(!id){
        id = [];
        var temp = $("#table").bootstrapTable('getSelections');
        for(var i in temp){
            id.push(temp[i]['id']);
        }
    }    
    if(status == 2){

        errselect = Config.fail_select;
        op = '<option value="">常见错误提示</option>';
        for(i in errselect){
            op += '<option value="'+i+'" title="'+errselect[i]+'">'+subStringShowDot(errselect[i])+'</option>';
        }

        layer.open({
            title: '请选择转入失败备注'
            ,area: ['38%', '56%']
            , btn: ['确定','取消']
            , content: '<div class="layui-form-item"><div class="layui-input-block"><select id="errselect" style="width:100%;height:33px;">' +op+'</select></div></div>'
                +'<div class="layui-form-item"><div class="layui-input-block"><textarea id="errtext" placeholder="请选择或者填写失败备注" style="width:100%;margin-top:7%;height:130px;"></textarea></div></div>'
                +'<script>$("#errselect").on("change",function(){errselect = Config.fail_select;if(errselect[this.value]){console.log(errselect[this.value]);$("#errtext").val($("#errtext").val()+errselect[this.value]);$(this).val("");}});</script>'
            , yes: function (index) {
                serr = $('#errtext').val();
                if(!serr){
                    layer.msg('请选择或者填写失败备注!');
                    return false;
                }
                layer.close(index);
                layer.load(1);
                ajx(serr,id,2); 
            }
        });
         // layer.prompt({title: '请输入错误备注', formType: 2}, function(text, index){
         //    layer.close(index);
         //    layer.load(1);
         //    ajx(text,id,2); 
         //  });
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
        url:'/admin/domain/access/shift/UpdateS',
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


