<?php

namespace Goodwong\WechatServer;

use Illuminate\Support\Facades\Route;

class Router
{
    /**
     * routes
     * 
     * @return void
     */
    public static function server()
    {
        Route::namespace('Goodwong\WechatServer\Http\Controllers')->group(function () {
            Route::any('wechat-server', 'ServerController@serve')->name('wechat-server');
        });
    }
}
