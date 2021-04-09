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

