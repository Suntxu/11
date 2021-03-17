define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
        }
    };

    return Controller;
});

// 推广链接
function copeurl(id,oldurl,cid,type='') {
    var nurl = $('#'+id).val();
    if(nurl == ''){
        var url = oldurl;
    }else{
        var url = nurl;
    }
    $.ajax({
        type:'post',
        url:'/admin/staffuse/dashboard/copeurl',
        data:{cid:cid,url:url},
        async:false,
        dataType:'json',
        beforesend:function(){
            layer.load(2);
        },
        success:function(data){
            layer.close('loading');
            if(data.code == 0){
                document.getElementById(id).value = data.uri
                selec(id);
            }else{
                layer.msg('复制失败,请联系管理员！');
            }
        },
        error:function(a,b){
            msg('发送失败，请联系管理员'+b);
        }
    });
}

function selec(nurl){
    var demo = document.getElementById(nurl);
    demo.select(); // 选择对象
    document.execCommand("Copy"); // 执行浏览器复制命令
    layer.msg('复制成功');
}
