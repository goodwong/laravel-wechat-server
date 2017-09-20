<?php

namespace Goodwong\LaravelWechatServer\Http\Controllers;

use Goodwong\LaravelUserAttribute\Handlers\UserDataHandler;

abstract class HandlerBase
{
    /**
     * context
     * 
     * @var string
     */
    protected $context = null;

    /**
     * user
     * 
     * @var User
     */
    protected $user = null;

    /**
     * wechatUser
     * 
     * @var WechatUser
     */
    protected $wechatUser = null;

    /**
     * qrcode
     * 
     * @var WechatQrcode
     */
    protected $qrcode = null;

    /**
     * handler
     * 
     * @var UserDataHandler
     */
    protected $handler = null;

    /**
     * constructor
     * 
     * @param  string  $context
     * @param  User  $user
     * @param  WechatUser  $wechatUser
     * @param  WechatQrcode  $wechatQrcode
     * @return void
     */
    public function __construct($context, $user, $wechatUser, $qrcode)
    {
        $this->context = $context;
        $this->user = $user;
        $this->wechatUser = $wechatUser;
        $this->qrcode = $qrcode;

        $this->handler = new UserDataHandler($this->context);
    }

    /**
     * message
     * 
     * @param  object  $message
     * @return object
     */
    protected function message($message) {
        return app()->wechat->staff
            ->message($message)
            ->to($this->wechatUser->openid)
            ->send();
    }

    /**
     * get user value by code
     * 
     * @param  string  $code
     * @param  mixed  $default
     * @return string
     */
    protected function getValue($code, $default = null)
    {
        return data_get($this->handler->getByCode($this->user->id, $code), 'value', $default);
    }

    /**
     * set user value by code
     * 
     * @param  string  $code
     * @param  string  $value
     * @param  array  $additional (optional)
     * @return string
     */
    protected function setValue($code, $value, $additional = [])
    {
        return $this->handler->setByCode($this->user->id, $code, $value, $additional);
    }

    /**
     * serve
     * 
     * @param  mixed  $message
     * @return Response | string
     */
    abstract public function serve($message);
    // {
    //     return '收到：' . $message->Content;
    //     $this->message($m);
    //     return null
    // }
}