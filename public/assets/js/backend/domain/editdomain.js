define(['jquery','form'], function ($, Form) {

    var Controller = {
        index: function () {
           
            Form.api.bindevent($("form.edit-form"));
            //不可见的元素不验证
            $("form#add-form").data("validator-options", {ignore: ':hidden'});
            Form.api.bindevent($("form#add-form"), null, function (ret) {
                location.reload();
            });
            //添加向发件人发送测试邮件按钮和方法
            $('input[name="row[mail_from]"]').parent().next().append('<a class="btn btn-info testmail">' + __('Send a test message') + '</a>');
            $('.icptrue').click(function(){
                if(this.value == 3 || (this.value == 'icptrue' &&  this.checked )){
                    $('#domain_type61').prop('checked',true);
                }else{
                    $('#domain_type61').prop('checked',false);
                }
            });

        },
    };
    return Controller;
});
$(function(){
    $('input[name="row[operate]"]').click(function(){
        if(this.value == 1){
            $('#edit').hide();
        }else{
            $('#edit').show();
        }
    });

});
