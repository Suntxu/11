define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/attestation/index',

                    table: 'attachment'
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'createtime',
                sortOrder:'desc',
                escape:false,
                columns: [
                    [
                        {field: 't.title', title: '所属模板',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/attestation/edit',fieldvaleu:'id',fieldname:'ids',tit:'模板信息',},
                        {field: 't.id', title: '模板ID',visible: false,  operate:false},
                        {field: 'uid', title: '用户名',},
                        {field: 'c.id', title: '注册商', visible: false, operate:false,searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        { field: 'a.id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'system_id', title: '注册商对应id',},
                        {field: 'auth_status', title:'实名状态', formatter: Table.api.formatter.status,notit:true, searchList: {0:"未实名",1:"实名提交失败",2:"提交成功",3:"认证成功",4:"注册商实名失败",9:"实名查询结果时模板不存在"},},
                        {field: 'auth_remark', title:'实名认证失败原因',operate:false},
                        {field: 'group', title:'实名名称',},
                        {field: 'RegistrantType', title:'模板类型',searchList: {1:"个人",2:'企业'},},
                        {field: 'info_status', title:'模板状态', formatter: Table.api.formatter.status, custom: {'创建失败':'danger','创建成功':'success','申请手动添加':'info'}, searchList: {1:'创建失败',2:'创建成功',9:'申请手动添加'},},
                        {field: 'info_remark', title:'添加模板失败原因',operate:false},
                        {field: 'r.createtime',title: '创建时间',addclass: 'datetimerange',sortable:true,operate: false,formatter: Table.api.formatter.datetime},
                        {field: 'op', title: __('Operate'),width:200, operate:false}
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        slist: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/attestation/slist',
                    table: 'attachment'
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'o.createtime',
                sortOrder:'desc',
                escape:false,
                columns: [
                    [
                        {field: 'o.zcs', title: '注册商', operate:false,searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        // { field: 'a.id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'out_reg_id', title: '外部注册商',searchList: $.getJSON('/admin/domain/manage/getOutZcsList')},
                        {field: 'o.auth_status', title:'实名状态', formatter: Table.api.formatter.status,notit:true, searchList: {0:"未实名",1:"实名提交失败",2:"提交成功",3:"认证成功",4:"注册商实名失败",9:"实名查询结果时模板不存在"},},
                        {field: 'o.auth_remark', title:'实名认证失败原因',operate:false},
                        {field: 'o.info_status', title:'模板状态', formatter: Table.api.formatter.status, custom: {'创建失败':'danger','创建成功':'success','申请手动添加':'info'}, searchList: {1:'创建失败',2:'创建成功',9:'申请手动添加'},},
                        {field: 'o.info_remark', title:'模板认证失败原因',operate:false},
                        {field: 'out_code', title:'注册商编码',},
                        {field: 'o.createtime',title: '创建时间',addclass: 'datetimerange',operate: false,formatter: Table.api.formatter.datetime},
                        {field: 'op', title: __('Operate'),width:200,operate:false}
                    ]
                ],

                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['rz_id'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});

// 发送ajax
function real(url){
    layer.load(1);
    layer.confirm('确定要操作吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        $.post(url,{},function(data){
            layer.closeAll('loading');
            layer.msg(data.msg);
            if(data.code == 0){
                $('.btn-refresh').click();
            }
        });
    }, function(){
        layer.closeAll('loading');
    });
}

//错误信息
function errai(url){
    layer.load(1);
    $.get(url,{},function(data){
        layer.closeAll('loading');
        layer.alert(data.msg,{title:'返回信息'});
    },'json');

}
