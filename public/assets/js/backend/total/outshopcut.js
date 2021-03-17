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
 * 导出域名
 */
function exportDomain(type){
    var domain = $('#bh').val();
    if(!domain){
        layer.msg('请输入域名');
        return false;
    }
    $('#edomain').val(domain);
    $('#etype').val(type);
    $('#ebtn').click();
}