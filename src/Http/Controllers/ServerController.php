<?php

namespace Goodwong\LaravelWechatServer\Http\Controllers;

use App\Http\Controllers\Controller;
use Goodwong\LaravelUser\Entities\User;
use Goodwong\LaravelWechat\Entities\WechatUser;
use Goodwong\LaravelWechatQrcode\Entities\WechatQrcode;
use Goodwong\LaravelWechatServer\Handlers\MessageHandler;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    /**
     * constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $this->server = app()->wechat->server;
    }

    /**
     * wechat server handler.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function serve(Request $request)
    {
        $this->server->setMessageHandler(function ($message) {
            return (new MessageHandler)->serve($message);
        });
        return $this->server->serve();
    }
}
