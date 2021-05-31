define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/delrecord/index',
                    table: 'user',
                }
            });

            var table = $("#table");
             //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                //查看值
              $('.show_value').on('click',function(){
                  title = $(this).attr('title');
                  layer.alert(title.replace(/;/g,"<br>"),{
                    title:'查看'
                  });
              });
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                escape: false, //转义空格
                columns: [
                    [
                        { field: 'tit1', title: '操作',operate:false},
                        { field: 'group', title: '域名搜索',visible: false,},
                        { field: 'create_time', title: '操作时间',addclass:'datetimerange',operate:'INT',formatter: Table.api.formatter.datetime},
                        { field: 'nickname', title: '操作者',},
                        { field: 'type', title: '操作类型',formatter: Table.api.formatter.status,searchList: {0:'域名出库',1:'冻结操作',2:'修改微信cookie',3:'手动补单',4:'域名入库',5:'注册域名退款',6:'修改一口价属性',7:'手动过户',8:'域名续费',9:'解除异地限制'},},
                        { field: 'value', title: '操作值',operate:'LIKE'},
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var type = $("#type").val();
                    if (type != '')
                        filter['r.type'] = type;
                    var id = $('#id').val();
                    if (id != '')
                        filter['r.id'] = id;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
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

//拷贝内容
function copytit(){
  var domains = document.getElementById("copy_content");
  domains.select(); // 选择对象
  document.execCommand("Copy"); // 执行浏览器复制命令
  layer.msg('域名复制成功!');

}
/**
 * 查看详情
 */
function showDetail(selp){
  var id = $(selp).data('id');
  layer.load(1);
  $.post('/admin/oprecord/delrecord/show',{id:id},function(ret){
    layer.closeAll('loading');
    if(ret.code == 1){
      layer.msg(ret.msg);
    }else{
      var html = '<table class="layui-table" style="width:400px;margin:10px 5px;" lay-size="sm">';
      var tit = ret.data.split(',');
      if(tit[0].indexOf('^') == -1){
        html += '<tr><th style="padding-left:8%;">域名</th></tr>';
      }else{
        html += '<tr><th style="padding-left:8%;">域名</th><th style="padding-left:8%;">用户</th></tr>';
      }
      var tits = '';
      for(var i  in tit){
          erg = tit[i].split('^'); //分离用户名和状态
          tits += erg[0]+"\r\n";
          if(erg[1]){
              html += '<tr><td style="padding-left:8%;">'+erg[0]+'</td><td style="padding-left:8%;">'+erg[1]+'</td><tr>';
          }else{
              html += '<tr><td style="padding-left:8%;">'+erg[0]+'</td><tr>';
          }
        }


        html += '</table>';
        var num = tits.split("\r\n").length-1;
        html+= '<textarea style="opacity:0;" id="copy_content">'+tits+'</textarea>';
        var titleHtml;
        if (num > 1){
            titleHtml = '域名列表<a style="margin-left: 10px;color: #FFFFFF" href="javascript:;" onclick="copytit()">复制域名</a>';
        }else{
            titleHtml = '域名列表';
        }



        layer.open({
            type: 1,
            title: titleHtml,
            area:'450px',
            shadeClose: true,
            move:false,
            content: html,
        });
    }
  },'json')

}


