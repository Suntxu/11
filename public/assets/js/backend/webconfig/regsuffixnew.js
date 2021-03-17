define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'webconfig/regsuffixnew/index',
                    add_url: 'webconfig/regsuffixnew/add',
                    edit_url: 'webconfig/regsuffixnew/edit',
                    // del_url: 'webconfig/regsuffix/del', 
                    multi_url: 'webconfig/regsuffixnew',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                escape:false,
                sortName: 'etime',
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'uid', title: '用户',},
                        { field: 'suffix', title: '后缀',searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker'},
                        { field: 'zcs', title: '注册商',operate: false,},
                        { field: 'aid', title: '接口商',operate: false,},
                        { field: 'money', title: '设置金额', operate:'BETWEEN' },
                        { field: 'stime', title: '开始时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'etime', title: '结束时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'group', title: '任务状态', formatter: Table.api.formatter.status,searchList: {1:'未开始',2:'进行中',3:'已结束'},notit:true},
                        { field: 'create_time', title: '添加时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'status', title: '状态', formatter: Table.api.formatter.status,searchList: {0:'已启用',1:'已禁用'}},
                        { field: 'show', title: '注册详情', operate:false,formatter:Table.api.formatter.alinks,url:'/admin/vipmanage/recode/reglog',fieldvaleu:['suffix','wtime','uid'],fieldname:['d.hz','r.createtime','u.uid'],tit:'域名注册记录'},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ] 
                ]
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
 * 选择接口商
 */
function getSuffixApi(self){
    var hz = $(self).val();
    layer.load(1);
    $.post('/admin/webconfig/regsuffixnew/getRegisterApis',{hz:hz},function(res){
        layer.closeAll('loading');
        if(res.code == 0){
            if(res.data.length){
                var html = '<option value="">请选择</option>';
                for(var i in res.data){
                    html += '<option value="'+res.data[i].id+'">'+res.data[i].name+'</option>';
                }
            }else{
                var html = '<option value="">无可用注册商</option>';
            }
            
            $('#ref').html(html);
        }else{
            layer.msg('注册商接口获取失败,请联系管理人员!');
        }
        return false;

    },'json');



}