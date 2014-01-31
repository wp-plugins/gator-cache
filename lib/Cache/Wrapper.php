<?php
/**
 * Cache_Wrapper
 * 
 * Wrapper class for the Cache_Lite component.
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
class Cache_Wrapper
{
    protected $cacheTtls = array();
    protected $cache;
    protected $config = array(
        'lifetime' => 0, 'cache_dir' => '/tmp', 'enabled' => true,
        'last_modified' => false, 'pingback' => false
    );

    public function __construct(array $config = null){
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Lite.php');
        if(isset($config)){
            $this->config = $config + $this->config;
        }
        $this->config['lifetime'] = (int)$this->config['lifetime'];
        $this->cache = new Cache_Lite(array('cacheDir' => $this->config['cache_dir'] . DIRECTORY_SEPARATOR, 'lifeTime' => $this->config['lifetime']));
        /*if(false !== ($cacheTtls = $this->cache->get('cacheTtls'))){
            $this->cacheTtls = unserialize($cacheTtls);
        }*/
        $this->config['gzip'] = extension_loaded('zlib') ? !empty($this->config['gzip']) : false;
    }

    public function save($id, $data, $group = 'page', $ttl = null){
        if(!$this->config['enabled']){
            return false;
        }
        $result = $this->cache->save($data, $id, $group);
        if($result && $this->config['gzip'] && false !== ($data = gzencode($data, 6))){
            $this->cache->save($data, $id, $group, 'index.gz');//save gzipped
        }
        /*if(isset($ttl)){//@todo this should be switched back out of filemode, eg $this->cache->setFileMode(false)
            $this->cacheTtls[$id] = (int)$ttl;
            $this->cache->save(serialize($this->cacheTtls), 'cacheTtls', 'ttl');
        }*/
        return $result;
    }

    public function get($id, $group = 'page'){
        if(!$this->config['enabled']){
            return false;
        }
        /*$ttl = isset($this->cacheTtls[$id]) ? $this->cacheTtls[$id] : $this->config['lifetime'];
        //var_dump($ttl);
        //var_dump($this->cache->getLifeTime());
        if($ttl !== $this->cache->getLifeTime()){
            $this->cache->setLifeTime($ttl);
        }*/
        return $this->cache->get($id, $group);
    }

    public function has($id, $group = 'page'){
        if(!$this->config['enabled']){
            return false;
        }
        /*$ttl = isset($this->cacheTtls[$id]) ? $this->cacheTtls[$id] : $this->config['lifetime'];
        if($ttl !== $this->cache->getLifeTime()){
            $this->cache->setLifeTime($ttl);
        }*/
        return $this->cache->has($id, $group);
    }

    public function purge($group = 'page'){
        return $this->cache->clean($group);
    }

    public function remove($id, $group = 'page', $check=false){
        if($this->config['gzip']){
            $this->cache->remove($id, $group, 'index.gz', $check);
        }
        return $this->cache->remove($id, $group, 'index.html', $check);
    }

    public function getCache(){
        return $this->cache;
    }

    public function hasCache($group = 'page'){
        return is_dir($this->config['cache_dir'] . DIRECTORY_SEPARATOR . $group);
    }
}
