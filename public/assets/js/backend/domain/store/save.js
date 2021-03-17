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
$(function(){
    getApp($('#zcs'));
});
function getApp(self){
    var html = '<label for="apiw"><input id="apiw" value="" checked name="appid" type="radio">无</label>  ';
    var id = $(self).val();
    if(id == 0){
        $('#ref').html(html);
        $('#c-category_id').val('201055555@qq.com');
        return true;
    }else
    if(id != 71){
        $('#c-category_id').val('201055555@qq.com');
    }else{
        $('#c-category_id').val('twzhuanyong@163.com');
    }
    $.ajax({
        url:'domain/store/save/getApi',
        type:'post',
        data:{id:id},
        dataType:'json',
        success:function(data){
            if(data.code ==1){
                html += '<label for="apie"><input id="apie"  name="appid" type="radio">'+data.msg+'</label>';
            }else{
                $.each(data.res,function(i,n){
                    html += '<label for="api_'+i+'"><input id="api_'+i+'" value="'+n.id+'" name="appid" type="radio">'+n.tit+'</label>  ';
                });
                $('#ref').html(html);
            }
        }
    });
}
