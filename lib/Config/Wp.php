<?php
/**
 * Config_Wp
 * 
 * A configuration class that uses the built-in WordPress option
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
class Config_Wp
{
    protected $config = array();
    protected $key;

    public function __construct($key, array $defaults = array()){
        $this->config = false === ($options = get_option($this->key = $key)) ? $defaults : $options + $defaults;
    }

    public function get($key){
        return isset($this->config[$key]) ? $this->config[$key] : false;
    }

    public function __get($key){
        return $this->get($key);
    }

    public function set($key, $val){
        $this->config[$key] = $val;
    }

    public function setOptions(array $options){
        $this->config = $options;
    }

    public function write($config = null){
        if(isset($config)){
            $this->config = $config;
        }
        $result = update_option($this->key, $this->config);
        return true;
    }

    public function save($key, $val){//combine set and write
        $this->set($key, $val);
        return $this->write();
    }

    public function toArray(){
        return $this->config;
    }
}
