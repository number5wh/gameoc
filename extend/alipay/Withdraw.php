<?php

namespace alipay;


use app\common\Api;

//支付宝
class Withdraw
{
    private $merid = '2019091116163825367';
    private $url = "http://www.ganzaoji99.com/api.php/gateway/gateway_order_pay";
    private $notify = "http://mt.gamefeizhu.com/admin/withdraw/alipaynotify";
    private $publicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxUstcY8YhMyq6mXhxqQ3
6yoxW2/DkHoiMqQzPcRe8ymmolZHt9mwO4F1i4gCHveLw6m5TDRhxmKacLsE1/cC
EZqLHdOuZkigi6t40h2CZxjMeZB5UJhta1T+fuJef1og+Kawe0FUcLWP8/CVG3+D
5deOhSwUXA7uDi8yRLNHVfuPcb0FRx7D0jJfCEl393MHo60MIELsODXSrRV/I4bs
e4bkgnXNU6Nzm/THS2mSjY2oxtil5Uy7patiTlb/9WQPE/fpAlFZotA1TamgIfnA
HiJuDB8JfW0rtHEyLWzxLAJIZ3WL1SHJ0K8VMHumDZdlpoiU9Qq/Sp76wHIlMvoa
OQIDAQAB";
    private $priKey = "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDFSy1xjxiEzKrq
ZeHGpDfrKjFbb8OQeiIypDM9xF7zKaaiVke32bA7gXWLiAIe94vDqblMNGHGYppw
uwTX9wIRmosd065mSKCLq3jSHYJnGMx5kHlQmG1rVP5+4l5/WiD4prB7QVRwtY/z
8JUbf4Pl146FLBRcDu4OLzJEs0dV+49xvQVHHsPSMl8ISXf3cwejrQwgQuw4NdKt
FX8jhux7huSCdc1To3Ob9MdLaZKNjajG2KXlTLulq2JOVv/1ZA8T9+kCUVmi0DVN
qaAh+cAeIm4MHwl9bSu0cTItbPEsAkhndYvVIcnQrxUwe6YNl2WmiJT1Cr9KnvrA
ciUy+ho5AgMBAAECggEBAIwEeYDhTEZbNmVZ3uzp+OGFtTeuTv2HICQOkmsgpT4v
bhpB324kKUVh8DkRUmgFyQQYvO/PMSDpM7ATmjFnFOnHYznM1DW1D3NwQzPjS3u9
hsgzd1VyiB0nWeJU5zm8ji/JpPAkgjfnMv2t3TSBv+rrmzL6AI0A74PTjPpivZrP
x2Ef0UXcQDnGLtSJjiYNZ22aSxJMbtVMRmVgO4ukYQVQKiPNnoz/CSGudaU5iL1A
9ynNQDOVfn7Sp8FkGtXX6+fvsgiV3aLTC07hXlKibZQn/BUODyWoYW16cIB0nLxd
fQch0bracq69irgPy2J0OPkds6CKGdyOdhYKMd2bmIECgYEA/Ln7OsK1NAcsQDVI
oZycm+2qGAmkHU3K80Dcu9LBel/8rN8A2y17U1ozSYaK6tDlyImoqBzEQas1yAVE
HXr4fw1/4NhIp98qMEuIflKb5q4v0FfjPqU6LYq6l/Sg14VKxE8qoBWalgD6Tmp8
oXK1RKGI2JT3tI1ySgQkkStNhEkCgYEAx9lixD0l8ZQMBeA0lp6sGObzJcageauJ
0BW18rNmMa8gCSa5jV8e5RyT+1c42y9HnOXfwiiaV57EJIO3BqPn3MwAVFbLLJQP
4lrmojbtWEwoOXJ1h2vSMTP31jpVEAIa80/zYR0DjCf9gqExbs9taEBbUGUg7TCB
Uq9HYv1hBnECgYBM7J8Xp6RYDcbeVFmjN3RD0fdwEZ8ufDtGB3wof2H8ybKzO72a
+SEMoevyeU1XY/ZNL/lyEi96fY6FL3UoNAHnSkieO7cBwd+pi5QkPyjM8kADfnzg
2JH4wr1A+2jpsNytHBuxVmITPoDx1V/SFIQwO6rXoaA0CMm81b37od6aYQKBgQC9
iHNc/UkhBXEpmWsGddFthIqRBwFmosL+r7hxRqbi892EEE/lvZKFY0cNFbl8Viiq
qnA9qVhHRPFsV+aay5O4GVkuo2npCzrNR7x3l3QdS4zSfrTsC9u6gjjH2WaW2ghJ
PUfqkCOvJrYMz2ccWmi+eFqhsmc5y8i6bDVdskP0YQKBgA0CMQAMy4dh47ONW8Oa
6ItudBPEqijcJG6nfFZQ6E1Ig9z6MTtgSpllIjNN6hoKdNLKv9oC4A+vDxfD8ruX
LTAtuHuFSXcbegj1JS7FXOewqGQxUVeZ4rBYKeyg0toPfAMh5eOYTRhd6qL+77ap
YOtCjEN2W5vCH9uFMZKAbFu/";
    private $pubkey2 = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuz8VJ2NYH86ENDhYIZ98
YxpnKMExxgwrDg+K1SSagPOGjug8ZSdUtDXJNuYY5V54PUOgYoNBfSvvWhsLSAke
m1nH7WKLQe/h2RRQQk2HwV6+ft0il+UP0ZvHpbPuy+5q6v8oBmZZljA4u2MKy7Dt
fkRrFHwNaJci4QwE6o8NovnXRmGvLXUk9g8BoA0hbXvz/UBH/fDOkbMsI/Pm3I8O
bGkfJNsvk8j7L3Hcg7j/TgE2x3ybOs+++lhxRVSat3HX6z45OU+nBFXjmG6I3NaY
dN5L1D2zboui2H/J9Ae6a71fmWIaVRvkxMG4EsGwmkKv56Wso8vkZ7JAZeQ+ziUK
nwIDAQAB';

