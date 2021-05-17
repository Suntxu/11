define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/issue/illegal/index',
                    add_url: 'total/issue/illegal/add',
                    del_url: 'total/issue/illegal/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                escape:false,
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'group', title: '归属用户',},
                        { field: 'create_time', title: '添加时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        { field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: '用户信息',
                                    text: '用户信息',
                                    title: '用户信息',
                                    classname: 'btn btn-xs add_info btn-primary  btn-dialog',
                                    url: function(data){
                                        return '/admin/total/issue/illegal/show?userid='+data.userid+'&tit='+data.tit;
                                    },
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }
                                },

                                {
                                    name: '充值记录',
                                    text: '充值记录',
                                    title: '充值记录',
                                    classname: 'btn btn-xs btn-success',
                                    url: function(data){
                                        return '/admin/total/issue/illegal/exportInfo?type=1&userid='+data.userid+'&tit='+data.tit;
                                    },
                                    visible:function(res){
                                        return true;
                                    },
                                },
                                {
                                    name: '解析记录',
                                    text: '解析记录',
                                    title: '解析记录',
                                    classname: 'btn btn-xs btn-success',
                                    url: function(data){
                                        return '/admin/total/issue/illegal/exportInfo?type=2&userid='+data.userid+'&tit='+data.tit;
                                    },
                                    visible:function(res){
                                        return true;
                                    },
                                },
                                {
                                    name: '个人信息',
                                    text: '个人信息',
                                    title: '个人信息',
                                    classname: 'btn btn-xs btn-success',
                                    url: function(data){
                                        return '/admin/total/issue/illegal/exportInfo?type=3&userid='+data.userid+'&tit='+data.tit;
                                    },
                                    visible:function(res){
                                        return true;
                                    },
                                },
                            ],
                        }
                    ]
                ],
            });
            $(document).on("click", ".btn-rechargerecord", function () {
                var style = this.getAttribute('btn-style');
                if (style == 'rechargerecord') {
                    var type = 1;
                }else if(style == 'analysisrecord'){
                    var type = 2;
                }else{
                    var type = 3;
                }
                $.each(table.bootstrapTable('getSelections'),function(index ,row){
                    var url = '/admin/total/issue/illegal/exportInfo?userid=' + row.userid + '&tit=' + row.tit+'&type='+type;
                    var elemIF = document.createElement("iframe");
                    elemIF.src = url;
                    elemIF.style.display = "none";
                    document.body.appendChild(elemIF);
                })
            }),

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
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



