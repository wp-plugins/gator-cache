<?php 
/**
 * Gator Cache
 * 
 * A Factory class for the Cache and its associated components.
 * 
 * Copyright(c) 2013 Schuyler W Langdon
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *      
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */
class GatorCache
{
    protected static $cache;
    protected static $request;
    protected static $config;
    protected static $options;
    protected static $notices;

    public static function getCache(array $options = null){
        if(!isset(self::$cache)){
            require_once(dirname(__FILE__) . '/Cache/Wrapper.php');
            self::$cache = new Cache_Wrapper($options);
        }
        return self::$cache;
    }

    public static function getRequest(){
        if(!isset(self::$request)){
            require_once(dirname(__FILE__) . '/Request.php');
            self::$request = new Request();
        }
        return self::$request;
    }

    public static function getConfig($path, $chkPath = false){
        if(!isset(self::$config)){
            require_once(dirname(__FILE__) . '/Config/Lite.php');
            try{
                self::$config = new Config_Lite($path);
            } catch (Exception $e){
                if($chkPath){
                    unset(self::$config);
                    return false;
                }
            }
        }
        return self::$config;
    }

    public static function getOptions($key, array $defaults = null){
        if(!isset(self::$options)){
            require_once(dirname(__FILE__) . '/Config/Wp.php');
            self::$options = new Config_Wp($key, $defaults);
        }
        return self::$options;
    }

    public static function getNotices(){
        if(!isset(self::$notices)){
            require_once(($dir = dirname(__FILE__)) . '/GatorNotice.php');
            require_once($dir . '/Notice/GatorNoticeCollection.php');
            self::$notices = new GatorNoticeCollection();
        }
        return self::$notices;
    }
}
