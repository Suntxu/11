define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Form.api.bindevent($("form[role=form]"), function(){ //返回成功调用
            },function(data,ret){ //返回失败调用
                if(ret.data){
                    html = '<font style="color: red">'+ret.msg+'</font>:<br>';
                    html += ret.data.join('<br>');
                    layer.alert(html);
                }
            },function(){ //提交之前调用
            });
        },
    };
    return Controller;
});
function getApiRadio(self){
    var html = '<label for="apiw"><input id="apiw" value="" checked name="api_id" type="radio">无</label>  ';
    var id = $(self).val();
    layer.load(1);
    $.ajax({
        url:'domain/Updatezcs/getApi',
        type:'post',
        data:{regid:id},
        dataType:'json',
        success:function(data){
            layer.closeAll('loading');
            if(data.code == 0){
                $.each(data.res,function(i,n){
                    html += '<label for="api_'+i+'"><input id="api_'+i+'" value="'+n.id+'" name="api_id" type="radio">'+n.tit+'</label>  ';
                });
            }
            $('#ref').html(html);
        }
    });
}
