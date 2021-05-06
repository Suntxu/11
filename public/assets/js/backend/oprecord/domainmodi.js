define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/domainmodi/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'special_condition', title: '归属用户',},
                        { field: 'show', title: '详情',operate:false},
                        { field: 'ext', title: '备注',operate:false},
                        { field: 'time', title: '操作时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime,defaultValue:getTimeFrame()},
                        { field: 'group', title: '操作者',},
                        { field: 'type', title: '操作类型',operate:false, formatter: Table.api.formatter.status,searchList: {0:'域名出库',1:'冻结操作',2:'修改微信cookie',3:'手动补单',4:'域名入库',5:'注册域名退款',6:'修改一口价属性',7:'手动过户',8:'修改一口价价格'},},
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});

/**
 * 查看详情
 */
function showDetail(selp){

  var url = $(selp).data('url');
  var id = $(selp).data('id');
  var field = $(selp).data('operate');
  layer.load(1);
  $.post(url,{id:id,field:field},function(ret){
    layer.closeAll('loading');
    if(ret.code == 1){
      layer.msg(ret.msg);
    }else{

      var remark = {'txt':'简介：','money':'价格：','endtime':'更新域名时间：'};
      var html = '<table class="layui-table" style="width:400px;margin:10px 5px;" lay-size="sm">';
      erg = field.split(',');
      html += '<tr><td style="padding-left:8%;color:orange;">'+(erg[1] ? '原值' : '域名')+'</td></tr>';

      for(var j in ret.data[erg[0]]){

        var msg = remark[j] ? remark[j] : '';
        var val = j.indexOf('time') == -1 ? ret.data[erg[0]][j] : formtDate(ret.data[erg[0]][j]);
        html += '<tr><td style="padding-left:8%;">'+msg+val+'</td></tr>';

      }
      if(erg[1]){ //修改值
          html += '<tr><td style="padding-left:8%;color:red">修改值</td></tr>';
          for(var i in ret.data[erg[1]]){
            var msg = remark[i] ? remark[i] : '';
            var val = i.indexOf('time') == -1 ? ret.data[erg[1]][i] : formtDate(ret.data[erg[1]][i]);
            html += '<tr><td style="padding-left:8%;">'+msg+val+'</td></tr>'
          }
      }
      html += '</table>';
      layer.open({
          type: 1,
          title: '详情',
          area:'450px',
          shadeClose: true,
          move:false,
          content: html,
      });
    }
  },'json')

}