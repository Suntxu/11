define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/realaudit/index',
                    // add_url: 'general/attachment/add',
                    edit_url: 'vipmanage/realaudit/edit',
                    // del_url: 'general/attachment/del',
                    // multi_url: 'general/attachment/multi',
                    table: 'attachment'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'r.createtime desc , r.status asc',
                escape: false,
                columns: [
                    [
                        {field: 'uid', title: '用户名',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/realaudit/edit',fieldvaleu:'id',fieldname:'ids',tit:'身份审核',},
                        {field: 'r.renzheng', title:'认证类型', formatter: Table.api.formatter.status,searchList: {0:"个人认证",1:"企业认证"},},
                        {field: 'group', title:'实名名称',},
                        {field: 'r.status', title:'审核状态',
                            sortable:true,formatter: Table.api.formatter.status,
                            searchList: {0:"审核中",1:'失败',2:"成功",9:'已删除'},
                            custom:{'失败':'red','成功':'green'}},
                        {field: 'r.title', title: '标题'},
                        {field: 'r.createtime',title: '申请时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'checktime',title: '审核时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: '审核说明', operate:false},
                        {field: 'manmage', title: '操作',operate:false},
                    ]
                ],
                
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    var id = $("#id").val();
                    if (id !== '')
                        filter['r.id'] = id;
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
        maralname: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        }

    };
    return Controller;
});
$('.operaudit').on('click',function(){
    $('#audit_remark').val('手动审核');
});

$('.meral').on('click',function(){
    if($(this).val() == 1){
        $('#firm').css('display','');
    }else{
        $('#firm').css('display','none');
        $('input[name="row[busname]"]').val('');
        $('input[name="row[buslicence]"]').val('');
        $('input[name="row[image2]"]').val(''); 
    }
});

function setStat(id){
    layer.confirm('您确定要删除吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        $.ajax({
            url: '/admin/vipmanage/Realaudit/delrenzheng',
            type:'post',
            data:{id:id},
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
                layer.msg('删除失败');
            },
        });
    })
}





