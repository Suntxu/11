define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {

            //自定义 默认选中第一个标签
            $(function(){
                $.each($('input[name="row[shopzt]"]'),function(i,n){
                    if(n.checked){
                        hiddenYs('shop_bj',n,'4');
                    }
                });
                //实名认证
                $.each($('input[name="row[sfzrz]"]'),function(i,n){
                    if(n.checked){
                        hiddenYs('sfzrz',n,'3');
                    }
                });
                 //专属客服
                $.each($('input[name="row[special]"]'),function(i,n){
                    if(n.checked){
                         hiddenYs('special',n,'1');
                    }
                });
            })
             // 给上传按钮添加上传成功事件
            $("#plupload-avatar").data("upload-success", function (data) {
                var url = Backend.api.cdnurl(data.url);
                $(".profile-user-img").prop("src", url);
                
                Toastr.success("上传成功！");
            });
            
            Form.api.bindevent($("form.edit-form"));
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
/**
 * 
 */
function hiddenYs(id,self,str=''){
    if(self.value == str){
        $('#'+id).css('display','');
    }else{
        $('#'+id).css('display','none');
        $('#'+id+'1').val('');
    }
}
/**
 * 点击专属客服
 */
function servire(self){
    console.log(self.checked);
    if(self.checked){
        $('#special').css('display','');
    }else{
        $('#special').css('display','none');
    }
}


