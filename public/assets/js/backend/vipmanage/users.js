define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/users/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'u.id',
                orderName:'desc',
                escape: false, //转义空格
                exportDataType:'all',
                pageSize:20,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'u.id', title: '用户ID',sortable:true},
                        {field: 'special_status', title:'会员账号',align:'left',operate: 'LIKE',sortable:true,formatter:Table.api.formatter.alink,url:'vipmanage/setuser/index',fieldvaleu:'id',fieldname:'id',flag:'text',tit:'会员设置',fwhere:['0'],finame:['zt'],ys:['red'],pdtxt:['[禁]'],st:['font']},
                        {field: 'u.mot', title: '手机号码',},
                        {field: 'u.uqq', title: 'QQ',},
                        {field: 's.flag', title: '店铺类型',formatter: Table.api.formatter.status,notit:true,searchList:{'null':'未开店',0:'普通店铺',2:'消保店铺',1:'怀米网店铺'},},
                        {field: 'u.zt', title: '会员状态',formatter: Table.api.formatter.status, searchList:{1:'正常使用',2:'邮箱未激活',3:'禁用',4:'安全码错误过多',5:'注销审核中',6:'已注销'},notit:true},
                        {field: 'special_condition', title: '身份证认证状态',formatter: Table.api.formatter.status, searchList:{'-1':'未认证',0:'审核中',1:'认证失败',2:'通过认证',9:'已删除'}},
                        {field: 'zflow', title: '流水总金额',operate: false,sortable:true,},
                        {field: 'xflow', title: '消费总金额',operate: false,sortable:true},
                        {field: 'u.money1', title: '账户余额',operate: 'between',sortable:true},
                        {field: 'kyye', title: '可用余额',operate:false},
                        {field: 'baomoney1', title: '保证金',operate: 'between',sortable:true},
                        {field: 'u.special', title: '会员标识',formatter: Table.api.formatter.status, notit:true, searchList:{0:'普通',1:'专属客服'}},
                        {field: 'u.sj', title: '注册时间',operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'spec', title: '是否员工推广',formatter: Table.api.formatter.status, notit:true, searchList:{0:'否',1:'是'},},
                        {field: 'u.uip', title: '注册IP',operate:'LIKE',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'group', title: '0元转回用户',visible:false,formatter: Table.api.formatter.status, searchList:{'not exists':'否',1:'是'}},
                        {field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                width:200,
                                buttons: [{
                                    name: '新前台',
                                    text: '新前台',
                                    title: '新前台',
                                    classname: 'btn btn-xs btn-ajax btn-success  ',
                                    url: '/admin/vipmanage/users/Jump?flag=new',
                                    error: function (data,ret) {
                                        if(ret.code==0){
                                            window.open(ret.weburl+'api/apioperate/goadmin?sign='+ret.token+'&uid='+ret.uid+'&time='+ret.time+'&admin_id='+ret.admin_id);
                                        }else{
                                            layer.msg(ret.msg);
                                        }
                                        return false;
                                    },
                                },{
                                    name:'解除冻结',
                                    text: '解除冻结',
                                    title:'解除冻结',
                                    classname:'btn btn-xs btn-ajax btn-warning',
                                    url:function(res){
                                        return '/admin/vipmanage/users/Unfreeze?userid='+res.id;
                                    },
                                    visible:function(res){
                                        if(res.zt == 4){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }
                                },{
                                    name:'解除异地限制',
                                    text: '解除限制',
                                    title:'解除异地限制',
                                    classname:'btn btn-xs btn-ajax btn-danger',
                                    url:function(res){
                                        return '/admin/vipmanage/users/relieveRemote?userid='+res.id;
                                    },
                                    success: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        window.setTimeout(function(){
                                            $('.btn-refresh').click();
                                        },1500);
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.msg(ret.msg);
                                        return false;
                                    }
                                },{
                                    name:'修改手机号',
                                    text: '修改手机号',
                                    title:'修改手机号',
                                    classname:'btn btn-xs btn-info',
                                    extend:function(res){
                                        return 'onclick="updateMot('+res.id+')"';
                                    },                                    
                                }],
                          },
                    ]
                ],
            });
            $(window).resize(function() {
                table.bootstrapTable('resetView', {
                    height: $(window).height() - 100
                });
            });
            //选中耨个时间 获取选择的按钮
            $('#btn-send').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                //拼装链接 进行点击
                $('#btn-send').attr('href','/admin/orderfx/msg/add?id='+id.join(','));
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

/**
 * 快速修改手机号
 */
function updateMot(id){

    layer.prompt({title: '请输入要修改的手机号', formType: 0}, function(text, index){
        layer.close(index);
        
        layer.load(1);
        $.post('/admin/vipmanage/users/modiMot',{userid:id,mot:text},function(res){
            layer.closeAll('loading');
            layer.msg(res.msg);
            if(res.code == 1){
                window.setTimeout(function(){
                    $('.btn-refresh').click();
                },1500);
            }
            console.log(res);
            return false;

        },'json');
    
    });



}
