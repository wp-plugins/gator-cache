<?php if(!defined('ABSPATH')){exit;}?>
# BEGIN Gator Cache
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
RewriteCond %{HTTP_HOST} ^<?php echo($host = str_replace('.', '\.', $config->get('host')));?>$ 
RewriteCond %{HTTP:Cookie} !^.*(wordpress_logged_in|comment_author).*$
RewriteCond %{HTTP:Accept-Encoding} gzip
RewriteCond %{ENV:no-gzip} !1
RewriteCond <?php echo $cacheDir . '/' . $groupDir;?>/%{REQUEST_URI} -d
RewriteCond <?php echo $cacheDir . '/' . $groupDir;?>/%{REQUEST_URI}index.gz -f
RewriteRule ^/?(.*)$ /gator_cache/$1index.gz [L,E=no-gzip:1]
#Clients without gzip
RewriteCond %{HTTP_HOST} ^<?php echo($host);?>$ 
RewriteCond %{HTTP:Cookie} !^.*(wordpress_logged_in|comment_author).*$
RewriteCond %{HTTP:Accept-Encoding} !gzip [OR]
RewriteCond %{ENV:no-gzip} 1
RewriteCond <?php echo $cacheDir . '/' . $groupDir;?>/%{REQUEST_URI}index.html -f
RewriteRule ^/?(.*)$ /gator_cache/$1index.html [L]
<?php echo '# END Gator Cache';?>
