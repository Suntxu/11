define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'domain/into/intolist/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortOrder:'desc',
                sortName:'subdate',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'bath', title: '批次',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        {field: 'audit', title: '审核状态',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'等待处理',1:'审核成功',2:'审核失败',3:'已撤销',4:'正在审核'},
                            footerFormatter: function (data) {
                                var field = 'szmsg';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }
                        },
                        {field: 'reg_id', title: '目标注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent'),},
                        {field: 'targetuser', title: '目标账号',formatter:Table.api.formatter.onclk,fieldname:'targetuser',affair:'onclick="clickcopy(this)"',},
                        {field: 'email', title: '用户名'},
                        {field: 'moneynum', title: '手续费',operate:false,sortable:true,
                            footerFormatter: function (data) {
                                var field = 'xtotal';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '审核成功的手续费 '+total_sum.toFixed(2)+'元';
                            }
                        },
                        {field: 'subdate', title: '提交时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'finishdate', title: '审核时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'special', title: '转回域名类型',formatter: Table.api.formatter.status,notit:'true',searchList:{0:'普通',1:'预释放',2:'0元转回'},},
                        {field: 'remark', title: '备注',operate:false},
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
        }

    };
    return Controller;
});

function setStat(status,id){
    if(status == 2){
        errselect = Config.fail_select;
        op = '<option value="">常见错误提示</option>';
        for(i in errselect){
            op += '<option value="'+i+'" title="'+errselect[i]+'">'+subStringShowDot(errselect[i])+'</option>';
        }

        layer.open({
            title: '请选择转回失败备注'
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
         //    ajx(text,id,2); 
         //  });
    }else if(status == 1){
        layer.confirm('是否执行此操作', {
          btn: ['执行','不执行'] //按钮
        }, function(index){
           layer.close(index);
           ajx('转回成功',id,1);
        }, function(){
            layer.msg('已放弃本次操作');
        });
    }else if(status == 4){
        layer.confirm('是否执行此操作', {
          btn: ['执行','不执行'] //按钮
        }, function(index){
           layer.close(index);
           ajx('',id,4);
        }, function(){
            layer.msg('已放弃本次操作');
        });
    }
}
function ajx(remark = '',id,status){
    $.ajax({
        url:'/admin/domain/into/intolist/UpdateS',
        type:'post',
        data:{action:'setStat',id:id,status:status,remark:remark},
        success:function(data){
            layer.closeAll('loading');
            layer.msg(data);
        },
        beforeSend:function(){
            layer.load(1);
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
function clickcopy(self){
    $('#target').val($(self).text());
    $('#target').select();
    if (document.execCommand('copy')) {
        document.execCommand('copy');
        layer.msg('目标账号复制成功');
    }else{
        layer.msg('浏览器不支持,请手动复制');
    }
}

