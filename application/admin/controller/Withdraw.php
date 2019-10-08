<?php

namespace app\admin\controller;

//use pay\Pay;
use alipay\Pay as Alipay;

class Withdraw
{
    //private $pay;
    private $alipay;


    public function __construct()
    {
        //$this->pay = new Pay();
        $this->alipay = new Alipay();
    }

//同略云
//    //提现
//    public function index()
//    {
//        $this->pay->pay();
//    }
//
//
//    public function notify()
//    {
//        $this->pay->notify();
//    }

    public function alipay()
    {
        $this->alipay->pay();
    }

    public function alipaynotify()
    {
        $this->alipay->notify();
    }
}