<?php
/**
 * @package Gator Cache
 * @version 1.33
 */
/*
Plugin Name: Gator Cache
Plugin URI: http://wordpress.org/plugins/gator-cache/
Description: A Better, Stronger, Faster Wordpress Cache Plugin. Easy to install and manage. 
Author: GatorDev
Author URI: http://www.gatordev.com/
Text Domain: gatorcache
Domain Path: /lang
Version: 1.33
*/
class WpGatorCache
{
    protected static $defaults = array(
        'installed' => false,
        'enabled' => false,
        'debug' => true,
        'lifetime' => array('value' => '2', 'unit' => 'week', 'sec' => 1209600),
        'post_types' => array('product'),
        'exclude_paths' => array(),
        'app_support' => array(),
        'roles' => array('subscriber'),
        'refresh' => array('home' => true, 'archive' => true, 'all' => false),
        'pingback' => false,
        'skip_ssl' => true,
        'version' => false,
    );

    protected static $options;
    protected static $path;
    protected static $configPath;
    protected static $post;
    protected static $refresh = false;
    protected static $sslHandler;
    const PREFIX = 'gtr_cache';
    const VERSION = '1.33';

    public static function initBuffer(){
        $options = self::getOptions();
        $request = GatorCache::getRequest();
        global $post;
        if(!$options['enabled'] || self::VERSION !== $options['version'] //not upgraded only needed for 1.0 > 1.11
          || '.php' === substr($path = $request->getBasePath(), -4) //uri returns the whole qs
          || (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE)
          || !isset($post)
          || ('post' !== $post->post_type && 'page' !== $post->post_type && !in_array($post->post_type, $options['post_types']))
          || 'GET' !== $request->getMethod()
          || '' !== $request->getQueryString()
          || (defined('DOING_AJAX') && DOING_AJAX)
          || is_admin() || is_user_logged_in()
          || '' === get_option('permalink_structure')
          || self::hasPathExclusion($path)
          || self::isWooCart()
          || isset($_COOKIE['comment_author_' . COOKIEHASH])
          || ($request->isSecure() && ($options['skip_ssl'] || self::sslObHandlers()))){//obhandlers has to be last
              return;
        }
        //should be good to cache
        ob_start('WpGatorCache::onBuffer');
    }

    public static function onBuffer($buffer, $phase){
        if(empty($buffer) || is_404() || !self::responseOk()){//do not cache 
            return $buffer;
        }
        $options = self::getOptions();
        if(false === ($config = GatorCache::getConfig(self::$configPath, true))){//check config is loaded
            return;
        }
        if($options['debug']){
            global $post;
            $buffer .= "\n" . '<!-- Gator Cached ' . $post->post_type . (isset(self::$sslHandler) ? ' via ' . self::$sslHandler : '') . ' on [' . gmdate('Y-m-d H:i:s', time() + (get_option('gmt_offset') * 3600)) . '] -->';
        }
        $cache = GatorCache::getCache($opts = $config->toArray());
        if(!$cache->has($path = GatorCache::getRequest()->getBasePath(), $opts['group'])){
            if(isset(self::$sslHandler) && false !== ($replace = self::doHttpsHandler($buffer))){
                $buffer = $replace;
            }
            $result = $cache->save($path, $buffer, $opts['group']);//return $result;
        }
        return $buffer;
    }

    public static function chkUser($cookie_elements, $user){
        if(!defined('GC_CHK_USER') || is_admin()){
            return;
        }
        $options = self::getOptions();
        if(!$options['enabled'] || !isset($user->roles) || !is_array($user->roles)){
            return;
        }
        $cacheme = array_intersect($options['roles'], $user->roles);
        if(!empty($cacheme)){//serve the cache
            include(WP_CONTENT_DIR . '/advanced-cache.php');
        }
    }

    public static function Activate(){
        $options = self::getOptions();
        if(!$options['installed']){//install will handle this
            return;
        }
        //check config and advance cache
        if(!self::saveWpConfig() || !self::copyAdvCache()){
            $wpConfig = GatorCache::getOptions(self::PREFIX . '_opts');
            $wpConfig->set('installed', false);
            $wpConfig->set('enabled', false);
            $wpConfig->write();
        }
    }

