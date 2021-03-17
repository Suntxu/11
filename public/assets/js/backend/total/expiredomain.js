define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'total/expiredomain/index',
                    table: 'attachment'
                }
            });

            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'userid',
                orderName:'asc',
                escape: false,
                exportDataType:'all',
                columns: [
                    [ 
                        {field: 'group', title: '用户名',},
                        {field: 'mot', title: '电话',operate:false},
                        {field: 'hz', title: '后缀',searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        {field: 'num', title: '域名数量',operate:false,sortable:true},
                        {field: 'dqsj',title: '到期时间',addclass: 'datetimerange',operate:'RANGE', visible:false,},
                    ]
                ],
            });
            
            Table.api.bindevent(table);
        },
    };
    return Controller;
});
/**
 * 过期时间搜索
 */
function dateSearch(day){

    if(day == -1){
        $('#dqsj').val('');

    }else{
        
        var date = getDateExpire(day);
        var today = getDateExpire(0);

        var sj = (today + ' 00:00:00') +' - '+ (date + ' 23:59:59');

        $('#dqsj').val(sj);
    }
    
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    }); 
}
