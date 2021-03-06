<?php

namespace Enlighter\extensions;

use \Enlighter\skltn\ResourceManager as ResourceManager;

class Jetpack{

    // @see resources/extensions/jetpack-infinite-scroll.js
    public static function getInfiniteScrollCode(){
        return '!function(n){"undefined"!=typeof jQuery&&jQuery(document.body).on("post-load",function(){"undefined"!=typeof EnlighterJSINIT&&n.setTimeout(function(){EnlighterJSINIT.apply(n)},180)})}(window);';
    }
}