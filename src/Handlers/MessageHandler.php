<?php

namespace Goodwong\LaravelWechatServer\Handlers;

use Goodwong\LaravelUser\Entities\User;
use Goodwong\LaravelWechat\Entities\WechatUser;
use Goodwong\LaravelUser\Handlers\UserHandler;
use Goodwong\LaravelWechat\Handlers\WechatHandler;
use Goodwong\LaravelWechatQrcode\Entities\WechatQrcode;

class MessageHandler
{
    /**
     * wechatUser
     * 
     * @var WechatUser
     */
    private $wechatUser = null;

    /**
     * qrcode
     * 
     * @var WechatQrcode
     */
    private $wechatQrcode = null;

    /**
     * user
     * 
     * @var User
     */
    private $user = null;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->wechatHandler = app(WechatHandler::class);
        $this->userHandler = app(UserHandler::class);
    }

    /**
     * serve
     * 
     * @param  mixed  $message
     * @return
     */
    public function serve($message)
    {
        // prepare wechat user
        $this->loadWechatUser($message->FromUserName);

        // load qrcode
        if (in_array($message->Event, ['subscribe', 'SCAN'])) {
            $this->loadQrcode(str_replace('qrscene_', '', $message->EventKey));
        }

        // load user
        $this->loadUser();

        // logic
        return "哈哈";
    }

    /**
     * load wechat user
     * 
     * @param  string  $openid
     * @return void
     */
    private function loadWechatUser($openid)
    {
        $wechatUser = WechatUser::where('openid', $openid)->first();
        if (!$wechatUser) {
            $wechatUser = $this->wechatHandler->fetchByOpenid($openid);
        }
        $this->wechatUser = $wechatUser;
    }

    /**
     * load qrcode
     * 
     * @param  string  $id
     * @return void
     */
    private function loadQrcode($id)
    {
        $this->qrcode = WechatQrcode::find($id);
    }

    /**
     * load user
     * 
     * @return void
     */
    private function loadUser()
    {
        if ($this->wechatUser->user_id) {
            $this->user = User::find($this->wechatUser->user_id);
        } else {
            $this->user = $this->userHandler->create([
                'email' => $this->wechatUser->unionid ? $this->wechatUser->unionid . '@wexin_unionid' : $this->wechatUser->openid . "@weixin",
                'name' => $this->wechatUser->nickname,
            ]);
            $this->wechatUser->update(['user_id' => $this->user->id]);
        }
    }

    /**
     * set user scene
     * 
     * @return void
     */
    private function switchScene()
    {
        //
    }

    /**
     * record activity
     * 
     * @return void
     */
    private function record()
    {
        //
    }

    /**
     * dispatch
     * 
     * @return  Response
     */
    private function dispatch()
    {
        //
    }
}
