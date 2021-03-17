define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/reserve/domainreserve/index',
                    // edit_url:'domain/reserve/domainreserve/edit',
                    table: 'user',
                }
            });
            var table = $("#table");
            // $('#multire').on('click',function () {
            //     var temp = table.bootstrapTable('getSelections');
            //     var ids = '';
            //     for(i in temp){
            //         ids += ','+temp[i].id;
            //     }
            //     $('#multire').attr('href','/admin/domain/reserve/domainreserve/multire?id='+ids.substr(1));
            // });
            //在表格内容渲染完成后回调的事件
            // table.on('post-body.bs.table', function (e, json) {
            //     $("tbody tr[data-index]", this).each(function (i,n) {
            //         var stat = $(n).children().eq(6).text();
            //         stat = stat.trim();
            //         if(stat != '进行中' && stat != '已提交'){
            //             $("input[type=checkbox]",this).prop("disabled", true);
            //         }
            //     });
               // $.each(json,function(i,n){
               //      if(n.flag){
               //          $($('#table tr').get(i+1)).children('td:last-child').html('<font style="color:orange">任务执行中</font>');
               //      }
               // });
            // });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.time desc,r.status asc',
                escape: false, //转义空格
                columns: [
                    [
                        // { checkbox: true, title: '选择' },
                        { field: 'r.tit', title: '域名',operate:'TEXT',formatter:Table.api.formatter.alink,url:'/admin/domain/reserve/orders/index',fieldvaleu:'tit',fieldname:'r.tit',tit:'预定订单',},
                        { field: 'group', title: '后缀',searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'r.money', title: '订单金额',sortable:true,operate:'BETWEEN'},
                        { field: 'renshu', title:'参与人数',operate:false,sortable:true},
                        { field: 'i.del_time', title: '域名删除时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'status', title: '订单状态',operate:false,},
                        { field: 'r.pstatus', title: '交割状态',formatter: Table.api.formatter.status,searchList: {'0':'未支付','1':'未交割','2':'交割失败','3':'已交割','4':'违约'},notit:true},
                        // { field: 'special_condition', title: '处理状态',formatter: Table.api.formatter.status,searchList:{1:'未处理',2:'已处理'},notit:true},
                        { field: 'r.api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),visible:false},
                        { field: 'r.time', title: '订单创建时间',addclass:'datetimerange',sortable:true,operate:'INT',formatter: Table.api.formatter.datetime},
                        { field: 'r.endtime', title: '订单结束时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
            });
            
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        // multire:function(){
        //     $('#domains').on('blur',function(){
        //         var status = $('input[name="row[status]"]:checked').val();
        //         if(status == 1 || !status){
        //             checkau();
        //         }else{
        //             $('.auction').css('display','none');
        //         }
        //     });
        //     Form.api.bindevent($("form[role=form]"), function(data, ret){},function(data,ret){
        //         if(ret.code == 300){
        //             $('.auction').css('display','');
        //         }
        //     });
        // },
        edit: function () {
            Form.api.bindevent($("form[role=form]"), function(data, ret){},function(data,ret){
                if(ret.code == 300){
                    $('.auction').css('display','');
                }
            });
        },
    };
    return Controller;
});


// $(function(){
//     getApp($('#zcs'));
//     $('input[name="row[status]"]').click(function(){
//         //是否显示竞拍参数
//         if($('input[name="row[status]"]:checked').val() == 1){
//             checkau();
//             if($(this).data('bbu') > 1){
//                 $('.auction').css('display','');
//             }
//             $('.yudin').css('display','');
//         }else{
//             $('.auction').css('display','none');
//             $('.yudin').css('display','none');
//         }
//     });
// });
// function getApp(self){
//     var id = $(self).val();
//     if(id == 0){
//         $('#ref').html("<option>请选择</option>");
//         return true;
//     }
//     $.ajax({
//         url:'domain/store/save/getApi',
//         type:'post',
//         data:{id:id},
//         dataType:'json',
//         success:function(data){
//             if(data.code ==1){
//                 var op = '<option value="">'+data.msg+'</option';
//             }else{
//                 var op = '';
//                 $.each(data.res,function(i,n){
//                     op+='<option value="'+n.id+'">'+n.tit+'</option>';
//                 });
//                 $('#ref').html(op);
//             }
//         }
//     });
// }
// /**
//  * 检测域名是否含有竞价域名
//  */
// function checkau(){
//     var domains = $('#domains').val();
//     if(domains){
//         $.ajax({
//             url:'/admin/domain/reserve/domainreserve/checkUpod',
//             data:{domains:domains},
//             async:false,
//             type:'post',
//             dataType:'json',
//             success:function(ret){
//                 if(ret.code == 1){
//                     $('.auction').css('display','');
//                 }else{
//                     $('.auction').css('display','none');
//                 }
//             }
//         });
//     }else{
//         $('.auction').css('display','none');
//      }
// }