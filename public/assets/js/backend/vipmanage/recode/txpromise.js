define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/txpromise/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'p.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [   
                        { field: 'uid', title: '用户名',},
                        { field: 't.txname', title: '提现姓名',},
                        { field: 't.txzh', title: '账号/卡号',},
                        { field: 'p.status', title: '状态',searchList:{0:'未审核',1:'审核成功',2:'审核失败'}},
                        { field: 'p.type', title: '类型',searchList:{0:'身份证正面照片',1:'身份证反面照片',2:'手持身份证正面照片',3:'承诺书照片',4:'企业营业执照照片'}},
                        { field: 'datum', title: '图片',formatter: Table.api.formatter.image,operate:false},
                        { field: 'p.create_time', title: '提交时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'remark', title: '备注',operate:false},
                        { field: 'operate', title: __('Operate'), table: table, 
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name:'成功',
                                    text: '成功',
                                    title:'成功',
                                    classname:'btn btn-xs btn-warning btn-ajax',
                                    icon:'fa fa-deaf',
                                    confirm:'确定要进行此操作吗?',
                                    url: function(res){
                                        return '/admin/vipmanage/recode/txpromise/audit?status=1&id='+res.id;
                                    },
                                    visible:function(res){
                                        if(res.status == 0){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }

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
                                        return 'onclick="audit(\''+res.id+'\')"';
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
 * 失败审核
 */
function audit(id){
    layer.prompt({title: '请输入错误备注', formType: 2}, function(text, index){
        layer.close(index);
        layer.load();
        $.post('/admin/vipmanage/recode/txpromise/audit?id='+id,{remark:text,status:2},function(res){
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