<?php
if(!defined('ABSPATH') || is_admin() || (defined('WP_INSTALLING') && WP_INSTALLING)
  || false === (@include_once(WP_CONTENT_DIR . '/plugins/gator-cache/lib/GatorCache.php'))//for some reason this needs parens
  || (($isMulti = is_multisite()) && (false === ($blogMap = GatorCache::getBlogMap()) || false === ($blogId = $blogMap->getBlogId())))
  || false === ($config = GatorCache::getConfig($path = ABSPATH . ($isMulti ? 'gc-config-' . $blogId . '.ini.php' : 'gc-config.ini.php'), true))
  || !$config->get('enabled')){
    return;
}
if(!defined('GC_CHK_USER')){
    for($ct = count($cookies = array_reverse(array_keys($_COOKIE))),$xx=0;$xx<$ct;$xx++){
        if(0 === strpos($cookies[$xx], 'wordpress_logged_in')){
            if($config->get('skip_user')){//skip_user prevents any more user checks if exclusions are empty
                define('GC_CHK_USER', true);
            }
            return;
        }
        if(0 === strpos($cookies[$xx], 'comment_author')){
            return;
        }
    }
}
//check for JetPack Mobile Theme
if($config->get('jp_mobile') && false !== (@include_once(WP_CONTENT_DIR . '/plugins/jetpack/class.jetpack-user-agent.php'))){
    for($xx = 0; $xx < 1; $xx++){
        if(isset($_COOKIE['akm_mobile'])){
            if('true' !== $_COOKIE['akm_mobile'] ){
                break;
            }
        }
        elseif(!jetpack_is_mobile()){
            break;
        }
        if(!$config->get('jp_mobile_cache')){
            return;
            break;
        }
        $config->set('group', $config->get('group') . '-jpmobile');//use the mobile cache
    }
}
$request = GatorCache::getRequest();
if('GET' !== $request->getMethod() || '' !== $request->getQueryString() || ($request->isSecure() && $config->get('skip_ssl'))
  || false === ($host = $config->get($request->isSecure() && $config->has('secure_host') ? 'secure_host' : 'host'))
  || $host !== $request->getHost()
  || ($config->get('dir_slash') && '/' !== substr($request->getBasePath(), -1))){
    return;
}
if(false !== ($result = GatorCache::getCache($opts = $config->toArray())->get($request->getBasePath(), $request->isSecure() ? 'ssl@' . $opts['group'] : $opts['group']))){
    if($opts['last_modified'] && false !== ($fileTime = GatorCache::getCache($opts)->getCache()->getFileTime())){
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileTime). ' GMT');
    }
    if(!empty($opts['pingback'])){
        header('X-Pingback: ' . $opts['pingback']);
    }
    die($result . ($opts['debug'] ? "\n<!-- Served by Advanced Cache " . $host . " -->\n" : ''));
}
