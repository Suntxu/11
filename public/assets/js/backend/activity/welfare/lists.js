define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/welfare/lists/index',
                    add_url: 'activity/welfare/lists/add',
                    edit_url: 'activity/welfare/lists/edit',
                    // del_url: 'activity/welfare/lists/del',
                    table: 'user',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'sort',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'title', title:'标题',},
                        {field: 'suffix', title: '后缀',operate:'IN',searchList: $.getJSON('domain/manage/getDomainHz'),addclass:'request_selectpicker'},
                        {field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'original_cost', title:'注册原价格',operate:'BETWEEN',sortable:true},
                        {field: 'cost', title:'注册成本价格',operate:'BETWEEN',sortable:true},
                        {field: 'start_time', title: '福利开始时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: '福利结束时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: '状态',formatter:Table.api.formatter.status,searchList:{0:'开启',1:'关闭'},custom:{'开启':'success','关闭':'danger'},},
                        {field: 'group', title: '是否过期',formatter:Table.api.formatter.status,searchList:{1:'未过期',2:'已过期'},custom:{'未过期':'success','已过期':'danger'},},
                        {field: 'create_time', title: '创建时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        {field: 'sort', title: '序号',operate:false,sortable:true},
                        {field: 'Operate', title: __('Operate'),operate:false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,

                              formatter: function (value, row, index) {
                                    var that = $.extend({}, this);
                                    // if(row.flag === 0){
                                    //     $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                    // }else{
                                        $(table).data("operate-edit", true);
                                    // }
                                    that.table = table; 
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                        },
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

$(function(){
    //自动选择注册价格
    $('#suffix').on('change',function(){
        if(this.value){
            $('#hd_title1').val($('#suffix option:selected').data('price'));
            $('#hd_title2').val($('#suffix option:selected').data('cost'));
        }else{
            $('#hd_title1').val(0);
            $('#hd_title2').val(0);
        }

    });

});