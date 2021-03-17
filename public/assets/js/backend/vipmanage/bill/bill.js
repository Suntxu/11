define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/bill/bill/index',
                    edit_url: 'vipmanage/bill/bill/edit',
                    // del_url: 'vipmanage/bill/bill//flag/2', 
                    // multi_url: 'vipmanage/bill/bill//flag/2',
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
                sortName: 'c.regtime',
                columns: [
                    [  
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            } },
                            
                        { field: 'c.bh', title: '订单编号',},
                        { field: 'b.bname', title: '发票抬头',},
                        { field: 'c.money', title: '发票金额', operate:'BETWEEN',
                         footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        { field: 'b.utype', title: '用户类型',formatter: Table.api.formatter.status,searchList: {1:'个人',2:'企业'}},
                        { field: 'b.btype', title: '发票类型',formatter: Table.api.formatter.status,searchList: {1:'增值税普通发票',2:'增值税专用发票'}},
                        { field: 'u.uid', title: '申请人',},
                        { field: 'b.status', title: '模板状态', formatter: Table.api.formatter.status,searchList: {0:'待处理',1:'处理完成',2:'处理失败'},notit:true},
                        { field: 'c.statu', title: '审核状态', formatter: Table.api.formatter.status,searchList: {0:'待审核',1:'审核通过',2:'审核失败'},notit:true},
                        { field: 'c.regtime', title: '申请时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        { field: 'c.autime', title: '审核时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        { field: 'c.remark', title: '备注',operate:false},
                      
                        // { field: 'chenn', title: '渠道名称',  },
                        
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ] 
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['c.id'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    console.log(params);
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
function doPrint() {
    var aa = window.form;
    window.print(aa); //调用浏览器的打印功能打印指定区域
}

//显示错误码
function hiddenYs(id,self,str=''){
    if(self.value == str){
        $('#'+id).css('display','');
    }else{
        $('#'+id).css('display','none');
        $('#'+id).val('');
    }
}
