define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'staffuse/voucherrecord/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'c.createtime',
                columns: [
                    [  
                        // { checkbox: true,},
                        // { checkbox: true,},
                        { field: 'bh', title: '编号',operate:'LIKE',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类'RANGE'
                        } },
                        { field: 'uid', title: '发放用户',},
                        { field: 'addmoney', title: '面额',operate:'BETWEEN',footerFormatter: function (data) {  
                                var field = 'mezje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        { field: 'c.createtime', title: '使用日期',operate: 'INT',addclass: 'datetimerange',formatter:Table.api.formatter.datetime,},
                        { field: 'c.type', title: '使用类型',sorttableL:false,formatter: Table.api.formatter.status,searchList: {'0':'系统发放','1':'域名注册',},},
                         { field: 'remark', title: '备注',operate:false},
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

function bigImg(self,id){

    var src = self.getAttribute('src');
    if(document.getElementById('bigimg'+id).getAttribute('src') == src){
        document.getElementById('bigimg'+id).setAttribute('src','');
        document.getElementById('a'+id).setAttribute('href','');
        document.getElementById('a'+id).setAttribute('target','');
    }else{ 
        document.getElementById('bigimg'+id).setAttribute('src',src);
        document.getElementById('a'+id).setAttribute('href',src);
        document.getElementById('a'+id).setAttribute('target','blank');
    }
       
}