define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/Attributeupdate/index',
                    table: 'user',
                }
            });
            var table = $("#table");
                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    createTime: 'desc',
                    escape:false,
                    showJumpto: true,
                    columns: [
                        [
                            { checkbox: true,formatter:function(value, row, index){
                                    if (row.status !== 0){
                                        return {disabled: true};
                                    }
                                }},
                            { field: 'tradeid', title: '域名ID',sortable:false},
                            { field: 'tit', title: '域名',operate:'TEXT',formatter:function(value){
                                    return '<a style="cursor:pointer;color:#66B3FF" onclick="copyData(' + "'" +value + "'" +')">' + value + '</a>'
                            }},
                            { field: 'uid', title: '归属用户',},
                            { field: 'sogou_sl', title: '搜狗收录',operate:'BETWEEN'},
                            { field: 'baidu_sl', title: '百度收录',operate:'BETWEEN'},
                            { field: 'icpholder', title: '建站类型',searchList:{0:'不设置',1:'阿里云',2:'腾讯云',3:'其它',4:'所有'},formatter:function(value){
                                    if (value === 0) {
                                        return '不设置';
                                    }else if(value == 1){
                                        return '阿里云';
                                    }else if(value == 2){
                                        return '腾讯云';
                                    }else if(value == 3){
                                        return '其它';
                                    }else if(value == 4){
                                        return '所有';
                                    }
                                }},
                            { field: 'icptrue', title: '建站性质',searchList:{0:'不设置',1:'个人',2:'企业',3:'未备案',4:'存在'},formatter:function(value){
                                    if (value === 0) {
                                        return '不设置';
                                    }else if(value == 1){
                                        return '个人';
                                    }else if(value == 2){
                                        return '企业';
                                    }else if(value == 3){
                                        return '未备案';
                                    }else if(value == 4){
                                        return '存在';
                                    }
                                }},
                            { field: 'attc', title: '特殊属性',searchList:{0:'不设置',1:'二级不死',2:'大站',3:'绿标'},formatter:function(value){
                                    if (value == 0) {
                                        return '不设置';
                                    }else if(value == 1){
                                        return '二级不死';
                                    }else if(value == 2){
                                        return '大站';
                                    }else if(value == 3){
                                        return '绿标';
                                    }
                                }},
                            { field: 'create_time', title: '申请时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                            { field: 'txt', title: '审核备注',operate:false},
                            { field: 'dptu.status',sortable:true,title: '审核状态',searchList:{0:'待审核',1:'已审核',2:'拒绝'},formatter:function(value){
                                    if (value == 0) {
                                        return '<font color="blue">待审核</font>';
                                    }else if(value == 1){
                                        return '<font color="green">已审核</font>';
                                    }else if(value == 2){
                                        return '<font color="red">拒绝</font>';
                                    }
                                }},
                            { field: 'nickname', title: '审核管理员',operate:false},
                            { field: 'time', title: '审核时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                            {field: 'manmage', title: '操作',operate:false},
                        ]
                    ],
                });
            //批量复制
            $('#btn-copy').click(function(){
                // 获取选中的列
                var object = $('#table').bootstrapTable('getAllSelections');
                var tit = '';
                $.each(object,function (index,obj) {
                    tit += obj.tit + "\n";
                })
                copyData(tit);
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
        layer.prompt({title: '请输入拒绝备注', formType: 2}, function(serr, index){
            layer.close(index);
            layer.load(1);
            ajx(serr,id,2);
        });
    }else if(status == 1){
        layer.load(1);
        ajx('',id,1);
    }
}

function ajx(remark = '',id,status){
    if (status == 2) {
        var url = '/admin/domain/Attributeupdate/refuse';
    }else if(status == 1){
        var url = '/admin/domain/Attributeupdate/agree';
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



