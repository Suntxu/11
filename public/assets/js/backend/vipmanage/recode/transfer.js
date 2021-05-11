define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/transfer/index',
                    table: 'user',
                }
            });
            // console.log({tasktype});
            // if(Config.typs == 5){
             stvisible = false;
            // }
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sort:'r.createtime desc r.status asc',
                pageSize:25,
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                    
                        {field: 'r.id', title: '任务ID',},
                        {field: 'r.userid', title: '用户ID/管理员',},
                        {field: 'spec', title: '用户名/管理员',},
                        {field: 'r.tasktype', title: '操作类型',formatter: Table.api.formatter.status,searchList: {1:'更换信息模板',2:'注册域名',3:'修改dns',4:'域名续费',5:'批量解析',6:'批量删除解析',7:'批量找回域名'}},
                        {field: 'dcount', title: '域名数量',sortable:true,operate:false,footerFormatter: function (data) {
                                var field = 'znum';
                                var total_sum = data.reduce(function (sum, row) {
                                    return row[field];
                                }, 0);
                                return total_sum;
                            }},
                        {field: 'group', title: 'DNS修改值',},
                        {field: 'account', title: '账户余额',sortable:true,operate:'BETWEEN'},
                        {field: 'bh', title: '代金券编号',formatter:Table.api.formatter.alink,url:'/admin/spread/expand/voucherrecord',fieldvaleu:'bh',fieldname:'bh',tit:'代金券使用记录',},
                        {field: 'v_r_money', title: '使用金额',sortable:true,operate:'BETWEEN'},
                        {field: 'r.createtime', title: '开始时间',operate: 'INT', addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        {field: 'special_condition', title: '注册类型',formatter: Table.api.formatter.status, searchList:{0:'普通',1:'拼团',2:'限量',3:'注册包'}},
                        {field: 'r.status', title: '执行进度',formatter: Table.api.formatter.status,searchList: {0:'任务执行中',1:'任务执行完成'}},
                        {field: 'r.ltype', title: '提交来源',formatter: Table.api.formatter.status,searchList: {0:'官网',1:'分销系统'}},
                        {field: 'uip', title: '用户操作IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '任务详情',
                                    text: '任务详情',
                                    title: '任务详情',
                                    classname: 'btn btn-xs btn-success btn-magic dialogit',
                                    icon: 'fa fa-magic',
                                    url: function(res){
                                        if(res.flag){
                                            return '/admin/vipmanage/recode/transfershow/index?type='+res.tasktype+'&tid='+res.id;
                                        }else{
                                            return '/admin/oldrecord/transfershow/index?type='+res.tasktype+'&tid='+res.id;

                                        }
                                    },
                                }],
                          },
                    ]
                ],

                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#userid").val();
                    if (gro != '')
                        filter['u.uid'] = gro;
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
