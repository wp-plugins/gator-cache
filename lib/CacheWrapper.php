<?php
/**
 * CacheWrapper
 *
 * Wrapper class for the Reo Classic CacheLite component.
 *
 * Copyright(c) Schuyler W Langdon
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class CacheWrapper
{
    protected $cacheTtls = array();
    protected $cache;
    protected $config = array(
        'lifetime' => 0, 'cache_dir' => '/tmp', 'enabled' => true,
        'last_modified' => false, 'pingback' => false, 'skip_ssl' => true
    );
    const DIRECTORY_INDEX = 'index.html';
    const DIRECTORY_GZIP = 'index.gz';

    public function __construct(array $config = null)
    {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Reo/Classic/CacheLite.php');
        if (isset($config)) {
            $this->config = $config + $this->config;
        }
        $this->config['lifetime'] = (int)$this->config['lifetime'];
        $this->cache = new Reo_Classic_CacheLite($this->config['cache_dir'], array(
            'lifeTime'         => $this->config['lifetime'],
            'debug'            => false,//$this->config['debug'] this will only throw exceptions when purging non-existent stuff
            'readControl'      => false,
            'hashedDirectoryUmask' => 0755,
            'fileNameHashMode' => 'apache'
        ));
        $this->config['gzip'] = extension_loaded('zlib') ? !empty($this->config['gzip']) : false;
    }

    public function save($id, $data, $group = 'page', $ttl = null)
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $result = $this->cache->save($key = $this->getKeyForUri($id), $data, $group);
        if ($result && $this->config['gzip'] && strstr($key, self::DIRECTORY_INDEX) && false !== ($data = gzencode($data, 6))) {
            $this->cache->save(str_replace(self::DIRECTORY_INDEX, self::DIRECTORY_GZIP, $key), $data, $group);//save gzipped
        }
        return $result;
    }

    public function get($id, $group = 'page')
    {
        if (!$this->config['enabled']) {
            return false;
        }
        return $this->cache->get($key = $this->getKeyForUri($id), $group);
    }

    public function has($id, $group = 'page')
    {
        if (!$this->config['enabled']) {
            return false;
        }
        return $this->cache->has($this->getKeyForUri($id), $group);
    }

    public function purge($group = 'page', $path = null)
    {
        return $this->cache->clean($group, $path);
    }

    public function remove($id, $group = 'page', $check = false)
    {
        //pretty urls
        $id = trim($id, '/');

        if ($this->config['gzip']) {
            $this->cache->remove($id . '/index.gz', $group, $check);
        }
        $result = $this->cache->remove($id . '/index.html', $group, $check);

        //zap any pagination
        $this->cache->clean($pag = $group . '/' . $id  . '/page');
        return $result;
    }

/**
 * removeGroups
 *
 * Remove id from multiple groups
 */
    public function removeGroups($id, array $groups, $check = false)
    {
        foreach ($groups as $group) {
            $result = $this->remove($id, $group, $check);
        }
        return $result;
    }

/**
 * purgeGroups
 *
 * Purge multiple groups
 */
    public function purgeGroups(array $groups)
    {
        foreach ($groups as $group) {
            $result = $this->purge($group);
        }
        return $result;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function hasCache($group = 'page')
    {
        return is_dir($this->config['cache_dir'] . DIRECTORY_SEPARATOR . $group);
    }

    public function hasCacheGroups(array $groups)
    {
        foreach ($groups as $group) {
            if ($this->hasCache($group)) {
                return true;
                break;
            }
        }
        return false;
    }

    protected function getKeyForUri($uri)
    {
        //It's not known in uri mapping if a path is file or dir, so if it isn't a file name assume a dir
        $paths = array_filter(explode('/', $uri), array($this->cache, 'filterPaths'));
        if (empty($paths)) {
            return self::DIRECTORY_INDEX;
        }
        
        if (false === strpos(end($paths), '.')) {
            $paths[] = self::DIRECTORY_INDEX;
        }
        return implode('/', $paths);
    }
}
