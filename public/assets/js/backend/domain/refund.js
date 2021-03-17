define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Form.api.bindevent($("form.edit-form"));
            //不可见的元素不验证
            $("form#add-form").data("validator-options", {ignore: ':hidden'});
            Form.api.bindevent($("form#add-form"), null, function (ret) {
                location.reload();
            });
        },
    };
    return Controller;
});

/**
 * 退款
 */
function refund(){
    domains = $('#bh').val();
    if(!domains){
        layer.msg('请输入域名');
        return false;
    }
    layer.confirm('确定要对<font color="red">'+$('#total').text()+'</font>个域名进行退款操作吗', {
      btn: ['确定','取消'] //按钮
    }, function(){
        layer.load(1);
        $.post('/admin/domain/refund/opdomain',{domain:domains},function(data){
            layer.closeAll('loading');
            layer.msg(data.msg);
            if(data.code == 0){
                window.parent.location.reload();
            }

        },'json');
    }, function(){
      
    });



}