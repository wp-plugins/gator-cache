<?php
/**
 * Config_Lite
 * 
 * A configuration class that uses the built-in PHP config ini file
 * functionality for storage.
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
class Config_Lite
{
    protected $config = array();
    protected $path;

    public function __construct($path, $default = null, $verify = false){
        if(false === ($config = @parse_ini_file($this->path = $path))){
            if(isset($default)){
                $this->config = $default;
            }
            $this->path = null;
            if($verify){//unfortunately if you throw an exception in the constructor the class won't load
                throw new InvalidArgumentException(sprintf('Unable to parse config ini file [%s]', $path));
            }
        }
        else{
            $this->config = $config;
        }
    }

    public function get($key){
        return isset($this->config[$key]) ? $this->config[$key] : false;//this should return null
    }

    public function set($key, $val){
        $this->config[$key] = $val;
    }

    public function has($key){
        return isset($this->config[$key]);
    }

    public function toArray(){
        return $this->config;
    }

    public function write($path = null){
        if(!($usePath = isset($path)) && !isset($this->path)){//fubar
            return false;
        }
        $this->out = array('<?php exit;?>');
        $this->format($this->config);
        $fp = @fopen($usePath ? $path : $this->path, 'w');
        if($fp){
            @flock($fp, LOCK_EX);
            @fwrite($fp, implode("\n", $this->out));
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }
        return false;
    }

    public function save($key, $val){//combine set and write
        $this->set($key, $val);
        return $this->write();
    }

    protected function format(array $config){
        foreach($config as $key => $val){ 
            if(is_array($val)){
                $this->out[] = '[' . $key . ']';
                $this->format($val);
            }
            else{
                $this->out[] = $key . ' = ' . (is_bool($val) ? (int)$val : (is_numeric($val) || ctype_digit($val) ? $val : '"' . $val . '"'));
            }
        }
    }
}
