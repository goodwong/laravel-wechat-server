<?php

namespace Goodwong\WechatServer\Http\Controllers;

use App\Http\Controllers\Controller;
use Goodwong\User\Entities\User;
use Goodwong\Wechat\Entities\WechatUser;
use Goodwong\WechatQrcode\Entities\WechatQrcode;
use Goodwong\WechatServer\Handlers\MessageHandler;
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
