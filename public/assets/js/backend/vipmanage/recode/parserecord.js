define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/parserecord/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            var Type = {'A':'A','CNAME':'CNAME','AAAA':'AAAA','NS':'NS','MX':'MX','SRV':'SRV','TXT':'TXT','CAA':'CAA','显性URL':'显性URL','隐性URL':'隐性URL'}; //解析类型
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.time',
                orderName:'desc',
                escape: false, //是否对内容进行转义
                columns: [
                    [
                        // {checkbox:true, title:'选择'},
                        {field: 'r.tit', title: '域名',operate:'TEXT'},
                        {field: 'RR', title: '主机记录',},
                        {field: 'r.Type', title : '记录类型',searchList:Type},
                        {field: 'Value', title: '记录值',},
                        {field: 'Line', title: '解析线路isp',operate:false},
                        {field: 'TTL', title: 'TTL值',operate:false},
                        {field: 'r.Status', title: '状态',searchList:{'Enable':'启用','Disable':'停止'},notit:true,sortable:true,},
                        {field: 'u.uid', title: '用户名',},
                        {field: 'zcs', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent'),sortable:true,},
                        {field: 'r.time', title: '解析时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[{
                                name:'停止',
                                title:'停止',
                                text: '停止',
                                classname: 'btn btn-xs  btn-warning btn-ajax',
                                hidden:function(row){
                                    if(row.Status == 'Disable'){
                                        return true;
                                    }
                                },
                                url:function(row){
                                    return '/admin/vipmanage/recode/parserecord/modi?id='+row.id+'&status=1';
                                },
                                error:function(ret,data){
                                    layer.msg(data.msg);
                                },
                                success:function(ret,data){
                                    $('.btn-refresh').click();
                                    layer.msg(data.msg);
                                    
                                }

                            },{
                                name:'启用',
                                title:'启用',
                                text: '启用',
                                classname: 'btn btn-xs btn-success  btn-ajax',
                                hidden:function(row){
                                    if(row.Status == 'Enable'){
                                        return true;
                                    }
                                },
                                url:function(row){
                                    return '/admin/vipmanage/recode/parserecord/modi?id='+row.id+'&status=2';
                                },
                                error:function(ret,data){
                                    layer.msg(data.msg);
                                },
                                success:function(ret,data){
                                    $('.btn-refresh').click();
                                    layer.msg(data.msg);
                                }


                            }]
                        }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            //批量调用解析接口
            $('.parsebtn').on('click',function(){
                var item = table.bootstrapTable('getSelections'); //获取目录
                if(item.length > 20){
                    layer.msg('每次操作不得操作20条记录');
                    return false;
                }
                var succ =  new Array();
                var err = new Array();
                var succnum = 0;
                var errnum = 0;
                for(var i in item){
                    $.get('/admin/vipmanage/recode/parserecord/modi',{id:item[i].id,status:$(this).data('status')},function(data){
                        if(data.code == 1){
                            succnum+=1;
                            succ['ID:'+item[i]['id']+' 域名:'+item[i]['tit']] = data.msg;
                        }else{
                            errnum+=1;
                            err['ID:'+item[i]['id']+' 域名:'+item[i]['tit']] = data.msg;
                        }
                    },'json');
                }
                var html = '<table class="layui-table" style="width:760px;margin:5px;" lay-size="sm">';
                if(succnum > 0){
                    html += '<tr><th style="padding-left:3%;">成功 (<span style="color:green">'+succnum+' 条)</th></tr>';
                    for(var j in succ ){
                        html += '<tr><td style="padding-left:3%;"><span style="color:green">'+j+'</span> -- '+succ[j]+'</td></tr>'
                    }
                    $('.btn-refresh').click();
                }
                if(errnum > 0){
                    html += '<tr><th style="padding-left:3%;">失败(<span style="color:red">'+errnum+' 条)</th></tr>';
                    for(var l in err){
                        html += '<tr><td style="padding-left:3%;"><span style="color:red">'+l+'</span> -- '+err[l]+'</td></tr>'
                    }
                }
                html += '</table>';
                layer.closeAll('loading');
                layer.open({
                  type: 1,
                  title: '解析记录操作结果',
                  area:'800px',
                  closeBtn: 0,
                  shadeClose: true,
                  skin: 'yourclass',
                  content: '<div>'+html+'</div>',
                });
               
            });
            
        }
    };
    return Controller;
});
