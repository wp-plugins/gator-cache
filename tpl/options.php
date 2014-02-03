<?php if(!defined('ABSPATH') || !is_admin()){//no direct or frontend access
    exit;
}
$loading = site_url('/wp-includes/js/tinymce/themes/advanced/skins/default/img/progress.gif');
if('' === get_option('permalink_structure')){
?>
<div class="updated">
  <p><?php _e('Warning: Gator Cache will not Cache Pages without a permalink structure.', 'gatorcache');?>
    <a href="<?php echo is_multisite() ? network_admin_url('options-permalink.php') : admin_url('options-permalink.php');?>" class="button-secondary">Repair</a>
  </p>
</div>
<?}?>
<div class="wrap" id="gc_settings">
  <h2>Gator Cache <?php _e('Settings', 'gatorcache');?></h2>
  <div id="gc_load" style="width:100%;display:block;margin:2em auto;text-align:center">
    <img src="<?php echo($loading);?>"/>
    <p>Loading Gator Cache Settings</p>
  </div>
<div id="gci_tabs">
<ul>
<li><a href="#tabs-1"><?php _e('General Settings', 'gatorcache');?></a></li>
<li><a href="#tabs-2"><?php _e('Post Types', 'gatorcache');?></a></li>
<li><a href="#tabs-4"><?php _e('Users', 'gatorcache');?></a></li>
<li><a href="#tabs-6"><?php _e('Refresh Rules', 'gatorcache');?></a></li>
<li><a href="#tabs-3"><?php _e('Debug', 'gatorcache');?></a></li>
<li><a href="#tabs-5"><?php _e('Http', 'gatorcache');?></a></li>
</ul>
<div id="tabs-6">
  <form id="gci_ref" method="post" action="">
    <p style="font-size:1.0625em"><?php _e('Automatic refresh rules for your cache when new posts are published:', 'gatorcache');?></strong></p>
    <p class="result" style="display:none;color:forestgreen;font-weight:600"></p>
    <p>
      <input type="checkbox" name="rf_home" id="rf_home" value="1"<?php if($options['refresh']['home']){echo ' checked="checked"';}?>/> 
      <label for="rf_home"><?php _e('Refresh cached home page when new posts are published.', 'gatorcache');?></label> 
    </p>
    <p>
      <input type="checkbox" name="rf_archive" id="rf_archive" value="1"<?php if($options['refresh']['archive']){echo ' checked="checked"';}?>/> 
      <label for="rf_archive"><?php _e('Refresh archive pages, such as post category, when new posts in that category are published.', 'gatorcache');?></label> 
    </p>
<?php if(self::hasRecentWidgets()){?>
    <p>
      <input type="checkbox" name="rf_all" id="rf_all" value="1"<?php if($options['refresh']['all']){echo ' checked="checked"';}?>/> 
      <label for="rf_all"><?php _e('Refresh all pages. This is only necessary if your recent posts or custom posts, such as products, widget is on all pages.', 'gatorcache');?></label> 
    </p>
<?}?>
    <p><button class="button-primary"><?php _e('Update', 'gatorcache');?></button></p>
  </form>
</div>
<div id="tabs-5">
  <form id="gci_http" method="post" action="">
    <p><?php _e('Recommended webserver rules for http caching.', 'gatorcache');?></p>
    <p>Apache*:</p>
<textarea style="background:cyan;width:100%;" rows="35"># BEGIN Gator Cache
<?php 
$groupDir = $config->get('group');
if(!strstr($cacheDir = $config->get('cache_dir'), ABSPATH)){//cache dir is parallel to doc root, recommended
echo '# Important - Alias the cache directory
Alias /gator_cache/ "' . $cacheDir . '/' . $groupDir . '/"
';
}?>
<IfModule mod_mime.c>
  <FilesMatch "\.gz$">
    ForceType text/html
  </FilesMatch>
  FileETag None
  AddEncoding gzip .gz
  AddType text/html .gz
  <filesMatch "\.(html|gz)$">
    Header set Vary "Accept-Encoding, Cookie"
    Header set Cache-Control "max-age=5, must-revalidate"
  </filesMatch>
