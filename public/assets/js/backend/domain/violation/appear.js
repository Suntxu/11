define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () { //未上报
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/violation/appear/index',
                    del_url: 'domain/violation/appear/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                escape:false,
                showJumpto: true,
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'uid', title: '归属用户',},
                        { field: 'punish_type', title: '处罚动作',searchList:{0:'hold',1:'警告'}},
                        { field: 'type_cause', title: '处罚信息',searchList:{0:'网站存在欺诈侵权类违法违规内容',1:'网站存在赌博类违法违规内容',2:'网站存在色情低俗类违法违规内容',3:'网站存在国家政策类违法违规内容'}},
                        { field: 'img_path', title: '违规截图',operate:false,formatter:Table.api.formatter.image},
                        // { field: 'add_type', title: '添加类型',searchList:{0:'手动',1:'自查'}},
                        { field: 'create_time', title: '添加/处罚时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        { field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    title:'上报',
                                    classname:'btn btn-xs btn-success',
                                    icon:'fa fa-pencil',
                                    extend:function(res){
                                        return 'onclick="apperar('+res.id+')"';
                                    }
                                },
                                {
                                    title:'上传',
                                    classname:'btn btn-xs btn-success',
                                    icon:'fa fa-folder-o',
                                    extend:function(res){
                                        return 'onclick="uploading('+res.id+')"';
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ],
            });
             //批量修改
            $('#btn-update').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                //拼装链接 进行点击
                apperar(id.join(','));
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        reported: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/violation/appear/reported',
                    table: 'user',
                }
            });
            var table = $("#table");
            // // 在表格内容渲染完成后回调的事件
            // table.on('post-body.bs.table', function (e, json) {
            //     $("tbody tr[data-index]", this).each(function (i,n) {
            //         if (json[i].rreported_status == 1 ) {
            //             $("input[type=checkbox]", this).prop("disabled", true);
            //         }
            //     });
            // });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                escape:false,
                showJumpto: true,
                columns: [
                    [  
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'uid', title: '归属用户',},
                        { field: 'punish_type', title: '处罚动作',searchList:{0:'hold',1:'警告'}},
                        { field: 'type_cause', title: '处罚信息',searchList:{0:'网站存在欺诈侵权类违法违规内容',1:'网站存在赌博类违法违规内容',2:'网站存在色情低俗类违法违规内容',3:'网站存在国家政策类违法违规内容'}},
                        { field: 'img_path', title: '违规截图',operate:false,formatter:Table.api.formatter.image},
                        { field: 'add_type', title: '添加类型',searchList:{0:'手动',1:'自查'}},
                        { field: 'create_time', title: '添加/处罚时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
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

/**
 * 上报域名
 */
function apperar(ids){
    layer.load(1);
    $.post('/admin/domain/violation/appear/updamo',{ids:ids},function (res){
            layer.closeAll('loading');
            layer.msg(res.msg);
            if(res.code == 1){
                setTimeout(function(){
                    $('.btn-refresh').click();
                },1500);
            }
    },'json');

}
/**
 * 上传图片
 */
function uploading(ids){
    $('#myTabContent').append('<input accept="image/*" onchange="checkField()" name="fileupload" type="file" id="uploading">');
    $('#uploading').hide();
    $('#uploading').attr('ids',ids);
    $('#uploading').click();
}

function checkField(){
    var imgs = $('#uploading').get(0).files[0];
    var ids = $('#uploading').attr('ids');
    var formData = new FormData();//新建一个formData来储存需要传递的信息
    formData.append('file', imgs);//需要传递的字段image路径
    layer.load(1);
    $.ajax({
        type: 'POST',
        url: '/admin/ajax/upload?filepath=oss',
        data: formData,
        contentType:false,
        processData:false,
        dataType: 'json',
        success: function (data) {
            if (data.code == 1) {
                $.post('/admin/domain/violation/appear/Updateillegal',{ids:ids,img:data},function (res){
                    if(res.code == 1){
                        setTimeout(function(){
                            layer.closeAll('loading');
                            $('#uploading').remove();
                            layer.msg('上传成功');
                            $('.btn-refresh').click();
                        },1000);
                    }
                },'json');

            }
        },
        error: function (err) {
        }
    });
}

