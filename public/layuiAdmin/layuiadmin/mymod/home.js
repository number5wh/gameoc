/**

 @Name：layuiAdmin 主页控制台
 @Author：贤心
 @Site：http://www.layui.com/admin/
 @License：GPL-2

 */


layui.define(function(exports){

    /*
     下面通过 layui.use 分段加载不同的模块，实现不同区域的同时渲染，从而保证视图的快速呈现
     */

    //区块轮播切换
    layui.use(['admin', 'carousel'], function(){
        var $ = layui.$
            ,admin = layui.admin
            ,carousel = layui.carousel
            ,element = layui.element
            ,device = layui.device();

        //轮播切换
        $('.layadmin-carousel').each(function(){
            var othis = $(this);
            carousel.render({
                elem: this
                ,width: '100%'
                ,arrow: 'none'
                ,interval: othis.data('interval')
                ,autoplay: othis.data('autoplay') === false
                ,trigger: (device.ios || device.android) ? 'click' : 'hover'
                ,anim: othis.data('anim')
            });
        });

        element.render('progress');

    });

    //数据概览
    layui.use(['admin', 'carousel', 'echarts'], function(){
        var $ = layui.$
            ,admin = layui.admin
            ,carousel = layui.carousel
            ,echarts = layui.echarts;

        //获取折线图数据
        var getData = function(){
            var regData = [];
            var orderData = [];
            $.ajax({
                type:'post',
                url:'hall',
                dataType:'json',
                async:false,
                success: function(res) {
                    if (res.code === 0) {
                        regData[0] = res.data.dates;
                        regData[1] =  res.data.numbers;
                        regData[2] =  res.data.numbers2;
                    }
                }
            });
            $.ajax({
                type:'post',
                url:'game',
                dataType:'json',
                async:false,
                success: function(res2) {
                    if (res2.code === 0) {
                        orderData[0] = res2.data.dates;
                        orderData[1] =  res2.data.numbers;
                        orderData[2] =  res2.data.numbers2;

                    }
                }
            });

            drawLine(regData, orderData);
        };


        //画折线图
        var drawLine = function(regData, orderData) {
            var echartsApp = [], options = [
                //今日流量趋势
                {
                    title: {
                        text: '大厅在线人数',
                        x: 'center',
                        textStyle: {
                            fontSize: 16
                        }
                    },
                    tooltip : {
                        trigger: 'axis'
                    },
                    legend: {
                        data:['','']
                    },
                    toolbox: {
                        show : true,
                        feature : {
                            mark : {show: true},
                            dataView : {show: true, readOnly: false},
                            magicType : {show: true, type: ['line', 'bar']},
                            restore : {show: true},
                            saveAsImage : {show: true}
                        }
                    },
                    calculable : true,
                    xAxis : [{
                        type : 'category',
                        boundaryGap : false,
                        data: regData[0],
                    }],
                    yAxis : [{
                        type : 'value'
                    }],
                    series : [{
                        name:'IOS人数',
                        type:'line',
                        //smooth:true,
                        //itemStyle: {normal: {areaStyle: {type: 'default'}}},
                        data: regData[1],
                        markPoint : {
                            data : [
                                {type : 'max', name: '最大值'},
                                {type : 'min', name: '最小值'}
                            ]
                        },
                        markLine : {
                            data : [
                                //{type : 'average', name: '平均值'}
                            ]
                        }
                    },
                        {
                            name:'安卓人数',
                            type:'line',
                            //smooth:true,
                            //itemStyle: {normal: {areaStyle: {type: 'default'}}},
                            data: regData[2],
                            markPoint : {
                                data : [
                                    {type : 'max', name: '最大值'},
                                    {type : 'min', name: '最小值'}
                                ]
                            },
                            markLine : {
                                data : [
                                   // {type : 'average', name: '平均值'}
                                ]
                            }
                        }
                    ]
                },
                {
                    title: {
                        text: '游戏内在线人数',
                        x: 'center',
                        textStyle: {
                            fontSize: 14
                        }
                    },
                    tooltip : { //提示框
                        trigger: 'axis'
                    },
                    toolbox: {
                        show : true,
                        feature : {
                            mark : {show: true},
                            dataView : {show: true, readOnly: false},
                            magicType : {show: true, type: ['line', 'bar']},
                            restore : {show: true},
                            saveAsImage : {show: true}
                        }
                    },
                    xAxis : [{ //X轴
                        type : 'category',
                        data : orderData[0]
                    }],
                    yAxis : [{  //Y轴
                        type : 'value'
                    }],
                    series : [{ //内容
                        name:'IOS人数',
                        type: 'line',
                        symbol: "circle", //标记的图形为实心圆
                        data:orderData[1],
                        markPoint : {
                            data : [
                                {type : 'max', name: '最大值'},
                                {type : 'min', name: '最小值'}
                            ]
                        },
                        markLine : {
                            data : [
                               // {type : 'average', name: '平均值'}
                            ]
                        }
                    },
                        { //内容
                            name:'安卓人数',
                            type: 'line',
                            symbol: "circle", //标记的图形为实心圆
                            data:orderData[2],
                            markPoint : {
                                data : [
                                    {type : 'max', name: '最大值'},
                                    {type : 'min', name: '最小值'}
                                ]
                            },
                            markLine : {
                                data : [
                                   // {type : 'average', name: '平均值'}
                                ]
                            }
                        },
                    ]
                }
            ]
                ,elemDataView = $('#LAY-index-dataview').children('div')
                ,renderDataView = function(index){
                echartsApp[index] = echarts.init(elemDataView[index], layui.echartsTheme);
                echartsApp[index].setOption(options[index]);
                window.onresize = echartsApp[index].resize;
                admin.resize(function(){
                    echartsApp[index].resize();
                });
            };


            //没找到DOM，终止执行
            if(!elemDataView[0]) return;
            renderDataView(0);

            //监听数据概览轮播
            var carouselIndex = 0;
            carousel.on('change(LAY-index-dataview)', function(obj){
                renderDataView(carouselIndex = obj.index);
            });

            //监听侧边伸缩
            layui.admin.on('side', function(){
                setTimeout(function(){
                    renderDataView(carouselIndex);
                }, 300);
            });

            //监听路由
            layui.admin.on('hash(tab)', function(){
                layui.router().path.join('') || renderDataView(carouselIndex);
            });
        };

        getData();
    });

    exports('home', {})
});