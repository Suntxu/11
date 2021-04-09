define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'orderfx/order/index',
                    feedback_url:'orderfx/order/feedback',
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
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'bh', title: '工单编号',operate:'LIKE',formatter:Table.api.formatter.alink,url:'/admin/orderfx/order/show/',fieldvaleu:'id',fieldname:'id',tit:'工单查看',},
                        { field: 'type', title: '问题分类',operate:false,},
                        { field: 'email', title: '邮箱地址',},
                        { field: 'create_time', title: '提交时间',operate: 'RANGE',addclass: 'datetimerange',formatter:Table.api.formatter.datetime,},
                        { field: 'status', title: '工单进度',formatter: Table.api.formatter.status,searchList: {'0':'未处理','1':'处理中','2':'已完成'},},
                        { field: 'fx_stat', title: '工单类型',formatter: Table.api.formatter.status,searchList: {'0':'待回复','1':'待反馈','2':'已完结','4':'已删除'},},
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
        show: function () {
            Controller.api.bindevent();
        },
        feedback: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    feedback_url:'orderfx/order/feedback' ,
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
             // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.feedback_url,
                pk: 'id',
                sortName: 'id',
                orderName: 'desc',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'uid', title: '用户名',sortable: true,},
                        { field: 'uqq', title: 'QQ号', sortable: true,},
                        { field: 'type', title: '反馈类型',searchList: {0:'功能改进',1:'在线提问',2:'其他'},},
                        { field: 'nickname', title: '昵称',sortable: true,},
                        { field: 'desc', title: '反馈内容',operate:false,},
                        { field: 'create_time', title: '提交时间',operate: 'RANGE',addclass: 'datetimerange',formatter:Table.api.formatter.datetime,},
                        { field: 'status', title: '阅读状态',searchList: {0:'未读',1:'已读'}},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                name:'已读',
                                text: '已读',
                                title:'已读',
                                classname:'btn btn-xs btn-ajax btn-warning',
                                visible:function(res){
                                  if(res.y_status == 0){
                                      return true;
                                  }
                                  return false;
                                },
                                url: function(res){
                                    return '/admin/orderfx/order/ready?id='+res.id;
                                },
                                success: function (data,ret) {
                                    layer.msg(ret.msg);
                                    if(ret.code==1){
                                        $('.btn-refresh').click();
                                    }
                                    return false;
                                },
                            }]
                        }
                    ]
                ],
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

function bigImg(self,id){

    var src = self.getAttribute('src');
    if(document.getElementById('bigimg'+id).getAttribute('src') == src){
        document.getElementById('bigimg'+id).setAttribute('src','');
        document.getElementById('a'+id).setAttribute('href','');
        document.getElementById('a'+id).setAttribute('target','');
    }else{
        document.getElementById('bigimg'+id).setAttribute('src',src);
        document.getElementById('a'+id).setAttribute('href',src);
        document.getElementById('a'+id).setAttribute('target','blank');
    }

}
