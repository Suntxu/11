define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/realfault/index',
                    table: 'realfault',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'a.time',
                sortOrder:'desc',
                escape:false,
                columns: [
                    [
                        {field: 't.id', title: '模板ID',},
                        {field: 'uid', title: '用户名',},
                        {field: 'group', title:'实名名称',},
                        {field: 'true_name_id', title:'实名id',},
                        // {field: 'a.type', title:'注册商',searchList: {0:" 纳点"},},
                        {field: 'RegistrantType', title:'模板类型',searchList: {1:"个人",2:'企业'},},
                        {field: 'auth_status', title:'实名状态', formatter: Table.api.formatter.status, custom: {'未实名':'danger','实名提交失败':'danger','提交成功':'info','认证成功':'success','注册商实名失败':'danger','实名查询结果时模板不存在':'danger'}, searchList: {0:"未实名",1:"实名提交失败",2:"提交成功",3:"认证成功",4:"注册商实名失败",9:"实名查询结果时模板不存在"},},
                        {field: 'a.time',title: '实名失败时间',addclass: 'datetimerange',sortable:true,operate: false,formatter: Table.api.formatter.datetime},
                        {field: 'r.createtime',title: '模板创建时间',addclass: 'datetimerange',sortable:true,operate: false,formatter: Table.api.formatter.datetime},
                        {field: 'a.r_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'msg',title: '失败信息',operate:false },
                        {field: 'showurl', title: __('Operate'), table: table,operate:false,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [{
                                name: '实名认证',
                                text: '实名认证',
                                title: '重新实名认证',
                                classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                icon: 'fa fa-magic',
                                url: function(data){
                                    return '/admin/vipmanage/recode/realfault/resetreal/ids/'+data.id;
                                },
                                confirm:'是否确定此操作?',
                                success: function (data,ret) {
                                    layer.msg(ret.msg);
                                    if(ret.code==1) {
                                        $('.btn-refresh').click();
                                    }
                                    return false;
                                },
                            }],
                            formatter:function(value,row,index){
                                var that = $.extend({},this);
                                if(row['auth_status'] == '实名提交失败' || row['auth_status'] == '未实名'){
                                    return Table.api.formatter.operate.call(that,value,row,index);
                                }else{
                                    return '--';
                                }
                            }
                        },
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        edit: function () {
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