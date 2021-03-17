define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/reservedomainother/index',
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
                        // { field: 'domain_type', title: '类型',operate: 'RLIKE',searchList: $.getJSON('domain/manage/getDomainType'),},
                        { field: 'reg_time', title: '注册时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'del_time', title: '删除时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'icp_serial', title: '备案号',},
                        { field: 'icp_org', title: '备案性质',},
                        { field: 'special_condition', title: '是否备案',visible:false,searchList: {1:'已备案',2:'未备案'}},
                        { field: 'icp_name', title: '建站主体',operate:'LIKE'},
                        // { field: 'icp_index', title: '网站首页',operate:'LIKE'},
                        // { field: 'access_pro', title: '接入商',searchList:{'-1':'待查',0:'未知',1:'阿里云',2:'腾讯云',3:'GAINET',4:'西部数码',5:'CNDNS',6:'百度云'}},
                        // { field: 'pr', title: 'PR',sortable:true,operate:'BETWEEN'},
                        // { field: 'employ', title: '收录',sortable:true,operate:'BETWEEN'},
                        // { field: 'weight', title: '权重',sortable:true,operate:'BETWEEN'},

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