</IfModule>
Header unset Last-Modified
# These browsers may be extinct
BrowserMatch ^Mozilla/4 gzip-only-text/html
BrowserMatch ^Mozilla/4\.0[678] no-gzip
BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
# Assume mod_rewrite
RewriteEngine On
#Clients that support gzip
RewriteCond %{HTTP:Cookie} !^.*wordpress_logged_in.*$
RewriteCond %{HTTP:Accept-Encoding} gzip
RewriteCond %{ENV:no-gzip} !1
RewriteCond <?php echo $cacheDir . '/' . $groupDir;?>/%{REQUEST_URI} -d
RewriteCond <?php echo $cacheDir . '/' . $groupDir;?>/%{REQUEST_URI}index.gz -f
RewriteRule ^/?(.*)$ /gator_cache/$1index.gz [L,E=no-gzip:1]
#Clients without gzip
RewriteCond %{HTTP:Cookie} !^.*wordpress_logged_in.*$
RewriteCond %{HTTP:Accept-Encoding} !gzip [OR]
RewriteCond %{ENV:no-gzip} 1
RewriteCond <?php echo $cacheDir . '/' . $groupDir;?>/%{REQUEST_URI}index.html -f
RewriteRule ^/?(.*)$ /gator_cache/$1index.html [L]
# END Gator Cache
</textarea>
<p>*<?php _e('Copy this block to the very top of your .htaccess above the Wordpress rules. Remove any other caching plugin rules.', 'gatorcache');?></p>
  </form>
</div>
<div id="tabs-4">
  <form id="gci_usr" method="post" action="">
    <p class="result" style="display:none;color:forestgreen;font-weight:600"></p>
    <p><?php _e('By default, cached pages are not served to logged-in Wordpress Users.', 'gatorcache');?></p>
    <p><label for="gci_roles"><?php _e('Cache Pages for the following Wordpress User Roles:', 'gatorcache');?></label></p>
    <p>
      <select id="gci_roles" style="width:350px;height:24px" data-placeholder="<?php _e('Select User Roles', 'gatorcache');?>" multiple class="chosen">
<?php
    global $wp_roles;
    if(!isset($wp_roles)){
        $wp_roles = new WP_Roles();
    }
    $out = '';
    foreach($wp_roles->get_names() as $role => $name){
        $out .= '<option value="' . $role . '"';
        if(in_array($role, $options['roles'])){
            $out .= ' selected="selected"';
        }
        $out .= '>' . $name . "</option>\n";
    }
    echo $out;
?>
      </select>
    </p>
    <p><button class="button-primary"><?php _e('Update', 'gatorcache');?></button></p>
  </form>
</div>
<div id="tabs-1">
<form id="gci_gen" method="post" action="">
  <p class="result" style="display:none;color:forestgreen;font-weight:600"></p>
  <p>
    <input type="checkbox" name="enabled" id="enabled" value="1"<?php if($options['enabled']){echo ' checked="checked"';}?>/> 
    <label for="enabled"><?php _e('Enable Page Cache', 'gatorcache');?></label> 
  </p>
  <p><?php _e('Posts, Pages and selected Custom Post Types will be automatically refreshed when they are updated.');?></p>
  <p>
    <label for="gci_on"><?php _e('Cache Lifetime', 'gatorcache');?>*</label>
    <select id="lifetime_val" name="lifetime_val">
      <option value="0"><?php _e('Infinite', 'gatorcache');?></option>
      <?php echo self::rangeSelect(1, 12, $options['lifetime']['value']);?>
    </select>
    <select id="lifetime_unit" name="lifetime_unit">
      <option value="hr"><?php _e('Hours', 'gatorcache');?></option>
      <option value="day"<?php if('day' === $options['lifetime']['unit']){echo ' selected="selected"';}?>><?php _e('Days', 'gatorcache');?></option>
      <option value="week"<?php if('week' === $options['lifetime']['unit']){echo ' selected="selected"';}?>><?php _e('Weeks', 'gatorcache');?></option>
      <option value="month"<?php if('month' === $options['lifetime']['unit']){echo ' selected="selected"';}?>><?php _e('Months', 'gatorcache');?></option>
    </select>
  </p>
  <p><button class="button-primary"><?php _e('Update', 'gatorcache');?></button></p>
  <p>*<?php printf(__('Since pages are automatically refreshed a relatively high or %s lifetime can be set.', 'gatorcache'), '<em>' . __('Infinite', 'gatorcache') . '</em>');?></p>
  <p><?php _e('When new posts are published, your cached archive or category pages will be automatically refreshed.', 'gatorcache');?></p>
</form>
</div>
<div id="tabs-2">
<form id="gci_cpt" method="post" action="">
  <p class="result" style="display:none;color:forestgreen;font-weight:600"></p>
  <p>
    <?php _e('By Default Gator Cache will cache your posts and pages', 'gatorcache');?>.
  </p>
