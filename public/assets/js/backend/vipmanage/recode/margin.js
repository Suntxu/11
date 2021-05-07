define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/margin/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'margin',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'sj desc,id desc',
                pageSize: 25,
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'uid', title: '用户名',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/setuser/index',fieldvaleu:'userid',fieldname:'id',tit:'用户设置',},
                        {field: 'tit', title: '标题',},
                        {field: 'moneynum', title: '冻结金额',operate:false,sortable:true},
                        {field: 'status', title: '状态',sortable:true, formatter:Table.api.formatter.status,searchList:{0:'冻结中',1:'扣除',2:'还原'},custom:{'冻结中':'info','扣除':'danger','还原':'success'},},
                        {field: 'type', title: '冻结类型',sortable:true,formatter:Table.api.formatter.status,searchList:{0:'系统扣除',1:'转回原注册商',2:'提现',3:'发票申请',4:'店铺保证金',5:'域名预订',6:'域名竞价',7:'域名竞拍额外冻结资金',8:'预释放',9:'拼团',10:'批量注册',12:'域名续费',13:'委托购买'},custom:{'系统扣除':'danger','转回原注册商':'danger','提现':'pink','发票申请':'gray','店铺保证金':'danger','域名预订':'danger','域名竞价':'orange','域名竞拍额外冻结':'warning','预释放':'success','拼团':'red','域名注册冻结':'red','域名续费':'orange','委托购买':'orange'},},
                        {field: 'b.sj', title: '添加时间',sortable:true,operate: 'RANGE', addclass: 'datetimerange',defaultValue:getTimeFrame()},
                        {field: 'sremark', title: '说明',operate:false,},
                        {field: 'uip', title: '用户IP',operate:false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'l.uip',fieldname:'wd',tit:'Ip归属地查询',},
                        // {field: 'showurl', title: '操作',operate:false,notit:true},
                        {field: 'showurl', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '退还保证金',
                                    text: '退还',
                                    title: '退还保证金',
                                    classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: '/admin/vipmanage/recode/margin/policy',
                                    confirm:'是否确定此操作?',
                                    success: function (data,ret) {
                                        if(ret.code==1){
                                            $('.btn-refresh').click();
                                        }else{
                                            layer.msg(ret.msg);
                                        }
                                        return false;
                                    },
                                },{
                                    name: '扣除保证金',
                                    text: '扣除',
                                    title: '扣除保证金',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: '/admin/vipmanage/recode/margin/deduct',
                                    confirm:'是否确定此操作?',
                                    success: function (data,ret) {
                                        if(ret.code==1){
                                            $('.btn-refresh').click();
                                        }else{
                                            layer.msg(ret.msg);
                                        }
                                        return false;
                                    },
                                }],
                                formatter:function(value,row,index){
                                    var that = $.extend({},this);
                                    if(row['status'] == '冻结中' && row['type'] == '系统扣除' ){
                                        return Table.api.formatter.operate.call(that,value,row,index);
                                    }else{
                                        return row['showurl'];
                                    }
                                }
                          },
                       
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['b.id'] = id;
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


