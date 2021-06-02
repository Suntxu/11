define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/violation/inspection/index',
                    add_url: 'domain/violation/inspection/add',
                    edit_url: 'domain/violation/manual/index',
                    del_url: 'domain/violation/inspection/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                escape:false,
                showJumpto: true, //是否开启分页跳转
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'tit', title: '域名',operate:'TEXT'},
                        { field: 'uid', title: '归属用户',},
                        { field: 'type', title: '违规类型',operate:'like',searchList:{1:'百度敏感词',2:'综合敏感词',3:'暴恐',4:'反动',5:'民生',6:'色情',7:'贪腐',8:'其他',9:'百度过滤词'}},
                        { field: 'cause', title: '违规原因',operate:'like'},
                        { field: 'img_path', title: '违规截图地址',operate:false,formatter:Table.api.formatter.url},
                        { field: 'img_path', title: '违规截图',operate:false,formatter:Table.api.formatter.image,visible:false},
                        { field: 'is_redirect', title: '是否重定向',searchList:{0:'否',1:'是'}},
                        { field: 'is_img', title: '是否截图',searchList:{0:'未截图',1:'未上传',2:'已上传'}},
                        { field: 'add_type', title: '添加类型',searchList:{0:'自查',1:'手动'}},
                        { field: 'registrar', title: '注册商', searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        { field: 'create_time', title: '查询时间',operate: 'INT',addclass: 'datetimerange',sortable:true,formatter: Table.api.formatter.datetime},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
            });
             //批量修改
            $('#btn-update').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var id = new Array();
                $.each(temp,function(i,n){
                    id.push(n.id);
                });
                //拼装链接 进行点击
                $('#btn-update').attr('href','/admin/domain/violation/manual/index/ids/'+id.join(','));
            });
            //批量复制
            $('#btn-copy').click(function(){
                // 获取选中的列
                var temp=table.bootstrapTable('getSelections');
                var tit = new Array();
                $.each(temp,function(i,n){
                    tit.push(n.c_tit);
                });
                console.log(tit.join("\n"));
                copyData(tit.join("\n"));
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



