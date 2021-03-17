/**
 * 公共js方法
 */


/**
 * 快速时间搜素
 * @param  {INT}    vla 搜索栏第几个时间 从0开始 参数最好保持一致
 * @param  {INT}    lag 第几个时间选项 今天 昨天 最近7天..
 * @param  {string} eid 要选择的时间字段 用于清空当前时间条件
 * @return {[type]}     [description]
 */
function fastDateSearch(vla,lag,eid){
    if(lag == 6){ //清空
        $('#'+eid).val('');
    }else{
        $($('.ranges ul').get(vla)).children('li').get(lag).click();
    }
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    });
}

/**
 * 提交特殊条件  
 */
function submitSpecial(self){
    var id = $(self).data('value');
    var field = $(self).data('field');
    var html = '<input type="hidden" class="form-control operate" name="'+field+'-operate" data-name="'+field+'" value="=" readonly=""><input type="hidden" class="form-control" name="'+field+'" value="'+id+'"  id="'+field+'">';
    if($('#'+field).val() != undefined){
        $('#'+field).val(id);
    }else{
        $('fieldset').append(html);
    }
    $('.btn-success').each(function(i,n){
        if($(n).attr('formnovalidate') != 'undefined'){
            $(n).click();
            return false;
        }else{
            return true;
        }
    });

}

/**
 * 获取几天后的时间
 * day 几天  flag是否返回时分秒
 */
function getDateExpire(day,flag){
    var date = new Date();
    date.setDate(date.getDate() + day);
    if(flag){
        return date.getFullYear() +"-"+ (date.getMonth()+1) +"-"+ date.getDate()+' '+date.getHours()+':'+date.getMinutes()+':'+date.getSeconds();
    }
    return date.getFullYear() +"-"+ (date.getMonth()+1) +"-"+ date.getDate();

}
/**
 * 格式化时间
 */

function formtDate(time){
    var now = new Date(time*1000);
    var year=now.getFullYear();  //取得4位数的年份
    var month=now.getMonth()+1;  //取得日期中的月份，其中0表示1月，11表示12月
    var date=now.getDate();      //返回日期月份中的天数（1到31）
    var hour=now.getHours();     //返回日期中的小时数（0到23）
    var minute=now.getMinutes(); //返回日期中的分钟数（0到59）
    var second=now.getSeconds(); //返回日期中的秒数（0到59）
    return year+"-"+month+"-"+date+" "+hour+":"+minute+":"+second;
}

/**
 * 弹出一段信息
 */

function showRemark(tit){
    layer.alert(tit);
}

/**
 * 离线导出任务
 */
function offlineExport(action){

    layer.prompt({title: '请输入要生成的文件名', formType: 2}, function(text, index){
        layer.close(index);
        layer.load(1);
        var param = getSearchParams();
        $.get('/admin/export/index',{filter:JSON.stringify(param['filter']),op:JSON.stringify(param['op']),action:action,name:text},function(res){
            layer.closeAll('loading');
            console.log(res);
            layer.msg(res.msg);
        },'json');
    });
}


/**
 * 获取通用搜索条件
 */
function getSearchParams(){
    var formdata = decodeURIComponent($("form.form-commonsearch").serialize());
    var op = {};
    var filter = {};
    var arr = formdata.split('&');
    for(var i in arr){
        n = arr[i].split('=');

        if(!n[1]){ //如果值为空 跳过循环
            continue;
        }
        if(n[0].indexOf('-') == -1){
            var o = $.trim($(".form-commonsearch [name='" + n[0] + "-operate']").val()); //获取操作值

            if(o == 'BETWEEN' || o == 'THOUSANDS'){ //值包含逗号
                var value_begin = $.trim($(".form-commonsearch [name='" + n[0] + "']:first").val());
                var value_end = $.trim($(".form-commonsearch [name='" + n[0] + "']:last").val());
                filter[n[0]] = value_begin + ',' +value_end; 
                //如果是时间筛选，将operate置为RANGE
                if ($(".form-commonsearch [name='" + n[0] + "']:first").hasClass("datetimepicker")) {
                    o = 'RANGE';
                }   
            }else if(o == 'RANGE' || o == "INT"){
                filter[n[0]] = $.trim($(".form-commonsearch [name='" + n[0] + "']").val());

            }else{
                filter[n[0]] = n[1];
            }
            op[n[0]] = o;
        }
    }
    return {op:op,filter:filter}
}

/**
 * 根据后缀获取api
 */
function getSuffixApi(self){
    var hz = $(self).val();
    var html = '<option value="">请选择</option>';
    $.ajax({
        url:'webconfig/suffixreg/getApisOption',
        type:'post',
        data:{hz:hz},
        dataType:'json',
        success:function(data){
            if(data.code ==1){
                html += '<option>'+data.msg+'</option>';
            }else{
                $.each(data.res,function(i,n){
                    html += '<option value="'+n.id+'">'+n.tit+'</option>';
                });
                $('#ref').html(html);
            }
        }
    });

}
/**
 * 根据userid获取uid 主要用于历史表
 */
function getUserName(userid,self){
    layer.load(1);
    $.post('vipmanage/users/getUserName',{userid:userid},function(res){
        layer.closeAll('loading');
        $(self).parent().html(res.msg);
    },'json');


}

/**
 * 复制内容
 */
function copyData(content){
    var flag = $('#copyData').val();
    if(flag){
        $('#copyData').val(content);
    }else{
        $('body').append('<input style="opacity: 0;" id="copyData" value="'+content+'">');
    }
    $('#copyData').select();
    document.execCommand("Copy");
    layer.msg('复制成功');

}

/**
 * 几个字后用..显示
 */
function subStringShowDot(str,len){
    
    len = len ? len : 10;
    
    if(str.length <= len){
        return str;
    }
    return str.substr(0,len)+'...';

}