define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/outshop/index',
                    add_url: 'webconfig/outshop/add',
                    edit_url: 'webconfig/outshop/edit',
                    del_url: 'webconfig/outshop/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                orderName: 'desc',
                escape: false,
                columns: [
                    [
                        { checkbox: true},
                        { field: 'shopid', title: '店铺id'},
                        { field: 'discount', title: '折扣率',sortable:true },
                        { field: 'type', title: '合作方',searchList:{0:'聚名'} },
                        { field: 'create_time', title: '创建时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
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


function setStat(status,id){
    if(status == 1){
        layer.prompt({title: '请输入折扣率', formType: 0}, function(value, index){
            if ($.trim(value) === '') {
                layer.msg('请输入折扣率');
                return false;
            }

            if (isNaN(value)) {
                layer.msg('请输入数字');
                return false;
            }
            if (value < 0) {
                layer.msg('折扣率不能为负数');
                return false;
            }
            layer.close(index);
            layer.load(1);
            ajx(value,id,1);
        });
    }else if(status == 2){
        layer.prompt({
            formType: 3,
            placeholder: '请输入区间值',
            title: '请输入区间值',
        }, function(value, index, elem){
            var max_price = $('#max_price').val();
            if ($.trim(value) === '') {
                layer.msg('请输入最小值');
                return false;
            }
            if ($.trim(max_price) === '') {
                layer.msg('请输入最大值');
                return false;
            }
            if (isNaN(value) && isNaN(max_price)) {
                layer.msg('请输入数字');
                return false;
            }
            if (value >= max_price) {
                layer.msg('最小值不能大于等于最大值');
                return false;
            }
            layer.load(1);
            layer.close(index);
            ajx(value,id,2,max_price);
        });
        $('.layui-layer-input').css('width','100px');
        $('.layui-layer-input').attr('placeholder','最小值');
        $('.layui-layer-input').attr('id','min_price');
        $(".layui-layer-input").after('<span style="display: inline-block;width: 10px;margin: 0 5px 5px 5px;float:left;">--</span><input type="text" placeholder="最大值" class="layui-layer-input" id="max_price">')
        $('.layui-layer-input').css('float','left');
        $('#max_price').css('width','100px');
    }
}

function ajx(remark = '',id,status,max_price = ''){
    if (status == 2) {
        var url = '/admin/webconfig/outshop/updateZoneValue';
    }else if(status == 1){
        var url = '/admin/webconfig/outshop/updateDiscountRate';
    }
    $.ajax({
        url: url,
        type:'post',
        data:{id:id,remark:remark,max_price:max_price},
        success:function(data){
            layer.closeAll('loading');
            layer.msg(data.msg);
        },
        beforeSend:function(){
            layer.load(1);
        },
        complete:function(){
            $('.btn-refresh').click();
        },
        error:function(){
            layer.msg('发送失败');
        },
    });
}
//批量操作
function operation(status){
    var object = $('#table').bootstrapTable('getAllSelections');
    var ids = '';
    $.each(object,function (index,obj) {
        ids += obj.id + ',';
    })
    ids = ids.substring(0,ids.length-1)
    if (status == 1) {
        setStat(1,ids);
    }else if(status == 2){
        setStat(2,ids);
    }

}