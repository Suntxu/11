define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $("form.edit-form").data("validator-options", {
                display: function (elem) {
                    return $(elem).closest('tr').find("td:first").text();
                }
            });
            Form.api.bindevent($("form.edit-form"));
        },
    };
    return Controller;
});
$(function(){
    getApp($('#zcs'));
});
function getApp(self){
    var id = $(self).val();
    $.ajax({
        url:'domain/store/save/getApi',
        type:'post',
        data:{id:id},
        dataType:'json',
        success:function(data){
            if(data.code ==1){
                var op = '<option value="">'+data.msg+'</option';
            }else{
                var op = '';
                $.each(data.res,function(i,n){
                    op+='<option value="'+n.id+'">'+n.tit+'</option>';
                });
                $('#ref').html(op);
            }
        }
    });
}