<?php $postTypes = get_post_types(array('public'   => true, '_builtin' => false), 'objects');
if($isBbPress = is_plugin_active('bbpress/bbpress.php')){//it's bbpress Jim
    unset($postTypes[bbp_get_reply_post_type()]);//reply won't have a permalink
}
if(defined('WOOCOMMERCE_VERSION')){//woocommerce
    unset($postTypes['product_variation'], $postTypes['shop_coupon']);
}
if(isset($postTypes['wooframework'])){//woothemes
    unset($postTypes['wooframework']);
}
if(empty($postTypes)){?>
  <p><?php _e('Additional post types were not found.');?></p>
<?php } else{//there are post types?>
  <p><?php _e('Select any additional Post Types to cache', 'gatorcache');?>:</p>
  <p>
    <select id="post_types" name="post_types" style="width:350px;height:24px" data-placeholder="<?php _e('Select Post Types', 'gatorcache');?>" multiple class="chosen">
<?php $out = '';
    foreach($postTypes as $post_type){
        //var_dump($post_type);
    $out .= '<option value="' . $post_type->name . '"';
        if(in_array($post_type->name, $options['post_types'])){
            $out .= ' selected="selected"';
        }
        $out .= '>' . $post_type->label . "</option>\n";
    }
    echo $out;
?>
    </select>
  </p>
  <p><button class="button-primary"><?php _e('Update', 'gatorcache');?></button></p>
<?php }?>
  <p>*<?php _e('In addition to your regular Wordpress posts and pages, you may cache other post types as well, eg WooCommerce Products.', 'gatorcache');?></p>
<?php if($isBbPress){?>
  <p>**<?php printf(__('To cache %s pages, select Forums and Topics. They will always be fresh, since Gator Cache automatically refreshes when topics are added or replies are posted.', 'gatorcache'), '<em><strong>bbPress</strong></em>');?></p>
<?php }?>
</form>
</div>
<div id="tabs-3">
<form id="gci_dbg" method="post" action="">
  <p class="result" style="display:none;color:forestgreen;font-weight:600"></p>
  <p>
    <?php _e('Include the cached date in your html source (does not show on web pages)', 'gatorcache');?>.
  </p>
  <p><input type="checkbox" name="debug" id="debug" value="1"<?php if($options['debug']){echo ' checked="checked"';}?>/> <label for="debug"><?php _e('Add debug information', 'gatorcache');?></label>
  <p><button class="button-primary"><?php _e('Update', 'gatorcache');?></button></p>
</form>
<form id="gci_del" method="post" action="">
  <p class="result" style="display:none;color:forestgreen;font-weight:600"></p>
  <p><?php _e('Purge the entire cache. All cache files will be deleted.');?> <button class="button-secondary" style="margin-left:.5em"><?php _e('Purge', 'gatorcache');?></button></p>
</form>
<p><?php _e('Tech Support Forum:', 'gatorcache');?> <a href="http://gatordev.com/support/forum/gator-cache/" target="_blank">http://gatordev.com/support/forum/gator-cache/</a></p>
<p><?php _e('Tech Support Information:', 'gatorcache');?>
<p><?php echo self::getSupportInfo();?></p>
</div>
</div>
<script type="text/javascript">
(function($){
    $('#gci_gen,#gci_usr,#gci_cpt,#gci_dbg,#gci_del,#gci_ref').submit(function(e){
        e.preventDefault();
        var res = $(this).find('.result'); 
        res.html('<img src="<?php echo $loading;?>"/>').show();
        var sel = $(this).find($('select.chosen'));
        if(1 === sel.length){
            var form = [{'name':sel.attr('id'),'value': sel.val()}];
        }
        else{
            var form = $(this).serializeArray();
        }
        form.push({'name':'action','value': $(this).attr('id')});
        var btn = $(this).find('button');
        btn.attr('disabled', true);
        $.post(ajaxurl, form, function(data){
            btn.attr('disabled', false);
            if(null === data || 'undefined' === typeof(data.success)){
                res.html('<?php _e('Unspecified Data Error', 'gatorcache');?>');
                return;
            }
            if('1' === data.success){
                res.html('undefined' !== typeof(data.msg) ? data.msg : '<?php _e('Success: Your settings have been saved', 'gatorcache');?>');
                return;
            }
            res.html('undefined' === typeof(data.error) ? '<?php _e('Error Saving Settings', 'gatorcache');?>' : data.error);
        },'json').fail(function(xhr, textStatus, errorThrown){
            res.html('<?php _e('Error: Unspecified network error.', 'gatorcache');?>');
            btn.attr('disabled', false);
        });
        return false;
    });

    $('gci_http').submit(function(e){
        e.preventDefault();
        return false;
    });
})(jQuery);
  </script>
</div>