    public static function Deactivate(){
        //purge the cache
        self::getOptions();
        GatorCache::getCache($opts = GatorCache::getConfig(self::$configPath)->toArray())->purge($opts['group']);
        //update wp-cache setting in wp-config.php
        if(self::saveWpConfig(false)){//remove the advanced cache file
            @unlink(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'advanced-cache.php');
        }
    }

    public static function checkUpgrade(){
        if(defined('DOING_AJAX') && DOING_AJAX){
            return;
        }
        $options = self::getOptions();
        if(!$options['installed']){
            return;
        }
        //1.0 > 1.11 store the version and move the config file
        if(self::VERSION !== $options['version']){
            $version = (float)$options['version'];
            $wpConfig = GatorCache::getOptions(self::PREFIX . '_opts');
            if(1.11 > $version){//requires a reinstall
                $wpConfig->set('installed', self::$options['installed'] = false);
                $wpConfig->set('enabled', self::$options['enabled'] = false);
            }
            elseif(1.33 > $version){//add config option and advanced cache changed
                if(1.3 > $version){//ssl flag
                    GatorCache::getConfig(self::$configPath)->save('skip_ssl', true);
                }
                if(self::copyAdvCache()){
                    $wpConfig->set('version', self::$options['version'] = self::VERSION);
                }
            }
            else{//store the version
                $wpConfig->set('version', self::$options['version'] = self::VERSION);
            }
            $wpConfig->write();
        }
    }

    public static function addOptMenu(){
        add_menu_page('Gator Cache', 'Gator Cache', 'edit_posts', self::PREFIX, 'WpGatorCache::renderMenu', '', '74.5');//, self::getPath() . 'gator-icon.png'
    }

    public static function renderMenu(){
        $options = self::getOptions();
        //var_dump($options);
        if(!self::verifyInstall()){//new install or corrupted install
            include self::$path . 'tpl/install.php';
            return;
        }
        $config = GatorCache::getConfig(self::$configPath);
        include  self::$path . 'tpl/options.php';
    }

    public static function settingsLink($links){
        $links[] = '<a href="' . admin_url('admin.php?page=' . self::PREFIX) .'">Settings</a>';
        return $links;
    }

