<?php 
/**
 * GatorBlogMap
 * 
 * A class for mapping blog urls to id on WordPress Multisite.
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
class GatorBlogMap
{
    protected $config;
    protected $request;
    protected static $mapPath;

    public function __construct($config, $request){
        $this->config = $config;
        $this->request = $request;
    }

/**
 * getBlogId
 * 
 * Gets the blog ID from the saved configuration based upon request host and path.
 */ 
    public function getBlogId(){
        if((!defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL) && '/' !== ($path = GatorCache::getRequest()->getBasePath()) && '' !== $path){//subdir configuratin
            //get first subdir path for a match
            $dirs = explode('/', $path);
            if(false !== ($id = $this->config->get($this->request->getHost() . '/' . ('' === $dirs[0] ? $dirs[1] : $dirs[0])))){
                return $id;
            }
        }
        //subdomain config or subdir parent
        return $this->config->get($this->request->getHost());
    }

    public function saveBlogId($name, $blogId){
        if(empty($name)){//will corrupt ini file
            return;
        }
        $map = $this->config->toArray();
        if(!isset($map[$name])){
            if(false !== ($key = array_search($blogId, $map))){
                $this->config->remove($key);//replace old mapping
            }
        }
        $this->config->save($name, $blogId);
    }

    public function isBlogId($blogId){
        return in_array($blogId, $this->config->toArray());
    }

    public function getHost($blogId){
        return array_search($blogId, $this->config->toArray());
    }

    public static function getPath(){
        if(!isset(self::$mapPath)){
            self::$mapPath = ABSPATH . 'gc-blogs.ini.php';
        }
        return self::$mapPath;
    }
}
