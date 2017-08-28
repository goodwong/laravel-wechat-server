<?php

namespace Goodwong\LaravelWechatServer;

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
        Route::namespace('Goodwong\LaravelWechatServer\Http\Controllers')->group(function () {
            Route::any('wechat-server', 'ServerController@serve')->name('wechat-server');
        });
    }
}
