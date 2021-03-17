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
function checkedPack(){
    var domain = $('#domain').val();
    if(!domain){
        layer.msg('请输入要出库的域名！');
        return false;
    }else{
        layer.load(0);
        $.ajax({
            url:'/admin/domain/store/del/checkSelfDomain',
            type:'post',
            data:{domain:domain},
            dataType:'json',
            async:false,
            success:function(res){
                if(res.code == 0){
                    if(res.self){ // 自己的域名
                        var html = '';
                        for(var i in res.self){
                            html += '<tr><td>' + res.self[i] + '</td></tr><br>';
                        }
                        if(html){
                            layer.confirm('<font style="color:red">此批域名含有非自己账户的域名,是否要确定要出库?</font><br>'+html, { //检测域名是否属于自己的
                              btn: ['是','否'] //按钮
                            }, function(index){
                                layer.close(index);
                                // 检测域名是否打包域名
                                check(domain);
                            }, function(){
                                layer.closeAll('loading');
                                layer.msg('您已放弃了本次操作!');
                                return false;
                            });
                        }else{
                            check(domain);
                        }
                    }
                }else{
                    layer.closeAll('loading');
                    layer.msg(res.msg);
                    return false;
                }
            },
            error:function(){
                layer.msg('发送失败');
                return false;
            }
        });
    }
    return false;
}

//检测 是否是自己用户的域名
function check(domain){
    $.post('/admin/domain/store/del/checkpack',{domain:domain},function(res){ //检测域名是否打包
            if(res.code == 1){
                layer.closeAll('loading');
                layer.msg(res.msg);
                return false;
            }else{
                $('#ttxt').val(res.data.join(','));
                if(res.msg == 'ok'){
                    // 无打包域名
                   $('#edit-form').submit();
                   return true;
                }else{
                    layer.confirm('您要出库的域名含有打包的域名,是否要解除打包并出库?', {
                      btn: ['是','否'] //按钮
                    }, function(){
                        $('#pack').val(1);
                        $('#edit-form').submit();
                        return true;
                    }, function(){
                        layer.closeAll('loading');
                        layer.msg('您已放弃了本次操作!');
                        return false;
                    });
                }
            }
        },'json');
}