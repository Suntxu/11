define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'domain/Manage/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            table.on('post-body.bs.table', function (e, json) {

                $('.domain_status').each(function(){
                    var tit = $(this).attr('id').replace('bbc_','');
                    $.post('/admin/domain/manage/queryDomainStatys',{tit:tit},function(data){
                        $('#bbc_'+tit.replace(/\./g,'\\\.')).html(data.msg);
                    },'json');
                });
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'dqsj',
                escape:false,
                exportDataType:'',
                pageSize:20,
                pageList:[20,50,100,200,300,1000,2000,5000,'All'],
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'p.tit', title: '域名信息',operate:'TEXT', formatter:Table.api.formatter.alink,url:'/admin/domain/Manage/show/',fieldvaleu:'id',fieldname:'ids',tit:'详情',},
                        { field: 'p.zt', title: '发布状态',formatter: Table.api.formatter.status, notit:true, searchList:{1:'发布一口价',2:'打包一口价',4:'push域名中',5:'转回原注册商',6:'域名回收',9:'正常状态'}},
                        { field: 'pstatu', title: '域名状态',operate:false},
                        { field: 'p.status', title: '冻结状态',formatter: Table.api.formatter.status, notit:true, searchList:{0:'正常',4:'冻结中'}},
                        { field: 'p.infoZR', title: '过户状态',formatter: Table.api.formatter.status, notit:true, searchList:{0:'未过户',1:'过户成功',2:'过户失败',3:'过户中',4:'过户成功,实名失败'}},
                        { field: 'uid', title: '所属用户',},
                        { field: 'hz', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        { field: 'inserttime', title: '入库时间',operate: 'INT',addclass: 'datetimerange',sortable:true, formatter: Table.api.formatter.datetime},
                        { field: 'p.zcsj', title: '注册时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,},
                        { field: 'p.dqsj', title: '到期时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,},
                        { field: 'p.dtype', title: '类型',operate: 'RLIKE',searchList: $.getJSON('domain/manage/getDomainType'),},
                        { field: 'zcs', title: '注册商', searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        { field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        { field: 'p.special', title: '入库类型',formatter: Table.api.formatter.status, notit:true, searchList:{0:'普通',1:'转入',2:'预定',3:'预释放'}},
                        { field: 'p.parse_status', title: '云解析添加状态',formatter: Table.api.formatter.status, notit:true, searchList:{0:'入库中',1:'添加成功',2:'添加失败'}},
                        { field: 'out_zcs', title: '外部注册商',searchList: $.getJSON('/admin/domain/manage/getOutZcsList')},
                        { field:'group',title:'过期状态', formatter: Table.api.formatter.status, notit:true, searchList:{1:'未过期',2:'已过期',3:'赎回期'}}
                    ]
                ],
            });
            //赎回期查询
            $('.shuhui').click(function(){
                var type = $(this).data('type');
                if (type    === 1)
                {
                    $(this).css({"background": "#66CC33"});
                    $('#what2').css({"background": "#f39c12"});
                    $('#what3').css({"background": "#e74c3c"});
                    $('#what4').css({"background": "#2c3e50"});
                }else if (type===2)
                {
                    $(this).css({"background": "#FFCC00"});
                    $('#what1').css({"background": "#18bc9c"});
                    $('#what3').css({"background": "#e74c3c"});
                    $('#what4').css({"background": "#2c3e50"});
                }else if(type===3)
                {
                    $(this).css({"background": "#FF0000"});
                    $('#what1').css({"background": "#18bc9c"});
                    $('#what2').css({"background": "#f39c12"});
                    $('#what4').css({"background": "#2c3e50"});
                }else
                {
                    $(this).css({"background": "#000000"});
                    $('#what1').css({"background": "#18bc9c"});
                    $('#what2').css({"background": "#f39c12"});
                    $('#what3').css({"background": "#e74c3c"});
                }


                $('select[name="group"]').val(type);
                $('.btn-success').each(function(i,n){
                    if($(n).attr('formnovalidate') != 'undefined'){
                        $(n).click();
                        return false;
                    }else{
                        return true;
                    }
                });
            });
            //拼接链接查询
            $('.btn-operate').click(function(){
                url = $(this).data('url');
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                $(this).attr('href',url+'?id='+id.join(','));
            });
           
            // 冻结 解冻
            $('.btn-freeze').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                var status = $(this).data('type');

                if(status == 2){ //冻结原因
                  layer.prompt({title: '请输入冻结原因', formType: 2}, function(text, index){
                    layer.close(index);
                    layer.load();
                    $.post('/admin/domain/manage/updateStatus',{id:id,status:status,remark:text},function(data){
                        layer.closeAll('loading');
                        layer.msg(data.msg);
                        $('.btn-refresh').click();
                    })
                  });
                }else{
                    layer.load();
                    $.post('/admin/domain/manage/updateStatus',{id:id,status:status},function(data){
                        layer.closeAll('loading');
                        layer.msg(data.msg);
                        $('.btn-refresh').click();
                    })
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
        show: function () {
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
