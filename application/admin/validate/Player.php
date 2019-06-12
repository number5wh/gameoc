<?php

namespace app\admin\validate;

use think\Validate;

class Player extends Validate
{
    protected $rule = [
        'roleid' => 'require|number|length:8',
        'rate'   => 'require|number|between:0,10000'
    ];
    protected $message = [
        'roleid.require' => '玩家ID不能为空',
        'roleid.number'  => '玩家ID格式有误',
        'roleid.length'  => '玩家ID格式有误',
        'rate.require'   => '赠送比例不能为空',
        'rate.number'    => '赠送比例必须为数字',
        'rate.between'   => '赠送比例有误',
    ];
    protected $scene = [
        'addSuper' => [
            'roleid',
            'rate'
        ],
    ];

}
