define(['jquery', 'bootstrap', 'backend', 'table', 'form','echarts', 'echarts-theme',], function ($, undefined, Backend, Table, Form,Echarts, undefined) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'total/analysis/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 's.userid',
                orderName:'desc',
                pagination:false,
                columns: [
                    [   
                        { field: 'shopname', title: '店铺名',operate:false,},
                        { field: 'u.uid', title: '店铺账号',operate:false,},
                        { field: 'salenum', title: '总销量',operate:false,sortable:true, footerFormatter: function (data) {
                               
                                var zsale = data.reduce(function (sum, row) {
                                    return parseFloat(row['zsale']);
                                }, 0);
                               return zsale;
                        }},
                        { field: 'salemoney', title: '总金额',operate:false,sortable:true, footerFormatter: function (data) {
                               
                                var zje = data.reduce(function (sum, row) {
                                    return parseFloat(row['zje']);
                                }, 0);
                               return zje;
                        }},
                        { field: 'group', title : '支付时间',operate:'RANGE',sortable:true,addclass: 'datetimerange',visible:false },
                        { field: 'num', title: '购买人数',operate:false,sortable:true, footerFormatter: function (data) {
                               
                                var znum = data.reduce(function (sum, row) {
                                    return parseFloat(row['znum']);
                                }, 0);
                               return znum;
                        }},
                        { field: 'show', title: '购买用户',operate:false,formatter:Table.api.formatter.alinks,url:'/admin/total/analysis/show',fieldvaleu:['userid','group'],fieldname:['userid','paytime'],tit:'用户详情', }
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');
            varyMap(myChart);
            //监控是否点击了时间搜索
            $('.form-commonsearch .btn-success').on('click',function(){
                var time = $('#group').val();
                varyMap(myChart,time);

                //调用ajax方法
            });
            $(window).resize(function () {
                myChart.resize(); //下载
            });
            $(document).on("click", ".btn-checkversion", function () {
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });

        },
        show: function(){
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/analysis/show',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'o.userid',
                orderName:'desc',
                exportDataType:'all',
                columns: [
                    [   
                        { field: 'uid', title: '用户名',},
                        { field: 'salenum', title: '购买数量',operate:false,sortable:true},
                        { field: 'paytime', title: '支付时间',operate:'RANGE',sortable:true,addclass: 'datetimerange',visible:false},
                        { field: 'salemoney', title: '购买总金额',operate:false,sortable:true },
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var userid= $("#userid").val();
                    if (userid != '')
                        filter['o.selleruserid'] = userid;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
                
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});
//异步渲染图表
function varyMap(myChart,time){
    $.post('/admin/total/Analysis/varyMap',{ptime:time},function(res){
        var echarsData = JSON.parse(res);
        var colors = ['#5793f3', '#d14a61', '#675bba'];
        // 基于准备好的dom，初始化echarts实例
        option = {
            color: colors,
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    // type: 'cross',
                    crossStyle: {
                        color: '#999'
                    }
                }
            },
            grid: {
                right: '20%'
            },
            toolbox: {
                feature: {
                    magicType: {show: true, type: ['line', 'bar']},//转化类型
                    dataView: {show: true, readOnly: false},
                    saveAsImage: {show: true}
                }
            },
            legend: {
                data: ['总价格', '销量', '购买人数']
            },
            xAxis: [
                {
                    type: 'category',
                    data: echarsData.shopname,
                    axisLabel:{
                        interval: 0,
                        rotate: -30, //文字角度
                    },
                    axisPointer: {
                        type: 'shadow'
                    },
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    name: '总价格/元',
                    min: 0,
                    max: Math.round(echarsData.maxmoney + echarsData.maxmoney*0.3),
                    position: 'left',
                    axisLine: {
                        lineStyle: {
                            color: colors[0]
                        }
                    },
                    axisLabel: {
                        formatter: '{value}'
                    }
                },
                {
                    type: 'value',
                    name: '销量/个',
                    min: 0,
                    max: Math.round(echarsData.maxsnum + echarsData.maxsnum*0.3),
                    position: 'right',
                    axisLine: {
                        lineStyle: {
                            color: colors[1]
                        }
                    },
                    axisLabel: {
                        formatter: '{value}'
                    }
                },
                {
                    type: 'value',
                    name: '人数/个',
                    min: 0,
                    max: Math.round(echarsData.maxnum + echarsData.maxnum*0.3),
                    position: 'right',
                    offset: 72,
                    axisLine: {
                        lineStyle: {
                            color: colors[2]
                        }
                    },
                    axisLabel: {
                        formatter: '{value}'
                    }
                }
            ],
            series: [
                {
                    name: '总价格',
                    type: 'line',
                    data: echarsData.salemoney
                },
                {
                    name: '销量',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: echarsData.salenum
                },
                {
                    name: '购买人数',
                    type: 'bar',
                    yAxisIndex: 2,
                    data: echarsData.num
                }
            ]
        };
        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
    });
}