    public function sign($data, $pri_key, $signType = "RSA2")
    {
        $priKey = $pri_key;
        $res    = "-----BEGIN RSA PRIVATE KEY-----\r\n" .
            wordwrap($priKey, 64, "\r\n", true) .
            "\r\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    public function rsaSign($params, $rsaPrivateKey, $signType = "RSA2")
    {
        return self::sign(self::getSignSortContent($params), $rsaPrivateKey, $signType);
    }

    public function getSignSortContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i                = 0;
        foreach ($params as $k => $v) {
            if ($v != "") {
                // 转换成目标字符集
                $v = iconv('GBK//IGNORE', 'UTF-8', $v);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }


    public function verify($data, $sign, $rsaPublicKey, $signType = 'RSA2')
    {
        //转换为openssl格式密钥
        $pubKey = $rsaPublicKey;
        $res    = "-----BEGIN PUBLIC KEY-----\r\n" .
            wordwrap($pubKey, 64, "\r\n", true) .
            "\r\n-----END PUBLIC KEY-----";


        ($res) or die('公钥错误。请检查公钥文件格式是否正确');
        //save_log("apidata/withdrawalipaynotify", $sign . ' ' . $res . ' ' . openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256));

        //调用openssl内置方法验签，返回bool值

        $result = FALSE;
        if ("RSA2" == $signType) {

            $result = (openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256) === 1);
        } else {
            $result = (openssl_verify($data, base64_decode($sign), $res) === 1);
        }

        return $result;
    }

    public function rsaCheckV1($params, $rsaPublicKey, $signType = 'RSA2')
    {
        $sign = $params['sign'];
//        var_dump($params);
//        die;
        $params['sign_type'] = null;
        $params['sign']      = null;
        return $this->verify($this->getSignSortContent($params), $sign, $rsaPublicKey, $signType);
    }

    /**
     * curl模拟POST
     * @return string
     */
    public function curlPost($url, $var, $timeout = 120, $theHeaders = null, $isProxy = false)
    {
        $curl    = curl_init();
        $referer = '';
        if (stripos($url, 'https://') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
//        if ($isProxy) {
//            curl_setopt($curl, CURLOPT_PROXY, PROXYURL);
//        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        if ($theHeaders) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $theHeaders);
        }
        if (!empty($referer)) {
            curl_setopt($curl, CURLOPT_REFERER, $referer);
        }
        //curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookies.txt');
        //curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookies.txt');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $var);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $data['str']    = curl_exec($curl);
        $data['status'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $data['errno']  = curl_error($curl);

        return $data;
    }


    //下单
    public function addOrder($ordernumber, $account, $amount)
    {
        $data = [
            'merchant_biz_no' => $ordernumber, //商户订单号
            'id_merchant'     => $this->merid, //商户号 PID
            'amount'          => $amount,      //订单金额 元
            'payee_account'   => $account, //户号(银行卡号);  //支付宝账号
            'notify_url'      => $this->notify
        ];

        $data['sign'] = $this->rsaSign($data, $this->priKey, 'RSA2');
        $res          = $this->curlPost($this->url, $data, 20);
        $ret = $res;
        save_log('apidata/withdrawalipayadd', json_encode($res, JSON_UNESCAPED_UNICODE));
//        var_dump($res);
//        die;
        return $ret['str'];
    }

    //回调
    public function notify($get)
    {
        $flag = $this->rsaCheckV1($get, $this->pubkey2, "RSA2");
        if ($flag) {
            if ($get['code'] == 0) {
                //更新打款成功状态
                Api::getInstance()->sendRequest([
                    'roleid'    => 0,
                    'orderid'   => $get['merchant_biz_no'],
                    'status'    => 5,
                    'checkuser' => '系统处理',
                    'descript'  => '提现成功',
                ], 'charge', 'updatecheck');
                save_log('apidata/withdrawalipaynotify', 'orderid:' . $get['merchant_biz_no'] . ' SUCCESS');
                echo "success";
            } else {
                //更新打款失败状态
                Api::getInstance()->sendRequest([
                    'roleid'    => 0,
                    'orderid'   => $get['merchant_biz_no'],
                    'status'    => 6,
                    'checkuser' => '系统处理',
                    'descript'  => '支付宝处理未通过',
                ], 'charge', 'updatecheck');
                save_log('apidata/withdrawalipaynotify', 'orderid:' . $get['merchant_biz_no'] . ' fail');
                echo 'fail';
            }
        } else {
            save_log("apidata/withdrawalipaynotify", 'sign wrong ' . json_encode($flag, JSON_UNESCAPED_UNICODE));
        }
    }
}