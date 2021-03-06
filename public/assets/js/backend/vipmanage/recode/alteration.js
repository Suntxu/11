define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/alteration/index',
                    modify_url: 'vipmanage/recode/alteration/modify',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'a.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'uid', title: '用户名',},
                        {field: 'rz_id', title: '实名信息(新)',operate:false},
                        {field: 'old_rz_id', title: '实名信息(旧)',operate:false},
                        {field: 'a.status', title: '状态',searchList: {0:'已提交',1:'审核通过',2:'审核未通过'}},
                        {field: 'a.oper', title: '提交类型',searchList: {0:'用户提交',1:'客服提交'}},
                        {field: 'a.time', title: '申请时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: '审核时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'reason', title: '变更原因',operate:'like'},
                        {field: 'check_reason', title: '审核备注',operate:false},
                        {field: 'ip', operate:false, title: 'IP地址',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                name:'成功',
                                text: '成功',
                                title:'成功',
                                classname:'btn btn-xs btn-warning',
                                icon:'fa fa-deaf',
                                visible:function(res){
                                    if(res.status == 0){
                                        return true;
                                    }
                                },
                                extend:function(res){
                                    return 'onclick="audit(\''+res.id+'\',1)"';
                                },

                            },{
                                name:'失败',
                                text: '失败',
                                title:'失败',
                                classname:'btn btn-xs btn-danger',
                                icon:'fa fa-paypal',
                                visible:function(res){
                                    if(res.status == 0){
                                        return true;
                                    }
                                },
                                extend:function(res){
                                    return 'onclick="audit(\''+res.id+'\',2)"';
                                },
                            }]
                        }
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
        modify:function(){
                    $("#check").click(function(){
                        $("#bg_zh").empty();
                        $("#zh_syz").empty();
                        var username=$('#username').val();
                        layer.load();
                        $.post('/admin/vipmanage/recode/alteration/modify',{username:username},function(res){
                        layer.closeAll('loading');
                        if(res.code == 0){
                            layer.msg(res.msg);
                            return false;
                        }
                        for(var i=0;i<=res.length;i++){
                                if(res[i].default==1){
                                    $('#zh_syz').val(res[i].group);
                                    $('#dq_id').attr("href","/admin/vipmanage/realaudit/edit?ids="+res[i].id);
                                    continue;
                                }
                                html="";
                                html+="<option value="+res[i].id+">"+res[i].group+"</option>";
                                $("#bg_zh").append(html);
                        }
                    },'json');
                });
                    $('#bg_id').click(function(){
                        if(!$("#bg_zh").val()){
                             layer.msg('查看变更账户所有者为空');
                            return false;
                        }
                        $('#bg_id').attr("href","/admin/vipmanage/realaudit/edit?ids="+$("#bg_zh").val());
                    });
                    $('#dq_id').click(function(){
                        if(!$("#zh_syz").val()){
                             layer.msg('查看当前账户所有者为空');
                            return false;
                        }
                    });
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
 * 失败审核
 */
function audit(id,status){
    layer.prompt({title: '请输入审核备注', formType: 2}, function(text, index){
        layer.close(index);
        layer.load();
        $.post('/admin/vipmanage/recode/alteration/audit',{id:id,check_reason:text,status:status},function(res){
            layer.closeAll('loading');
            layer.msg(res.msg);
            if(res.code == 1){
                window.setTimeout(function(){
                    $('.btn-refresh').click();
                },1500);
            }
        },'json');
    });
}
