define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/release/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'icp_name',
                orderName:'desc',
                escape: false, //转义空格
                exportDataType:'all',
                columns: [
                    [
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'group', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'len', title: '长度',operate:'BETWEEN',sortable:true},
                        { field: 'reg_time', title: '注册时间',addclass: 'datetimerange',sortable:true,operate: 'INT'},
                        { field: 'del_time', title: '删除时间',defaultValue:getDateExpire(-24)+' 00:00:00 - '+getDateExpire(5)+' 23:59:59',addclass: 'datetimerange',sortable:true,operate: 'INT'},
                        { field: 'icp_serial', title: '备案号',},
                        { field: 'icp_org', title: '备案性质',},
                        { field: 'icp_index', title: '备案历史',visible:true,searchList: {'当前存在':'当前存在','历史存在':'历史存在'}},
                        { field: 'icp_name', title: '建站主体',operate:'LIKE'},
                        { field: 'access_pro', title: '接入商',searchList:{'-1':'待查',0:'未知',1:'阿里云',2:'腾讯云',3:'GAINET',4:'西部数码',5:'CNDNS',6:'百度云'}},
                        { field: 'type', title: '释放类型',sortable:true,searchList:{1:'ali',88:'ename',106:'GD',67:'xb',1000:'怀米'}},
                        { field: 'money', title: '预定价格',sortable:true,operate:'BETWEEN'},
                        { field: 'gj', title: '估价',operate:'BETWEEN'},
                        { field: 'pr', title: 'PR值',sortable:true,searchList:{'-1':'待查'}},
                        { field: 'employ', title: '收录',sortable:true,searchList:{'-1':'待查'}},
                        { field: 'weight', title: '权重',sortable:true,searchList:{'-1':'待查'}},
                        { field: 'ext_chain', title: '外链',searchList:{'-1':'待查'}},
                        { field: 'qq_check', title: 'QQ状态',searchList:{'-1':'待查',1:'未拦截',2:'拦截',3:'未知'}},
                        { field: 'wx_check', title: '微信状态',searchList:{'-1':'待查',1:'未拦截',2:'拦截',3:'未知'}},


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