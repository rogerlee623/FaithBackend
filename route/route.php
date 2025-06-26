<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/*Route::get('Chinahood', function () {
    return 'hello,Chinahood!';
});*/
return [
    '/'=>'home/index/index',
    'about/:catId'=>'home/about/index',
    'download/:catId'=>'home/download/index',
    'services/:catId'=>'home/services/index',
    'servicesInfo/:catId/[:id]'=>'home/services/info',
    'system/:catId'=>'home/system/index',
    'news/:catId'=>'home/news/index',
    'info/:catId/[:id]'=>'home/news/info',
    'team/:catId'=>'home/team/index',
    'contact/:catId'=>'home/contact/index',
    'senmsg'=>'home/index/senmsg',
    'down/:id'=>'home/index/down',
    'tags/:keyword'=>'home/tags/index',
    'listinfo/:catId/[:pageid]'=>'api/index/index',
    'banner'=>'api/index/banner',
    'homeinfo/[:page]'=>'api/index/homeInfo',
    'article/[:id]'=>'api/index/article',
    
    //'api/:catId'=>'api/index/index',
    // 'manager/index/index' => ['admin/index/index', ['method' => 'get']],
    // 'manager/login'=> ['admin/login/index',['method' => 'get']],
    // 'manager/login/index'=> 'admin/login/index',
    
    // 'manager'=>'home/index/index',
    // 'admin'=>'home/index/index',
    // 'admin/login'=>'home/index/index',
    // 'admin/login/index'=>'home/index/index',
];
