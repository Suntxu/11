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
                        { field: 'group', title: '编号',},
                        { field: 'uid', title: '用户名',sortable: true,},
                        { field: 'uqq', title: 'QQ号', sortable: true,},
                        { field: 'nickname', title: '昵称',sortable: true,},
                        { field: 'money', title: '奖励金额',operate: false,sortable:true,operate: 'BETWEEN',},
                        { field: 'desc', title: '反馈内容',operate:false,},
                        { field: 'type', title: '反馈类型',searchList: {0:'功能改进',1:'在线提问',2:'其他'},},
                        { field: 'status', title: '阅读状态',searchList:{0:"未阅读",1:"已阅读"}},
                        { field: 'cnstatus', title: '采纳状态',searchList:{0:"未采纳",1:"已采纳"}},
                        { field: 'ex_status', title: '处理状态',searchList:{0:"待处理",1:"已处理",2:'不处理'}},
                        { field: 'ex_remark', title: '不处理原因',},
                        { field: 'create_time', title: '提交时间',operate: 'RANGE',addclass: 'datetimerange',formatter:Table.api.formatter.datetime,},
                        { field: 'operate', title: __('Operate'),
                            width:150,
                            table: table,events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                name: '已处理',
                                text: '已处理',
                                title: '已处理',
                                classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                confirm:'确定要处理吗？',
                                url:function(res){
                                    return '/admin/orderfx/feedback/modi?id='+res.id+'&status=1';
                                },
                                visible:function(res){
                                    if(res.eex_status == 0){
                                        return true;
                                    }
                                    return false;
                                },
                                success: function (data,ret) {
                                    if(ret.code==1){
                                        $('.btn-refresh').click();
                                    }else{
                                        layer.msg(ret.msg);
                                    }
                                    return false;
                                },
                            },{
                                name: '不处理',
                                text: '不处理',
                                title: '不处理',
                                classname: 'btn btn-xs btn-danger btn-magic',
                                extend:function(res){
                                    return 'onclick="modi('+res.id+')"';
                                },
                                visible:function(res){
                                    if(res.eex_status == 0){
                                        return true;
                                    }
                                    return false;
                                }
                            }],
                        },
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

/**
 * 不处理
 * @param id
 */
function modi(id){
    layer.prompt({title: '请输入备注', formType: 2}, function(text, index){
        layer.close(index);
        layer.load(0);
        $.post('/admin/orderfx/feedback/modi',{id:id,remark:text,status:2},function(res){
            layer.closeAll('loading');
            if(res.code==1){
                $('.btn-refresh').click();
            }else{
                layer.msg(res.msg);
            }
        },'json');

    });
}