    public static function loadAdminJs($context){
        if('toplevel_page_gtr_cache' !== $context){
            return;
        }
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-selectable');
        wp_enqueue_script('chosen', ($pluginUrl = plugins_url(null, __FILE__)) . '/js/chosen.jquery.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('gator-cache', $pluginUrl . '/js/gator-cache.js', array('jquery-ui-tabs'), self::VERSION, true);
        wp_enqueue_style('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/redmond/jquery-ui.css');
        wp_enqueue_style('chosen', $pluginUrl . '/css/chosen.css');
        wp_enqueue_style('gator-cache', $pluginUrl . '/css/gator-cache.css');
        wp_enqueue_style('font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css');
    }

    public static function filterCacheUpdate($v){
        return null !== $v;
    }

    public static function pingSetting($whitelist_options){
        if(isset($_POST['option_page']) && 'discussion' === $_POST['option_page']){
            $options = self::getOptions();
            $pingback = isset($_POST['default_ping_status']) && 'open' === $_POST['default_ping_status'];
            if($pingback !== $options['pingback']){
                GatorCache::getOptions(self::PREFIX . '_opts')->save('pingback', $pingback);
                GatorCache::getConfig(self::$configPath)->save('pingback', $pingback ? get_bloginfo('pingback_url') : false);
            }
        }
        return $whitelist_options;
    }

    public static function getInitDir($inRoot = false){
        if(null === ($dir = GatorCache::getRequest()->getServer('DOCUMENT_ROOT'))
          && null === ($dir = GatorCache::getRequest()->getServer('PWD'))){
            $dir = defined(ABSPATH) ? ABSPATH : realpath('./../');
        }
        return ($inRoot ? $dir : dirname($dir)) . DIRECTORY_SEPARATOR . 'gator_cache';
    }

    public static function doInstall(){
        if(!current_user_can('edit_posts')){
            die('0');
        }
        $options = self::getOptions();
        if(!isset($_POST['gci_step']) || !ctype_digit($step = $_POST['gci_step'])){
            $step = '1';
        }
        if('2' === $step){
            if(!self::copyAdvCache()){
                $error = __('Error [103]: could not copy advance cache php file, please copy manually', 'gatorcache');
                GatorCache::getJsonResponse()->setParam('error', $error)->send(); 
            }
            if(!self::saveWpConfig()){
                $error = __('Error [104]: Could not write to your wordpress config file, please change permissions or manually insert WP_CACHE', 'gatorcache');
                GatorCache::getJsonResponse()->setParam('error', $error)->send(); 
            }
            //Installation complete
            $wpConfig = GatorCache::getOptions(self::PREFIX . '_opts');
            $wpConfig->set('installed', true);
            $wpConfig->set('version', self::VERSION);
            if('open' === get_option('default_ping_status')){
                $wpConfig->set('pingback', true);
                GatorCache::getConfig(self::$configPath)->save('pingback', get_bloginfo('pingback_url'));
            }
            $wpConfig->write();
            $msg = __('Gator Cache Successfully Installed', 'gatorcache');
            GatorCache::getJsonResponse()->setParam('msg', $msg)->send(true); 
        }
        if(is_dir($path = self::getInitDir(isset($_POST['ndoc_root']) && '1' === $_POST['ndoc_root']))){
            if(!@is_writable($path)){
                $error = sprintf(__('Error [101]: Cache Directory [%s] is not writable, please change permissions.', 'gatorcache'), $path);
                GatorCache::getJsonResponse()->setParam('error', $error)->send();
            }
            $msg = __('Cache directory exists, proceeding to Step 2', 'gatorcache');
        }
        else{
            if(false === @mkdir($path, 0755)){
                $error = __('Error [100]: Cache Directory could not be created', 'gatorcache');
                GatorCache::getJsonResponse()->setParam('error', $error)->send();
            }
            $msg = __('Cache directory created, proceeding to Step 2', 'gatorcache');
        }
        //get the group for subdir support or people that put blogs in the doc root
        if(false === ($url = parse_url($siteurl = get_option('siteurl')))){
            $error = sprintf(__('Error [105]: Could not parse site url setting [%s], please check Wordpress configuration.', 'gatorcache'), $siteurl);
            GatorCache::getJsonResponse()->setParam('error', $error)->send();
        }
        //new with ver 1.1, move config to cfg_dir
        if(!is_file(self::$configPath) && !self::copyConfigFile(ABSPATH)){
            $error = sprintf(__('Error [106]: Could not copy config file to your config directory [%s], please check permissions.', 'gatorcache'), ABSPATH);
            GatorCache::getJsonResponse()->setParam('error', $error)->send();
        }
        $group = str_replace('.', '-', $url['host']) . (empty($url['path']) || '/' === $url['path'] ? '' : str_replace('/', '-', $url['path']));
        if(!self::saveCachePath($path, $group)){
            $error = sprintf(__('Error [102]: Could not write to config file [%s], please check permissions.', 'gatorcache'), self::$configPath);
            GatorCache::getJsonResponse()->setParam('error', $error)->send();
        }
        GatorCache::getJsonResponse()->setParam('msg', $msg)->send(true);
    }

    public static function updateSettings(){
        if(!current_user_can('manage_options') || !isset($_POST['action'])){
            die();
        }
        $options = self::getOptions();
        $update = false;
        $cache = array('lifetime' => null, 'enabled' => null, 'skip_user' => null, 'debug' => null, 'skip_ssl' => null);
        switch($_POST['action']){
            case 'gci_mcd':
                if(!self::moveCache()){
                    $msg = __('Error [111]: Could not move your cache directory', 'gatorcache');
                    GatorCache::getJsonResponse()->setParam('error', $msg)->send();
                }
                GatorCache::getJsonResponse()->send(true);
            break;
            case 'gci_dir':
            case 'gci_xdir':
                if(empty($_POST['ex_dir']) || '' === ($dir = trim(wp_kses(stripslashes($_POST['ex_dir']), 'strip')))
                  || '' === $dir = trim(preg_replace('~^/+|/+$~', '', $dir))){
                    $error = __('Please enter a path name', 'gatorcache');
                    GatorCache::getJsonResponse()->setParam('error', $error)->send();
                }
                //if(!filter_var(get_option('siteurl') . ($dir = '/' . preg_replace('~\s+~', '-', $dir) . '/'), FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){}
                $key = array_search($dir = '/' . preg_replace('~\s+~', '-', $dir) . '/', $options['exclude_paths']);
                if('gci_xdir' === $_POST['action']){ 
                    if(false !== $key){
                        unset($options['exclude_paths'][$key]);
                    }
                }
                else{
                    if(false !== $key){
                        $error = __('This path is already excluded', 'gatorcache');
                        GatorCache::getJsonResponse()->setParam('error', $error)->send();
                    }
                    $options['exclude_paths'][] = $dir;
                }
                $update = true;
            break;
            case 'gci_del':
                $result = GatorCache::getCache($opts = GatorCache::getConfig(self::$configPath)->toArray())->purge($opts['group']);
                if(!$result){
                    $error = __('Cache could not be purged', 'gatorcache');
                    GatorCache::getJsonResponse()->setParam('error', $error)->send();
                }
                $msg = __('Cache successfully purged', 'gatorcache');
                GatorCache::getJsonResponse()->setParam('msg', $msg)->send(true);
            break;
            case 'gci_ref':
                $refresh = array(
                    'home'    => isset($_POST['rf_home']) && '1' === $_POST['rf_home'],
                    'archive' => isset($_POST['rf_archive']) && '1' === $_POST['rf_archive'],
                    'all'     => isset($_POST['rf_all']) && '1' === $_POST['rf_all']
                );
                $skip_ssl = !isset($_POST['cache_ssl']) || '1' !== $_POST['cache_ssl'];
                if($refresh !== $options['refresh'] || $skip_ssl !== $options['skip_ssl']){
                    $update = true;
                    $options['refresh'] = $refresh;
                    $options['skip_ssl'] = $cache['skip_ssl'] = $skip_ssl;
                }
            break;
            case 'gci_gen':
                $enabled = isset($_POST['enabled']) && '1' === $_POST['enabled'];
                if(!isset($_POST['lifetime_val']) || !ctype_digit($value = $_POST['lifetime_val'])){
                    $value = '0';
                }
                $validUnits = array('hr' => false, 'day' => false, 'week' => false, 'month' => false);
                if(!isset($_POST['lifetime_unit']) || !ctype_alpha($unit = $_POST['lifetime_unit']) || !isset($validUnits[$unit])){
                    $unit = 'hr';
                }
                if($value !== $options['lifetime']['value'] || $unit !== $options['lifetime']['unit']){
                    $update = true;
                    $mult = 'hr' === $unit ? 3600 : ('day' === $unit ? 86400: ('week' === $unit ? 604800 : 2629800));
                    $cache['lifetime'] = '0' === $value ? 0 : $mult * $value;
                    $options['lifetime'] = array('value' => $value, 'unit' => $unit, 'sec' => $cache['lifetime']);
                }
                if($enabled !== $options['enabled']){
                    $update = true;
                    $options['enabled'] = $cache['enabled'] = $enabled;
                }
            break;
            case 'gci_usr':
                if(!isset($_POST['gci_roles'])){
                    $error = __('Roles not specified', 'gatorcache'); 
                    GatorCache::getJsonResponse()->setParam('error', $error)->send();
                }
                $roles = '' === $_POST['gci_roles'] ? array() : explode(',', $_POST['gci_roles']);
                global $wp_roles;
                if(!isset($wp_roles)){
                    $wp_roles = new WP_Roles();
                }
                $validRoles = $wp_roles->get_names();
                foreach($roles as $key => $role){//for php 5.2 compat array filter not used here
                    if(!isset($validRoles[$role])){
                        unset($roles[$key]);
                    }
                }
                if($roles !== $options['roles']){
                    $update = true;
                    $options['roles'] = $roles;
                    $cache['skip_user'] = !empty($roles);
                }
            break;
            case 'gci_cpt':
                if(!isset($_POST['post_types'])){
                    $error = __('Post Types not specified', 'gatorcache'); 
                    GatorCache::getJsonResponse()->setParam('error', $error)->send();
                }
                $types = '' === $_POST['post_types'] ? array() : explode(',', $_POST['post_types']);
                $validTypes = get_post_types(array('public'   => true, '_builtin' => false));
                $isForum = false;
                $bbpTypes = array();
                if(is_plugin_active('bbpress/bbpress.php')){
                    $bbpTypes[bbp_get_reply_post_type()] = true;
                    $bbpTypes[bbp_get_topic_post_type()] = true;
                    $bbpTypes[bbp_get_forum_post_type()] = true;
                }
                foreach($types as $key => $type){//for php 5.2 compat array filter not used here
                    if(!isset($validTypes[$type])){
                        unset($types[$key]);
                        continue;
                    }
                    if(isset($bbpTypes[$type])){
                        $isForum = true;
                    }
                }
                $app_support = $options['app_support'];
                if($isForum){//add BBPress support
                    $app_support['bbpress'] = $bbpTypes;
                }
                elseif(isset($app_support['bbpress'])){
                    unset($app_support['bbpress']);
                }
                if($types !== $options['post_types'] || $app_support !== $options['app_support']){
                    $update = true;
                    $options['post_types'] = $types;
                    $options['app_support'] = $app_support;//stores the registered post types
                }
            break;
            case 'gci_dbg':
                $debug = isset($_POST['debug']) && '1' === $_POST['debug'];
                if($debug !== $options['debug']){
                    $update = true;
                    $options['debug'] = $cache['debug'] = $debug;
                }
            break;
            default:
                $error = __('Invalid Action', 'gatorcache'); 
                GatorCache::getJsonResponse()->setParam('error', $error)->send();
            break;
        }
        
        if(!$update){
            die('{"success":"0","error":"Settings were not changed"}');
        }
        $wpConfig = GatorCache::getOptions(self::PREFIX . '_opts');
        $wpConfig->write($options);//update with modified options
        //some options have to be saved to file
        $cache = array_filter($cache, 'WpGatorCache::filterCacheUpdate');//php 5.2 compat
        if(!empty($cache)){
            $config = GatorCache::getConfig(self::$configPath);
            foreach($cache as $k => $v){
                $config->set($k, $v);
            }
            $config->write();
        }
        if('gci_dir' === $_POST['action']){//include payload
            GatorCache::getJsonResponse()->setParam('xdir', $dir)->send(true);
        }
        GatorCache::getJsonResponse()->send(true);
    }

/**
 * savePost
 * 
 * Will invalidate the cache when post status is changed
 */
    public static function savePost($new_status, $old_status, $post){
        if((defined('DOING_AJAX') && DOING_AJAX)
          || '' === $post->post_name
          || (($newPost = 'publish' !== $old_status) && 'publish' !== $new_status)
          || '' === get_option('permalink_structure')){
            return;
        }
        $options = self::getOptions();
        $postTypes = array('post' => 0, 'page' => 0) + array_flip($options['post_types']);
        if(isset($options['app_support']['bbpress'])){//bbpress supported - perform ops on child types
            $postTypes = $options['app_support']['bbpress'] + $postTypes;
        }
        if(!$options['enabled'] || self::VERSION !== $options['version'] || !isset($postTypes[$post->post_type])){
            return;
        }
        $cache = GatorCache::getCache($opts = GatorCache::getConfig(self::$configPath)->toArray());
        if(!$cache->hasCache($opts['group'])){//the cache appears to be empty Jim
            return;
        }
        //return the same refresh checks for new and updated posts
        if($options['refresh']['all'] && self::hasRecentWidgets()){//purge cache so sidebar widgets refresh @note could refine by post type 'post' === $post->post_type &&
            $cache->purge($opts['group']);
            return self::$refresh = true;
        }
        //refresh parent posts and the current post
        foreach(($posts = self::getRefreshPosts($post, $newPost)) as $postId){
            if(false !== ($path = parse_url(get_permalink($postId), PHP_URL_PATH))){
                $cache->remove($path, $opts['group'], true);
            }
        }
        //refresh home page
        if($options['refresh']['home']){//refresh the home page
            $cache->remove(DIRECTORY_SEPARATOR, $opts['group']);
        }
        //refresh archive pages for this post or the last parent
        if(!$options['refresh']['archive']){
            return self::$refresh = true;
        }
        if(isset(self::$post)){//bbpress
            if(false !== ($link = get_post_type_archive_link(self::$post->post_type))
              && false !== ($path = parse_url($link, PHP_URL_PATH))){
                $cache->remove($path, $opts['group']);
            }
            return self::$refresh = true;
        }
        //taxonomy archive
        if(false !== ($terms = self::getArchiveTerms($post))){
            foreach($terms as $term){
                if(is_wp_error($termLink = get_term_link($term, $term->taxonomy))){
                    continue;
                }
                if(false !== ($path = parse_url($termLink, PHP_URL_PATH))){
                    $cache->remove($path, $opts['group']);
                }
            }
        }
        //woocommerce shop
        if('product' === $post->post_type && false !== ($link = get_permalink(woocommerce_get_page_id('shop')))
          && false !== ($path = parse_url($link, PHP_URL_PATH))){
            $cache->remove($path, $opts['group']);
        }
        self::$refresh = true;
    }

    public static function getArchiveTerms($post){
        $taxonomies = array_map('WpGatorCache::mapTaxonomies',
            array_filter(get_object_taxonomies($post, 'objects'), 'WpGatorCache::filterTaxonomies')
        );
        if(empty($taxonomies)){//only archivable taxonomies like category
            return false;
        }
        $terms = wp_get_object_terms(array($post->ID), array_values($taxonomies));
        if(empty($terms)){
            return false;
        }
        return $terms;
    }

    public static function mapTaxonomies($taxonomy){
        return $taxonomy->name;
    }

    public static function filterTaxonomies($taxonomy){
        return $taxonomy->hierarchical;
    }

    public static function savePostContext($location){
        if(self::$refresh){
            $location = add_query_arg('gtrcached', 1, $location);
        }
        return $location;
    }

    public static function savePostMsg($messages){
        if(isset($_GET['gtrcached'])){
            $options = self::getOptions();
            if(!$options['enabled']){
                return $messages;
            }
            $extra = __(' (GatorCache refreshed)', 'gatorcache');
            $messages['post'][1] =  $extra;
            $messages['page'][1] .= $extra;
            foreach($options['post_types'] as $type){
                if(isset($messages[$type])){
                   $messages[$type][1] .= $extra;
                }
            }
        }
        return $messages;
    }

    public static function saveComment($new_status, $old_status, $comment){
        if('approved' !== $new_status && 'approved' !== $old_status){//will not change page
            return;
        }
        if(null === ($path =  parse_url(get_permalink($comment->comment_post_ID), PHP_URL_PATH))){
            return;
        }
        $options = self::getOptions();
        GatorCache::getCache(
            $opts = GatorCache::getConfig(self::$configPath)->toArray()
        )->remove($path, $opts['group'], true);
    }

    public static function filterCookieLifetime($lifetime){
        return 1800;//set to reasonable lifetime, 0 won't work for life of the browser session, see wp_set_comment_cookies
    }

    public static function loadTextDomain(){
        load_plugin_textdomain('gatorcache', false, 'gator-cache/lang/' );
    }

    public static function filterStatus($header){
        return 0 === strpos($header, 'Location');//the status header is not in the return stack
    }

    public static function responseOk(){
        //in 5.4 see http_response_code
        $status = array_filter(headers_list(), 'WpGatorCache::filterStatus');//in 5.3 simply use a lambda
        return empty($status);
    }

    protected static function getOptions(){
        if(isset(self::$options)){
            return self::$options;
        }
        require_once((self::$path = plugin_dir_path(__FILE__)) . 'lib/GatorCache.php');
        self::$configPath = ABSPATH . 'gc-config.ini.php';//has to go here in case if subdir hosts
        //rather than implementing arrayaccess
        return self::$options = GatorCache::getOptions(self::PREFIX . '_opts', self::$defaults)->toArray();
    }

    protected static function hasPathExclusion($path){
        foreach(self::$options['exclude_paths'] as $exPath){
            if(strstr($path, $exPath)){
                return true;
                break;
            }
        }
        return false;
    }

    protected static function copyConfigFile($configDir){
        return false !== @copy(self::$path . 'lib' . DIRECTORY_SEPARATOR . 'config.ini.php',  $configDir . 'gc-config.ini.php');
    }

    protected static function saveCachePath($path, $group){
        if(false === ($config = GatorCache::getConfig(self::$configPath, true))){
            return false;
        }
        $config->set('cache_dir', $path);
        $config->set('group', $group);
        return $config->write();
    }

    protected static function getRefreshPosts($post, $isNew){
        $ids = array();
        if(!$isNew){
            $ids[] = $post->ID;
        }
        if(isset(self::$options['app_support']['bbpress']) && isset(self::$options['app_support']['bbpress'][$post->post_type])){//get bbpress parent posts
            self::$post = $post;//seeder
            for($xx=0;$xx<25;$xx++){
                if(false === $id = self::getParentPost(self::$post)){
                    break;
                }
                $ids[] = $id;
            }
        }
        return $ids;
    }

    protected static function getParentPost($post){
        if(0 === $post->post_parent){
            return false;
        }
        if(null !== ($parent = get_post($post->post_parent))){
            self::$post = $parent;
            return self::$post->ID;
        }
        return false;
    }

    protected static function copyAdvCache($copy = true){
        $sourceFile = self::$path . 'lib' . DIRECTORY_SEPARATOR . 'advanced-cache.php';
        if(is_file($cacheFile = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'advanced-cache.php')
          && md5_file($cacheFile) === md5_file($sourceFile)){
            return true;
        }
        return $copy ? (false !== @copy($sourceFile, $cacheFile)) : false;
    }

    protected static function saveWpConfig($wp_cache = true){
        if(defined('WP_CACHE') && $wp_cache === WP_CACHE){
            return true;
        }
        if(!is_file($file = ABSPATH . 'wp-config.php')){
            $file = dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'wp-config.php';
        }
        //backup the config just in case
        if($wp_cache){
            @copy($file, str_replace('wp-config.php', 'wp-config-bu.php'));
        }
        $fh = @fopen($file, 'r+');
        if(false === $fh){
            return false;
        }
        $lines = array();
        $pos = 0;
        $xx = 0;
        while(false !== ($buffer = fgets($fh))){
            if(!preg_match('~^define\s*\(\s*("|\')WP_CACHE\\1~', trim($buffer))){
                $lines[] = $buffer;
                if(preg_match('~^define\s*\(\s*("|\')WP_DEBUG\\1~', trim($buffer))){
                    $pos = $xx;
                }
                $xx++;
            }
        }
        fclose($fh);
        $pos++;
        $lines = array_merge(
            array_slice($lines, 0, $pos), array('define(\'WP_CACHE\', '. ($wp_cache ? 'true' : 'false') .');' . PHP_EOL), array_slice($lines, $pos)
        );
        return @file_put_contents($file, $lines);
    }

    static public function filterWidgets($name){
        return 0 === strpos($name, 'recent') && false === strpos($name, 'recently') && false === strpos($name, 'comments');
    }

    protected static function hasRecentWidgets(){
        if(false === ($sidebars = get_option('sidebars_widgets')) || empty($sidebars)){//instead of wp_get_sidebars_widgets()
            return false;
        }
        $hasRecent = false;
        foreach($sidebars as $key => $value){
            if('array_version' !== $key && is_array($value) && false === strpos($key, 'orphan') && false === strpos($key, 'inactive')){
                $recent = array_filter($value, 'WpGatorCache::filterWidgets');
                if(!empty($recent)){
                    $hasRecent = true;
                    break;
                }
            }
        }
        return $hasRecent;
    }

    protected static function rangeSelect($min, $max, $sel){
        for($max++,$xx=$min;$xx<$max;$xx++){
            $opts[] = '<option value="' . $xx . '"' . ($xx == $sel ? ' selected="selected"' : '') . '>' . $xx . '</option>';
        }
        return implode("\n", $opts);
    }

    protected static function getSupportInfo(){
        return '<textarea style="background:cyan;width:100%;" rows="6">
Wordpress: ' . get_bloginfo('version') . ' 
PHP: ' . phpversion() . '
Handler: ' . php_sapi_name() . '
System: ' . php_uname() . '
Web User: ' . get_current_user() . '
Writable: ' . (is_writable(self::$path . 'lib' . DIRECTORY_SEPARATOR . 'config.ini.php') ? 'Yes' : 'No') . '
</textarea>';
        //Path: ' . $path; echo var_export($options);echo var_export($config->toArray());
    }

    protected static function isWooCart(){//don't cache the mini-cart, lots of themes php code it
        global $woocommerce;
        return defined('WOOCOMMERCE_VERSION') && isset($woocommerce) && isset($woocommerce->cart) && 0 < $woocommerce->cart->cart_contents_count;
    }

    protected static function verifyInstall(){
        //check install flag
        if(!self::$options['installed']){
            return false;
        }
        //config file missing or corrupted
        if(!is_file(self::$configPath) || false === ($config = GatorCache::getConfig(self::$configPath))){
            $msg = __('Your Gator Cache configuration file is missing or corrupted.', 'gatorcache');
            GatorCache::getNotices()->add($msg, '107');
            self::disableCache(false);//requires reinstall
            return false;
        }
        //cache directory is missing or set to the default
        if('/tmp' === ($cacheDir = $config->get('cache_dir')) || !is_dir($cacheDir)){
            $msg = __('Your Gator Cache directory is missing or no longer set.', 'gatorcache');
            GatorCache::getNotices()->add($msg, '108');
            self::disableCache();//requires reinstall
            return false;
        }
        //check wp cache is set and the right adv cache is present
        if((defined('WP_CACHE') && WP_CACHE) && self::copyAdvCache(false)){
            return true;
        }
        //attempt to repair
        if(!($wpCache = self::saveWpConfig()) || !self::copyAdvCache()){
            if(!$wpCache){
                $msg = __('Your Wordpress configuration file could not be updated.', 'gatorcache');
                $code = '109';
            }
            else{
                $msg = __('Your advanced cache file is missing or corrupted.', 'gatorcache');
                $code = '110';
            }
            GatorCache::getNotices()->add($msg, $code);
            self::disableCache();//requires reinstall
            return false;//requires reinstall
        }
        return true;
    }

    protected static function sslObHandlers(){
        $buffers = ob_list_handlers();
        if(empty($buffers)){
            return false;
        }
        for($pos = false, $ct = count($buffers), $xx=$ct-1;$xx>-1;$xx--){
            if(0 === strpos($buffers[$xx], 'WordPressHTTPS')){//look for the https plugin ob handler
                $pos = $xx;
                self::$sslHandler = $buffers[$xx];
                break;
            }
        }
        if(false !== $pos){//kill the https cache buffer and whatever other ones are there on the way
            for($num = $ct - $pos, $xx=0;$xx<$num;$xx++){
                ob_end_clean();
            }
        }
        return false;
    }

    protected static function doHttpsHandler($buffer){
        global $wordpress_https;
        //recent versions us a module
        $module = false;
        list($class, $method) = explode('::', self::$sslHandler);
        if(strstr($class, 'Module_Parser')){
            $module = $wordpress_https->getModule('Parser');
        }
        if(isset($wordpress_https) && isset($method) && method_exists(false === $module ? $wordpress_https : $module, $method)){
            $out = false === $module ? $wordpress_https->{$method}($buffer) : $module->{$method}($buffer);//let WordPressHTTPS parse out theme developers src tag shananigans
            if(!empty($out)){
                return $out;
            }
        }
        return false;
    }

    protected static function disableCache($all = true){
        GatorCache::getOptions(self::PREFIX . '_opts')->save('enabled', false);
        if($all){
            GatorCache::getConfig(self::$configPath)->save('enabled', false);
        }
    }

    protected static function moveCache($docRoot = true){
        $config = GatorCache::getConfig(self::$configPath);
        if(!is_dir($cacheDir = ABSPATH . 'gator_cache')){
            if(!@rename($config->get('cache_dir'), $cacheDir)){
                return false;
            }
        }
        elseif(!is_writable($cacheDir)){
                return false;
        }
        return $config->save('cache_dir', $cacheDir);
    }
}
//Hooks
register_activation_hook(__FILE__, 'WpGatorCache::Activate');
register_deactivation_hook(__FILE__, 'WpGatorCache::Deactivate');
add_action('auth_cookie_valid', 'WpGatorCache::chkUser', 5, 2);
add_action('wp', 'WpGatorCache::initBuffer', 5);//after_setup_theme
add_action('init', 'WpGatorCache::loadTextDomain');
//admin settings
if(is_admin()){
    add_action('admin_menu', 'WpGatorCache::addOptMenu', 8);
    add_action('admin_init', 'WpGatorCache::checkUpgrade');
    add_action('admin_enqueue_scripts', 'WpGatorCache::loadAdminJs', 111);
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WpGatorCache::settingsLink');
    //installation ajax
    add_action('wp_ajax_gcinstall', 'WpGatorCache::doInstall');
    //settings
    add_action('wp_ajax_gci_gen', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_usr', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_cpt', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_dbg', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_del', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_ref', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_dir', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_xdir', 'WpGatorCache::updateSettings');
    add_action('wp_ajax_gci_mcd', 'WpGatorCache::updateSettings');
    add_filter('whitelist_options', 'WpGatorCache::pingSetting');
    add_filter('redirect_post_location', 'WpGatorCache::savePostContext');
    add_filter('post_updated_messages', 'WpGatorCache::savePostMsg', 11);
}
add_action('transition_post_status', 'WpGatorCache::savePost', 11111, 3);
add_action('transition_comment_status', 'WpGatorCache::saveComment', 11, 3);
add_filter('comment_cookie_lifetime', 'WpGatorCache::filterCookieLifetime', 11111);
