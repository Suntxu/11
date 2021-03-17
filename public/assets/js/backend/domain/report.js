define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/report/index',
                    edit_url: 'domain/report/edit',
                    table: 'attachment'
                }
            });
            var table = $("#table");
            table.on('post-body.bs.table', function (e, json) {
                $.each(json,function(i,n){
                     var tit = n['show'].split(',');
                     var html = '<table class="layui-table" style="width:200px;margin:10px 5px;" lay-size="sm">';
                     for(var i=0; i<tit.length;i++){
                        html += '<tr><td style="padding-left:8%;">'+tit[i]+'</td><tr>';
                     }
                     html+='</table>';
                    $('#show'+n.id).on('click',function(){
                          layer.open({
                          type: 1,
                          title: '被举报域名列表',
                          width:200,
                          closeBtn: 0,
                          shadeClose: true,
                          skin: 'yourclass',
                          content: html,
                        });
                    });
                });
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'create_time',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [ 
                        {field: 'tit',operate:'LIKE', title: '域名',},
                        {field: 'num',operate:false, title: '域名数量',},
                        {field: 'uname', title: '举报人',},
                        {field: 'sfz', title: '证件号',},
                        {field: 'email', title: '邮箱',},
                        {field: 'type', title:'举报类型', formatter: Table.api.formatter.status,searchList: {1:"涉黄暴力毒品赌博",2:"传播恶意软件",3:"钓鱼网站",4:"注册信息不准确",5:"其他违法网站"},},
                        {field: 'status', title:'审核状态', sortable:true,formatter: Table.api.formatter.status,searchList: {0:"未处理",1:'域名下架',2:'域名冻结',3:'不做处理 '},notit:true},
                        {field: 'create_time',title: '举报时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'etime',title: '处理时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'ip', title: 'IP地址',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }

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
            },
        }

    };
    return Controller;
});
