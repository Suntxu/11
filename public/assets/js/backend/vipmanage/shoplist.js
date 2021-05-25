define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/shoplist/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 't1.sj',
                orderName:'desc',
                escape:false,
                columns: [
                    [  
                        // { field: 't1.shopid', title: '店铺ID',},
                        { field: 't1.shopname', title: '店铺名称',operate:'LIKE',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            },formatter:Table.api.formatter.alink,url:'vipmanage/shoplist/modi',fieldvaleu:'t1.userid',fieldname:'ids',tit:'编辑', },
                        { field: 't1.userid', title: '用户ID'},
                        { field: 't3.uid', title: '账号',},
                        { field: 'special_condition', title: '默认店铺号',},
                        { field: 't1.shopzt', title: '店铺状态',formatter: Table.api.formatter.status,searchList:{1:'正常开店',2:'正在审核',3:'禁用',4:'审核被拒'},notit:true,},
                        { field: 't1.flag', title: '店铺类型',formatter: Table.api.formatter.status,notit:true,searchList:{0:'普通店铺',2:'消保店铺',1:'怀米网店铺'},},
                        { field: 'sellernum', title: '一口价域名(在售)',operate:false,sortable:true,formatter:Table.api.formatter.alinks,url:'/admin/domain/yjdomain/',fieldvaleu:['t3.uid','1',],fieldname:['uid','p.status'],tit:'一口价在售域名',},
                        { field: 'ysellernum', title: '一口价销量',operate:false,sortable:true,
                            footerFormatter: function (data) {
                                var field = 'geshu';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(0);
                            },
                        formatter:Table.api.formatter.alinks,url:'/admin/domain/tradeddomain/',fieldvaleu:['t3.uid','upaytime'],fieldname:['s.uid','c.paytime'],tit:'一口价成交域名',},
                        { field: 'sellermoney', title: '销量金额(元)',
                         footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            },
                        operate:false,sortable:true,},
                        { field: 'pn', title: '购买人数',operate:false,
                            footerFormatter: function (data) {
                                var field = 'zbuy';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum;
                            }
                        },
                        { field: 't1.pm', title: '店铺推荐',operate:false,sortable:true,formatter:Table.api.formatter.onclk,fieldname:'t1.pm',hz:true,affair:'onblur="updatePm(this)"',},
                        { field: 'group', title: '交易时间',operate:'RANGE', visible: false,addClass:'datetimerange selll'},
                        { field: 't1.deposit', title: '保证金',operate:'BETWEEN'},
                        {field: 'operate', title: __('Operate'), table: table,width:230,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '店铺号管理',
                                    text: '店铺号管理',
                                    title: '店铺号管理',
                                    classname: 'btn btn-xs btn-success btn-magic dialogit',
                                    icon: 'fa fa-ge fa-fw',
                                    url: function(res){
                                       return '/admin/vipmanage/shoplist/account?u.uid='+res['t3.uid']+'&shopname='+res['t1.shopname'];
                                    },
                                },{
                                    name: '店铺主页',
                                    text: '店铺主页',
                                    title: '店铺主页',
                                    classname: 'btn btn-xs btn-info btn-magic',
                                    icon: 'fa fa-hand-stop-o fa-fw',
                                    extend:'target="_blank"',
                                    visible:function(res){
                                        return res.special_condition > 0;
                                    },
                                    url: function(res){
                                        return res['wwwurl']+res['special_condition'];
                                    },
                                },{
                                    name: '店铺关联',
                                    text: '店铺关联',
                                    title: '店铺关联',
                                    classname: 'btn btn-xs btn-info btn-magic dialogit',
                                    icon: 'fa fa-hand-stop-o fa-fw',
                                    visible:function(res){
                                        return res.rel;
                                    },
                                    url: function(res){
                                        return '/admin/vipmanage/shoplist/relevance?u.uid='+res['t3.uid'];
                                    },
                                },{
                                    name: '退保',
                                    text: '退保',
                                    title: '退保',
                                    classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: '/admin/vipmanage/shoplist/audit',
                                    confirm:'是否确定此操作?',
                                    visible:function(res){
                                        if(res['t1.flag'] == '消保店铺' && res['t1.shopzt'].indexOf('正常开店') != -1 ){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    success: function (data,ret) {
                                        if(ret.code==1){
                                            $('.btn-refresh').click();
                                        }else{
                                            layer.msg(ret.msg);
                                        }
                                        return false;
                                    },
                                }],
                          },
                    ] 
                ],
            }); 
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        account: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/shoplist/account',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'a.id',
                orderName:'desc',
                escape: false,
                columns: [
                    [  
                        { field: 'shopname', title: '店铺名称',operate:'LIKE'},
                        { field: 'account', title: '店铺号',},
                        { field: 'u.uid', title: '用户名',},
                        { field: 'a.create_time', title: '创建时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        { field: 'a.endtime', title: '到期时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        { field: 'a.status', title: '状态',searchList:{0:'正常',1:'禁用'},},
                        { field: 'is_default', title: '是否为主账号',formatter: Table.api.formatter.status,notit:true,searchList:{0:'否',1:'是'},},
                        { field: 'gain_type', title: '账号类型',formatter: Table.api.formatter.status,notit:true,searchList:{0:'默认开通',1:'合作方'},},
                        { field: 'remark', title: '备注',operate:false,},
                        { field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    title: '启用',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-pencil',
                                    confirm: '确定要启用该店铺账号吗?',
                                    url: function(res){
                                        return '/admin/vipmanage/shoplist/accountmodi?id='+res.id+'&status='+res.status;
                                    },
                                    visible:function(res){
                                        if(res.gain_type == '默认开通' || res.status == 0){
                                            return false;
                                        }else{
                                            return true;
                                        }
                                    },
                                    success: function (data,ret) {
                                        layer.msg(ret.msg);
                                        if(ret.code==1){
                                            $('.btn-refresh').click();
                                        }
                                        return false;
                                    },
                                },{
                                    title: '禁用',
                                    classname: 'btn btn-xs btn-warning btn-magic',
                                    icon: 'fa fa-pencil',
                                    extend:function(res){
                                        var url = '/admin/vipmanage/shoplist/accountmodi?id='+res.id+'&status='+res.status;
                                        return 'onclick="mode(\''+url+'\')"';
                                    },
                                    visible:function(res){
                                        if(res.gain_type == '默认开通' || res.status == 1){
                                            return false;
                                        }else{
                                            return true;
                                        }
                                    }
                                },{
                                    title: '删除',
                                    classname: 'btn btn-xs btn-danger',
                                    icon: 'fa fa-trash',
                                    extend:function(res){
                                        var url = '/admin/vipmanage/shoplist/accountdel?id='+res.id;
                                        return 'onclick="mode(\''+url+'\')"';
                                    },
                                    visible:function(res){
                                        if(res.gain_type == '默认开通'){
                                            return false;
                                        }else{
                                            return true;
                                        }
                                    }
                                }],
                          },
                    ] 
                ],
            }); 
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        relevance: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/shoplist/relevance',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'r.id',
                orderName:'asc',
                escape: false,
                columns: [
                    [
                        { field: 'u.uid', title: '用户'},
                        { field: 'u1.uid', title: '被关联用户'},
                        { field: 'relevance_account', title: '被关联店铺号',},
                        { field: 'r.create_time', title: '关联时间',operate: 'INT',sortable:true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        { field: 'r.status', title: '状态',searchList:{0:'正常',1:'禁用'},},
                        { field: 'rel_status', title: '关联状态',searchList:{0:'关联中',1:'取消'},},
                        { field: 'remark', title: '备注',operate:false},
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        modi: function () {
            Controller.api.bindevent();
        },
        accountadd: function(){
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
    $.each($('input[name="row[jyfs]"]'),function(i,n){
        if(n.checked){
            jyfsonc(n.value);
        }
    });
});
//ajax 点击修改排序
function updatePm(self){
   var oldhtml = self.value;
   var id = self.id;
   var field = self.title;
    $.ajax({
        'type':'post',
        'data':{'id':id,field:field,val:oldhtml},
        'url':'/admin/vipmanage/shoplist/updatePm',
        success:function(data){
            layer.closeAll('loading');
            self.value = data;
        },
        error:function(){
            alert('发送失败');
        },
        beforeSend:function(){
            layer.load();
        }
    });
}
/**
 * 
 */
function hiddenYs(id,self,str=''){
    if(self.value == str){
        $('#'+id).css('display','');
    }else{
        $('#'+id).css('display','none');
        $('#'+id+'1').val('');
    }
}
function jyfsonc(x){
    for(i=1;i<=3;i++){
        $('#jyfs'+i).css('display','none');
    }
    $('#jyfs'+x).css('display','');
}

/**
 * 修改状态
 */
function mode(url){

    layer.prompt({title: '请输入操作备注', formType: 2}, function(text, index){
        layer.close(index);
        layer.load();
        $.get(url,{remark:text},function(res){
            layer.closeAll('loading');
            layer.msg(res.msg);
            if(res.code==1){
                $('.btn-refresh').click();
            }

        },'json');

    });

}
