<?php

namespace Goodwong\WechatServer\Handlers;

use Illuminate\Support\Facades\Cache;
use Goodwong\User\Entities\User;
use Goodwong\Wechat\Entities\WechatUser;
use Goodwong\User\Handlers\UserHandler;
use Goodwong\Wechat\Handlers\WechatHandler;
use Goodwong\WechatQrcode\Entities\WechatQrcode;
use Goodwong\UserAttribute\Handlers\UserDataHandler;

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
    private $qrcode = null;

    /**
     * user
     * 
     * @var User
     */
    private $user = null;

    /**
     * context
     * 
     * @var string
     */
    private $context = null;

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
        $this->loadWechatUser($message->FromUserName, $message->Event);

        // load qrcode
        if (in_array($message->Event, ['subscribe', 'SCAN'])) {
            $this->loadQrcode(str_replace('qrscene_', '', $message->EventKey));
        }

        // load user
        $this->loadUser();

        // set context
        $this->setContext();

        // record user activities
        $this->record($message);

        // logic
        return $this->dispatch($message);
    }

    /**
     * load wechat user
     * 
     * @param  string  $openid
     * @param  string  $event
     * @return void
     */
    private function loadWechatUser($openid, $event)
    {
        $wechatUser = WechatUser::where('openid', $openid)->first();
        if (!$wechatUser) {
            $wechatUser = $this->wechatHandler->createByOpenid($openid);
        }
        if ($event == 'unsubscribe') {
            $wechatUser->update(['subscribe' => 0]);
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
        if ($id) {
            $this->qrcode = WechatQrcode::find($id);
        }
    }

    /**
     * load user
     * 
     * @return void
     */
    private function loadUser()
    {
        if ($this->wechatUser->user_id) {
            $user = User::find($this->wechatUser->user_id);
        } else {
            $user = $this->userHandler->create([
                'email' => $this->wechatUser->unionid ? $this->wechatUser->unionid . '@weixin_unionid' : $this->wechatUser->openid . "@weixin",
                'name' => $this->wechatUser->nickname,
            ]);
            $this->wechatUser->update(['user_id' => $user->id]);
        }
        $this->user = $user;
    }

    /**
     * set user context
     * 
     * @return void
     */
    private function setContext()
    {
        $handler = new UserDataHandler('global');
        if ($this->qrcode && $this->qrcode->category_id !== 'global') {
            $context = $this->qrcode->category_id;
        } else {
            $value = $handler->getByCode($this->user->id, 'context');
            $context = $value ? $value->value : 'direct';
        }
        $handler->setByCode($this->user->id, 'context', $context, ['label' => '当前场景']);
        $this->context = $context;
    }

    /**
     * record activity
     * 
     * @param  object  $message
     * @return void
     */
    private function record($message)
    {
        $handler = new UserDataHandler($this->context);

        if ($message->Event == 'subscribe') {
            // 扫码关注
            if ($this->qrcode) {
                $handler->addTag($this->user->id, "subscribe", "二维码[{$this->qrcode->name}]",  ['label' => '关注来源', 'group_label' => '互动']);
                (new UserDataHandler($this->qrcode->category_id))->increase($this->user->id, "qrcode_{$this->qrcode->id}@scan", ['label' => "扫码 [{$this->qrcode->name}]", 'group_label' => '互动']);
                $handler->setByCode($this->user->id, "time-line", "来自关注 [{$this->qrcode->name}]",  ['label' => "动态", 'mode' => 'append', 'type' => 'textarea', 'group_label' => '互动']);
            }
            // 直接关注
            else {
                $handler->addTag($this->user->id, "subscribe", "直接关注",  ['label' => '关注来源', 'group_label' => '互动']);
                $handler->setByCode($this->user->id, "time-line", "直接关注公众号",  ['label' => "动态", 'mode' => 'append', 'type' => 'textarea', 'group_label' => '互动']);
            }
            // event(new WechatSubscribed($this->user, $this->qrcode));
        }

        // 取消关注
        elseif ($message->Event == 'unsubscribe') {
            $handler->setByCode($this->user->id, "time-line", "取消关注公众号",  ['label' => "动态", 'mode' => 'append', 'type' => 'textarea', 'group_label' => '互动']);
            // event(new WechatUnSubscribed($this->user));
        }

        // 扫码
        elseif ($message->Event == 'SCAN' && $this->qrcode) {
            (new UserDataHandler($this->qrcode->category_id))->increase($this->user->id, "qrcode_{$this->qrcode->id}@scan", ['label' => "扫码 [{$this->qrcode->name}]", 'group_label' => '互动']);
            $handler->setByCode($this->user->id, "time-line", "扫码 [{$this->qrcode->name}]",  ['label' => "动态", 'mode' => 'append', 'type' => 'textarea', 'group_label' => '互动']);
            // event(new WechatQrcodeScanned($this->user, $this->qrcode));
        }

        // 点击自定义菜单事件
        elseif ($message->Event == 'CLICK' || $message->Event == 'VIEW') {
            $button = $this->findMenuLabel($message);
            $handler->setByCode($this->user->id, "time-line", "点击菜单 [{$button}]",  ['label' => "动态", 'mode' => 'append', 'type' => 'textarea', 'group_label' => '互动']);
            // event(new WechatButtonClicked($this->user, $button));
        }
    }

    /**
     * find menu event label
     * 
     * @param  object  $message
     * @return string
     */
    private function findMenuLabel($message)
    {
        $retry = 1;
        do {
            $menu = Cache::remember("wechat:menu", 1440, function () {
                return data_get(app()->wechat->menu->all(), 'menu.button', []);
            });
    
            $key = $message->EventKey;
            foreach ($menu as $button) {
                if (data_get($button, 'url') == $key || data_get($button, 'key') == $key) {
                    return $button['name'];
                }
                if ( ! data_get($button, 'sub_button')) {
                    continue;
                }
                foreach ($button['sub_button'] as $sub_button) {
                    if (data_get($sub_button, 'url') == $key || data_get($sub_button, 'key') == $key) {
                        return $sub_button['name'];
                    }
                }
            }
            if ($retry) {
                Cache::forget("wechat:menu");
            }
        } while ($retry--);

        // fallback
        return $key;
    }

    /**
     * dispatch
     * 
     * @param  mixed  $message
     * @return  Response | null
     */
    private function dispatch($message)
    {
        $classes = [
            "handler-{$this->context}",
            "handler-default",
        ];
        if ($this->qrcode) {
            array_unshift($classes, "handler-{$this->context}-qrcode-{$this->qrcode->id}");
        }
        foreach ($classes AS $class) {
            $base = env('WECHAT_MESSAGE_HANDLER_NAMESPACE', '\App\Http\Controllers\WechatMessage');
            $class = $base . '\\' . ucfirst(camel_case($class));
            if ( ! class_exists($class)) {
                info('wechat server class missing: ' . $class);
                continue;
            }
            // 有结果直接返回
            // 没有结果: 继续下一个中间件的调用
            $handler = new $class($this->context, $this->user, $this->wechatUser, $this->qrcode);
            $response = $handler->serve($message);
            if ($response) {
                return $response;
            }
        }
        return null;
    }
}
