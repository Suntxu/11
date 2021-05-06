define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/domaindemand/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'd.id',
                createTime: 'desc',
                escape:false,
                showJumpto: true,
                sortreload:true,
                showtotal:false,
                columns: [[
                        { checkbox: true,formatter:function(value, row, index){
                            if (row.status !== 0){
                                return {disabled: true};
                            }
                        }},
                        { field: 'title', title: '标题',operate:'LIKE'},
						{ field: 'details', title: '详情',operate:false, },
						{ field: 'group', title: '是否议价',searchList:{1:'议价',2:'非议价'},visible:false},
						{ field: 'd.budget', title: '价格', operate:'BETWEEN',sortable:true},
						{ field: 'd.contact_qq', title: '联系QQ'},
						{ field: 'd.contact_tel', title: '联系电话'},
						{ field: 'd.addtime', title: '发布时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
						{ field: 'uid', title: '发布用户',operate:false},
						{ field: 'refuse_txt', title: '审核备注',operate:false},
						{ field: 'd.status', title: '审核状态',searchList:{0:'未处理',1:'已处理',2:'拒绝'},formatter:function(value){
                                if (value === 0) {
                                    return '未处理';
                                }else if(value == 1){
                                    return '<font color="green">已处理</font>';
                                }else if(value == 2){
                                    return '<font color="red">拒绝</font>';
                                }
                        }},
                        { field: 'a.nickname', title: '审核管理员'},
                        { field: 'd.handle_time', title: '审核时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        {field: 'manmage', title: '操作',operate:false},
                    ]]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
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
        layer.open({
            title: '请输入拒绝备注'
            ,area: ['56%', '38%']
            , btn: ['确定','取消']
            , content: '<div class="layui-form-item"></div>'
                +'<div class="layui-form-item"><div class="layui-input-block"><textarea id="errtext" placeholder="请输入拒绝备注" style="width:100%;margin-top:7%;height:130px;"></textarea></div></div>'
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
    }else if(status == 1){
        layer.load(1);
        ajx('',id,1);
    }
}

function ajx(remark = '',id,status){
    if (status == 2) {
        var url = '/admin/domain/Domaindemand/refuse';
    }else if(status == 1){
        var url = '/admin/domain/Domaindemand/agree';
    }
    $.ajax({
        url: url,
        type:'post',
        data:{id:id,txt:remark},
        success:function(data){
            layer.closeAll('loading');
            layer.msg(data.msg);
        },
        beforeSend:function(){
            layer.load(1);
        },
        complete:function(){
            $('.btn-refresh').click();
        },
        error:function(){
            layer.msg('发送失败');
        },
    });
}
//批量操作
function operation(status){
    var object = $('#table').bootstrapTable('getAllSelections');
    var ids = '';
    $.each(object,function (index,obj) {
        ids += obj.id + ',';
    })
    ids = ids.substring(0,ids.length-1)
    if (status == 1) {
        setStat(1,ids);
    }else if(status == 2){
        setStat(2,ids);
    }
    
}

