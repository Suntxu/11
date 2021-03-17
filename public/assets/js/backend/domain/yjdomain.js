define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/yjdomain/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'p.id',
                sortName: 'p.updatetime',
                sortOrder: 'desc',
                escape:false,
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'p.id', title: '一口价ID',operate:false},
                        { field: 'p.tit', title: '域名信息',operate:'TEXT',
                            formatter:Table.api.formatter.alink,url:'/admin/domain/yjdomain/show/',fieldvaleu:'id',fieldname:'ids',tit:'详情',flag:'text',align:'left',fwhere:[2,1,1,2,1,2,'0|>',1],finame:['istj','is_sift','wx_check','wx_check','qq_check','qq_check','quality','special'],ys:['red','orange'],
                            pdtxt:['(推荐)','(精选)','/assets/img/domain/domain_wx_check.png','/assets/img/domain/domain_wx_check_no.png','/assets/img/domain/domain_qq_check.png','/assets/img/domain/domain_qq_check_no.png','/assets/img/domain/guarantee_domain.png','/assets/img/domain/sale_domain.png'],
                            st:['font','font','img','img','img','img','img','img'],
                        },
                        { field: 'group', title: '域名关键字',operate:'LIKE',visible:false},
                        { field: 'icp_org', title: '备案主体',width:'100px',operate:'LIKE',},
                        { field: 'p.hz', title: '后缀',visible:false, searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        { field: 'money', title: '售价(元)',operate: 'BETWEEN',sortable:true,},
                        { field: 'p.status', title: '出售状态',addClass:'ztsea',formatter: Table.api.formatter.status, notit:true, searchList:{1:'已上架',2:'已下架'}},
                        // { field: 'p.webclass', title: '建站分类',addClass:'ztsea',formatter: Table.api.formatter.status, notit:true, searchList:{0:"无",1:'独立',2:'共享'}},
                        { field: 'p.icpholder', title: '建站类型',formatter: Table.api.formatter.status, notit:true, searchList:{1:'阿里云',2:'腾讯云',3:"其他",4:"所有"},},
                        { field: 'p.icptrue', title: '建站性质',formatter: Table.api.formatter.status, notit:true, searchList:{1:'个人',2:'企业',3:'未备案',"1|2|4":"存在"},},
                        { field: 'p.wx_check', title: '微信检测', visible:false,formatter: Table.api.formatter.status, notit:true, searchList:{0:"未知",1:'未拦截',2:'拦截'},},
                        { field: 'n.uid', title: '用户名',},
                        { field: 'is_sift', visible:false, title: '精选域名',formatter:Table.api.formatter.status, searchList:{0:'普通',1:'精选'}},//
                        { field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        { field: 'p.type', title: '域名类型',addClass:'ztsea',formatter: Table.api.formatter.status, notit:true, searchList:{0:'一口价',1:'满减',9:'打包一口价'}},
                        { field: 'p.lock', title: '域名状态',addClass:'ztsea',formatter: Table.api.formatter.status, notit:true, searchList:{0:'正常',1:'hold',2:'发布中',3:'被墙',4:'冻结中'}},
                        { field: 's.flag', title: '店铺类型',formatter: Table.api.formatter.status, notit:true, searchList:{0:'普通店铺',1:'怀米网店铺',2:'消保店铺'},},
                        { field: 'p.id', title: '一口价ID',visible:false},
                        { field: 'p.txt', title: '简介',operate:'LIKE', visible:false},
                        { field: 'p.quality', title: '质保天数',formatter:Table.api.formatter.status, searchList:{0:'非质保',1:'14天',2:'30天',3:'60天'}},//
                        { field: 'p.inserttime', visible:false, title: '发布时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime,datetimeFormat:"YYYY-MM-DD"},
                        { field: 'updatetime', title: '最后更新时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime,datetimeFormat:"YYYY-MM-DD"},
                        { field: 'endtime', visible:false, title: '一口价结束时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime,datetimeFormat:"YYYY-MM-DD"},
                        { field: 'p.dqsj', title: '到期时间',operate: 'RANGE',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime,datetimeFormat:"YYYY-MM-DD"},
                    ] 
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var uid = $("#uid").val();
                    if (uid != '')
                        filter['n.uid'] = uid;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
           
            }); 
             //批量修改
            $('#btn-update').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                //拼装链接 进行点击
                $('#btn-update').attr('href','/admin/domain/editdomain/index?id='+id.join(','));
            });
            //批量转优
            $('#btn-sj').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                chang('sj',id);
            });
             //参与满减
            $('#btn-activity').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                //拼装链接 进行点击
                $('#btn-activity').attr('href','/admin/spread/elchee/participator/add?id='+id.join(','));
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
    $.each($('input[name="row[jyfs]"]'),function(i,n){
        if(n.checked){
            jyfsonc(n.value);
        }
    });
});


function jyfsonc(x){
    for(i=1;i<=3;i++){
        $('#jyfs'+i).css('display','none');
    }
    $('#jyfs'+x).css('display','');
}

$(document).on("click", ".selectl", function () {
    var id = $(this).attr('id');


    $('.ztsea option').each(function(k,v){
        if($(v).val() == id){
            $(v).prop('selected',true);
            return false;
        }else{
            return true;
        }
    });
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    });
});
//批量转优
function chang(type,id){
    $.ajax({
        url:'/admin/domain/editdomain/BtachUpdate',
        type:'post',
        data:{type:type,id:id},
        beforeSend:function(){
            layer.load(1);
        },
        success:function(data){
            layer.closeAll('loading');
            layer.msg(data);
            $('.btn-refresh').click();
        },
        error:function(){
            layer.msg('发送失败');
        },
    });
    return false;
}
$(document).on("click", ".selectl", function () {
    var id = $(this).attr('name');
    if(id == 'sift'){
        $('select[name="is_sift"]').val(1);
    }else{
        $('#n\\.uid').val(id);
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
