define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/usersop/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'id',
                orderName:'desc',
                escape: false, //转义空格
                exportDataType:'all',
                pageSize:20,
                columns: [
                    [
                        {field: 'id', title: '用户ID',sortable:true},
                        {field: 'group', title:'会员账号',operate: 'LIKE',},
                        {field: 'mot', title: '手机号码',visible:false},
                        {field: 'uqq', title: 'QQ',},
                        {field: 'zt', title: '会员状态',formatter: Table.api.formatter.status, searchList:{1:'正常使用',2:'邮箱未激活',3:'禁用',4:'安全码错误过多',5:'注销审核中',6:'已注销'},notit:true},
                        {field: 'money1', title: '账户余额',operate: 'between',sortable:true},
                        {field: 'baomoney1', title: '保证金',operate: 'between',sortable:true},
                        {field: 'balance', title: '可用余额',operate: false,sortable:true},
                        {field: 'uip', title: '注册IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'operate', title: __('Operate'), table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [{
                                    name: '新前台',
                                    text: '新前台',
                                    title: '新前台',
                                    classname: 'btn btn-xs btn-ajax btn-success  ',
                                    icon: 'fa fa-magic',
                                    url: '/admin/vipmanage/usersop/Jump?flag=new',
                                    error: function (data,ret) {
                                        if(ret.code==0){
                                            window.open(ret.weburl+'api/apioperate/goadmin?sign='+ret.token+'&uid='+ret.uid+'&time='+ret.time+'&admin_id='+ret.admin_id);
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

//点击修改排序字段
function updatePm(self){

  var oldhtml = self.value;
  var id = self.id;
  if(oldhtml==0 || parseInt(oldhtml)){
     $.ajax({
        'type':'post',
        'data':{id:id,pm:oldhtml},
        'url':'/admin/vipmanage/users/updatePm',
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
  }else{
    layer.msg('请输入有效值');
    return false;
  }
}
$(document).on("click", ".selectl", function () {
    var id = $(this).attr('id');
    var m = id.substr(id.length-1,1);
    if(m == 6){ 
        $('#u\\.sj').val('');
    }else{
        $('.ranges ul').first().children('li').get(m).click();
    }
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    });
});

