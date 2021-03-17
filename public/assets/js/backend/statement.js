define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // 基于准备好的dom，初始化echarts实例
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');
            var myChart1 = Echarts.init(document.getElementById('echart1'), 'walden');
            // 指定图表的配置项和数据
            option = {
                title: {
                    text: '整站销量统计报表'
                },
                tooltip : {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'line',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    }
                },
                legend: {
                    data:['一口价销量','完成交易店铺','完成交易用户','待付款交易']
                },
                toolbox: {
                    feature: {
                        saveAsImage: {}
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis : [
                    {
                        type : 'category',
                        boundaryGap : false,
                        data : ['今天','昨天','最近7天','最近30天','总销量']
                    }
                ],
                yAxis : [
                    {
                        type : 'value',
                    }
                ],
                series : [
                    {
                        name:'一口价销量',
                        type:'line',
                        stack: '总量',
                        areaStyle: {normal: {}},
                        data:Orderdata.yfk
                    },
                    {
                        name:'完成交易店铺',
                        type:'line',
                        stack: '总量',
                        areaStyle: {normal: {}},
                        data:Orderdata.sellershop
                    },
                    {
                        name:'完成交易用户',
                        type:'line',
                        stack: '总量',
                        areaStyle: {normal: {}},
                        data:Orderdata.buyuser
                    },
                    {
                        name:'待付款交易',
                        type:'line',
                        stack: '总量',
                        areaStyle: {normal: {}},
                        data:Orderdata.wfk
                    }
                ]
            };
            option1 = {
                title: {
                    text: '一口价总金额'
                },
                tooltip : {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'line',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    }
                },
                legend: {
                    data:['一口价总金额']
                },
                toolbox: {
                    feature: {
                        saveAsImage: {}
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis : [
                    {
                        type : 'category',
                        boundaryGap : false,
                        data : ['今天','昨天','最近7天','最近30天','总销量']
                    }
                ],
                yAxis : [
                    {
                        type : 'value',
                    }
                ],
                series : [
                    {
                        name:'一口价总金额',
                        type:'line',
                        stack: '总量',
                        areaStyle: {},
                        data:Orderdata.sale_money
                    },
                ]
            };
            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
            myChart1.setOption(option1);
            $(window).resize(function () {
                myChart.resize(); //下载
                myChart1.resize();//下载
            });
            $(document).on("click", ".btn-checkversion", function () {
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });
        }
    };

    return Controller;
});