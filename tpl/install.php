<?php if(!defined('ABSPATH') || !is_admin()){//no direct or frontend access
    exit;
}
$notices = GatorCache::getNotices();
if($notices->has()){?>
  <div class="updated">
    <p><strong><?php _e('Error Code')?> <?php echo $notices->get()->getCode();?></strong>: <?php echo $notices->get()->getMessage();?> <strong><?php _e('Re-installation Required')?></strong></p>
  </div>
<?php }?>
<div class="wrap">
  <h2>Gator Cache <?php _e('Installation', 'gatorcache');?></h2>
  <h3><?php _e('Automated 2-step Gator Cache Install', 'gatorcache');?></h3>
  <div id="gci_result" style="display:none;margin:1em 0;color:forestgreen;font-weight:600"></div>
  <form id="gci_install" method="post" action="">
    <div id="step_1">
      <label><?php printf('<strong>%s</strong>) %s', __('Step 1', 'gatorcache'), __('Create Cache Directory', 'gatorcache'));?>:</label> <strong><?php echo($cacheDir = self::getInitDir());?></strong>*
      <input type="submit" id="gci_btn" name="gci_btn" class="button-primary" style="margin: 1em 0 1em 1em" value="Install"/>
      <p id="block_inroot" style="display:none">
        <span style="display:block;margin 1em 0;color:firebrick;font-weight:600"><?php _e('Gator Cache could not create your cache directory, please manually create the directory shown in the path above. Alternatively, your host may be set up to only allow the web server to write to files in the document root. While this is not a good practice, you can check the box below to create the cache directory in your document root.', 'gatorcache');?></span></br>
        <input type="checkbox" id="in_root" name="in_root" value="1"/> <label for="in_root"><?php _e('Create Cache Directory In Document Root (Not Recommended)', 'gatorcache');?></label>
      </p>
      <p>*<?php _e('Gator Cache will attempt to install your cache directory parallel to your document root, if it does not already exist.', 'gatorcache');?></p>
    </div>
    <div id="step_2" style="display:none">
      <p><?php printf('<strong>%s</strong>) %s', __('Step 2', 'gatorcache'), __('Copy Php Advanced Cache File and Update Wordpress Config', 'gatorcache'));?>:</label>
      <input type="submit" id="gci_btn2" name="gci_btn2" class="button-primary" style="margin: 1em 0 1em 1em" value="Install"/>
      <p>*<?php printf('%s <strong>advance_cache.php</strong> %s', __('Gator Cache will attempt to copy', 'gatorcache'), __('to Wordpress', 'gatorcache'));?></p>
    </div>
    <input type="hidden" id="gci_step" name="gci_step" value="1"/>
  </form>
  <script type="text/javascript">
(function($){
    $('#gci_btn2').click(function(){
        $('#gci_step').val('2');
        $(this).attr('disabled', true);
        return true;
    });
    $('#gci_install').submit(function(e){
        var step = $('#gci_step').val();
        e.preventDefault();
        $('#gci_result').html('<img src="<?php echo($loading = site_url('/wp-includes/js/tinymce/themes/advanced/skins/default/img/progress.gif'));?>"/>').show();
        var form = $(this).serializeArray();
        form.push({'name':'action','value':'gcinstall'});
        if($('#in_root').prop('checked')){
            form.push({'name':'ndoc_root','value':'1'});
        }
        $('#gci_btn').attr('disabled', true);
        $.post(ajaxurl, form, function(data){
            if(null === data || 'undefined' === typeof(data.success)){
                $('#gci_btn').attr('disabled', false);
                $('#gci_btn2').attr('disabled', false);
                $('#gci_result').html('<?php _e('Unspecified Data Error', 'gatorcache');?>');
                return;
            }
            if('1' === data.success){
                if('2' === step){
                    $('#gci_result').html('<?php _e('Gator Cache Successfully Installed, Refreshing', 'gatorcache');?> <img style="vertical-align:middle" src="<?php echo $loading;?>"/>');
                    window.setTimeout(function(){
                        window.location.replace("<?php echo admin_url('admin.php?page=gtr_cache');?>");
                    }, 1000);
                }
                else{
                    $('#gci_result').html('undefined' === typeof(data.msg) ? '<?php _e('Success: Your settings have been saved', 'gatorcache');?>' : data.msg);
                    $('#gci_btn2').attr('disabled', false);
                    $('#step_1').hide();
                    $('#step_2').show()
                }
                return;
            }
            $('#gci_btn').attr('disabled', false);
            $('#gci_btn2').attr('disabled', false);
            $('#gci_result').html('undefined' === typeof(data.error) ? '<?php _e('Error Saving Settings', 'gatorcache');?>' : data.error);
            $('#block_inroot').show();
        },'json').fail(function(xhr, textStatus, errorThrown){
            $('#gci_result').html('<?php _e('Error: Unspecified network error', 'gatorcache');?>.');
            $('#gci_btn').attr('disabled', false);
            $('#gci_btn2').attr('disabled', false);
        });
        return false;
    });
})(jQuery);
  </script>
  <h2><?php _e('Troubleshooting Code Reference Guide', 'gatorcache');?></h3>
  <p><strong><?php _e('Tech Support', 'gatorcache');?></strong>: <strong><a href="http://gatordev.com/support/forum/gator-cache/" target="_blank">http://gatordev.com/support/forum/gator-cache/</a></strong></p>
  <p><?php _e('Tech Support Information:', 'gatorcache');?>
  <p><?php echo self::getSupportInfo();?></p>
  <p><span style="background:gold"><strong>100</strong> <em><?php _e('Cache Directory could not be created', 'gatorcache');?></em></span> - <?php printf('Manually create the cache directory, <strong>%s</strong>. Change the ownership to <strong>%s</strong>. If this is not possible with your hosting, the permissions can be set to "0777" with your ftp client or file manager, however, this is not recommended.', $cacheDir, $webUser = get_current_user());?></p>
  <p><span style="background:gold"><strong>101</strong> <em><?php _e('Cache Directory is not writable', 'gatorcache');?></em></span>  - <?php _e('Change the ownership or permissions as mentioned in Error Code 100.', 'gatorcache');?></p>
  <p><span style="background:gold"><strong>102</strong> <em><?php _e('The Gator Cache config file is not writable', 'gatorcache');?></em></span>  - <?php printf('Change the ownership of <strong>%s</strong> to <strong>%s</strong>. If this is not possible with your hosting, the file permissions should be set to "0777".', ABSPATH . 'gc-config.ini.php', $webUser);?></p>
  <p><span style="background:gold"><strong>103</strong> <em><?php _e('Wordpress cache file could not be copied', 'gatorcache');?></em></span>  - <?php printf('%s <strong>%s</strong> %s: <strong>%s</strong>.', __('Manually copy ', 'gatorcache'), self::$path . 'lib/advanced-cache.php', __('to the following directory', 'gatorcache'), WP_CONTENT_DIR . DIRECTORY_SEPARATOR);?></p>
  <p><span style="background:gold"><strong>104</strong> <em><?php _e('Could not write to your Wordpress config file', 'gatorcache');?></em></span>  - <?php printf('%s <strong>wp-config.php</strong> %s:', __('Manually add this line to your ', 'gatorcache'), __('file', 'gatorcache'));?><br/><code>define('WP_CACHE', true);</code><br/><?php _e('Typically this is added after the WP_DEBUG line.', 'gatorcache');?></p>
</div>
