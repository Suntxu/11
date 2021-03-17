define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            Form.api.bindevent($("form[role=form]"),function(info,res){
                if(res.code == 1){
                    setTimeout(function(){
                        location.reload();
                    },1000);
                }
            });
        
        },
    };
    return Controller;
});