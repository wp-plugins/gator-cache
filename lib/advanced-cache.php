<?php
if(!defined('ABSPATH') || is_admin() || (defined('WP_INSTALLING') && WP_INSTALLING)
  || false === @include_once(($path = WP_CONTENT_DIR . '/plugins/gator-cache/lib/') . 'GatorCache.php')){
    return;
}
$config = GatorCache::getConfig($path . 'config.ini.php');
if(!$config->get('enabled')){
    return;
}
if(!defined('GC_CHK_USER') && $config->get('skip_user')){
    for($ct = count($cookies = array_reverse(array_keys($_COOKIE))),$xx=0;$xx<$ct;$xx++){
        if(0 === strpos($cookies[$xx], 'wordpress_logged_in')){
            define('GC_CHK_USER', true);
            return;
        }
    }
}
$request = GatorCache::getRequest();
if('GET' !== $request->getMethod() || '' !== $request->getQueryString() || $request->isSecure()){//skip ssl for now
    return;
}
if(false !== ($result = ($cache = GatorCache::getCache($opts = $config->toArray())->get($request->getBasePath(), $opts['group'])))){
    if($opts['last_modified'] && false !== ($fileTime = $cache->getCache()->getFileTime())){
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileTime). ' GMT');
    }
    if(!empty($opts['pingback'])){
        header('X-Pingback: ' . $opts['pingback']);
    }
    die($result . ($opts['debug'] ? "\n<!-- Served by Advanced Cache -->\n" : ''));
}
