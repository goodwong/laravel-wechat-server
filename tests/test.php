<?php
date_default_timezone_set("Asia/Hong_Kong");

# 配置信息

// 方式一：若手工配置：
$webapi = "http://v2.pinzhu.co/api/wechat-server";
$token        = 'a90ca5f0e4ce7696e4a57974c8974791';
$ToUserName   = 'gh_250884bca3c4';
$FromUserName = 'oGlNawLIWpv8Qx2BLE58-ZNOA5TU';

$testType     = 'subscribe'; // 'ECHO'

# 生成签名信息
$CreateTime = time();
$nonce= rand(100000000, 999999999);
$tmpArr = array($token, $CreateTime, $nonce);
sort($tmpArr, SORT_STRING);
$sign = sha1(implode($tmpArr));
$echostr = uniqid('echo-', TRUE) . '-ok';
$params = array('signature' => $sign,'timestamp' => $CreateTime,'nonce' => $nonce, 'echostr'=>$echostr,);

# 生成xml
$MsgId = "{$nonce}{$nonce}";
$data = compact('ToUserName', 'FromUserName', 'CreateTime', 'MsgId');

# 合成链接地址
$url = url_make($webapi, ['query'=>$params]);
$url = $webapi;


// run...
// testSubscribe();
// testCLICK();
// testVIEW();
testTEXT();


// 事件
function testSubscribe() {
    global $data;
    echo "\n\n============= subscribe ============\n";
    post(xml_make($data + ['MsgType'=>'event', 'Event'=>'subscribe', 'EventKey'=>'qrscene_11']));
    // post(xml_make($data + ['MsgType'=>'event', 'Event'=>'subscribe', 'EventKey'=>'qrscene_12']));
    // post(xml_make($data + ['MsgType'=>'event', 'Event'=>'subscribe', 'EventKey'=>'qrscene_13']));
    // post(xml_make($data + ['MsgType'=>'event', 'Event'=>'subscribe', 'EventKey'=>'qrscene_14']));
}
function testCLICK() {
    global $data;
    echo "\n\n============= CLICK ============\n";
    post(xml_make($data + ['MsgType'=>'event', 'Event'=>'CLICK', 'EventKey'=>'推荐品住']));
    post(xml_make($data + ['MsgType'=>'event', 'Event'=>'CLICK', 'EventKey'=>'售后服务']));
}
function testVIEW() {
    global $data;
    echo "\n\n============= VIEW ============\n";
    post(xml_make($data + ['MsgType'=>'event', 'Event'=>'VIEW', 'EventKey'=>'https://mp.weixin.qq.com/s?__biz=MzIyNzE0NDk4Ng==&mid=502740752&idx=1&sn=b2c94e373343a419e7c23e0eb734969f&chksm=706633654711ba7316502566638e62c1c7acfb551eb8a202e71781ca6639fd2cef0d302593be&scene=18#rd']));
}
// 位置坐标
function testLOCATION() {
    global $data;
    echo "\n\n============= LOCATION ============\n";
    post(xml_make($data + ['MsgType'=>'location', 'Location_X'=>'12.134521', 'Location_Y'=>'113.358803', 'Scale'=>'20', 'Label'=>'位置信息']));
}
// 文本信息
function testTEXT() {
    global $data;
    echo "\n============= TEXT ============\n";
    post(xml_make($data + ['MsgType'=>'text', 'Content'=>'1B102']));
}

function post($xml) {
    global $url;
    $res = simple_curl($url, $xml, 'POST');
    echo "\t\t【响应】\n";
    echo formated_xml($res), "\n\n\n";
    // echo "\n\n\t\t【请求】\n";
    // echo formated_xml($xml);
}

// 接口验证
function testEcho()
{
    $res = simple_curl($url);
    print_r($res);
}










////////////////////////////////////////
//
//           其它的辅助函数
//
////////////////////////////////////////

  function formated_xml($xml)
  {
    $xml = trim($xml);
    if (!$xml) {
      return "<empty string>\n";
    }
    if (!strstr($xml, '<xml')) {
        return $xml;
    }

    $ret = "";
    $raw = preg_replace('|( *)<|i', '<', trim($xml));
    try {
      $dom = new DOMDocument;
      $dom->preserveWhiteSpace = FALSE;
      @$dom->loadXML($xml); // $dom->loadXML(preg_replace('|^[^<]*|m', '',$raw));
      $dom->formatOutput = TRUE;
      $ret = $dom->saveXml();
    } catch(Exception $e) {
      print_r($e);
    }
    if (!$ret) {
      $ret = preg_replace('|(.{100,200})|', '$1...', $raw);
    }
    return $ret;
  }

  function simple_curl($url, $payload = '', $method = 'GET')
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain'));
    curl_setopt($ch, CURLOPT_USERAGENT,      'Mozilla/4.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain'));
    curl_setopt($ch, CURLOPT_USERAGENT,      'Mozilla/4.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if (strtoupper($method) == 'POST') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  "POST");
      curl_setopt($ch, CURLOPT_POST,           1 );
      curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
    }
    return curl_exec($ch);
  }

  function url_make($url, $parameters)
  {
    $url_info = parse_url($url);
    $scheme = isset($parameters['scheme']) ? "{$parameters['scheme']}://" : (isset($url_info['scheme']) ? "{$url_info['scheme']}://" : '');
    $_pass = isset($parameters['pass']) ? "{$parameters['pass']}" : (isset($url_info['pass']) ? "{$url_info['pass']}" : '');
    $_user = isset($parameters['user']) ? "{$parameters['user']}" : (isset($url_info['user']) ? "{$url_info['user']}" : '');
    $userpass = $_user && $_pass ? "{$_user}:{$_pass}@" : '';

    $host = isset($parameters['host']) ? "{$parameters['host']}" : (isset($url_info['host']) ? "{$url_info['host']}" : '');
    $port = isset($parameters['port']) ? ":{$parameters['port']}" : (isset($url_info['port']) ? ":{$url_info['port']}" : '');
    $path = isset($parameters['path']) ? "{$parameters['path']}" : (isset($url_info['path']) ? "{$url_info['path']}" : '');
    $fragment = isset($parameters['fragment']) ? "#{$parameters['fragment']}" : (isset($url_info['fragment']) ? "#{$url_info['fragment']}" : '');
    // 这两步，将url中的query与当前的$_GET参数合并，$_GET是补充参数
    parse_str(isset($url_info['query'])?$url_info['query']:'', $query_part);
    $query = http_build_query((isset($parameters['query'])?$parameters['query']:array()) + $query_part);
    $query = $query ? "?{$query}" : '';
    $new_url = "{$scheme}{$userpass}{$host}{$port}{$path}{$query}{$fragment}";

    return $new_url;
  }

  function xml_make($vars, $xml = null, $root='xml', $label='item', $level=0)
  {
    if (is_null($xml)) {
      $xml = new SimpleXMLElement('<'.'?xml version="1.0" encoding="UTF-8"?'.'><'.$root.'/>');
    }
    foreach ((array)$vars as $key => $value) {
      if (is_numeric($key)) {
        $key = $label;
      }
      if (is_array($value) || is_object($value)) {
        xml_make($value, $xml->addChild($key), $root, $label, $level+1);
      }
      else {
        $xml->addChild($key, str_replace('&', '&amp;', $value));
      }
    }

    if ($level == 0) {
      return $xml->asXML();
    }
  }

