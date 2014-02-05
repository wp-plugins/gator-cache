<?php 
/**
 * Gator Notice Collection
 * 
 * A collection class for Gator Notices.
 * 
 * Copyright(c) 2014 Schuyler W Langdon
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
class GatorNoticeCollection
{
    protected $data = array();
    
    public function __construct(array $notices = null){
        if(isset($notices)){
            $this->data = $notices;
        }
    }

    public function get(){
        return empty($this->data) ? false : end($this->data);
    }

    public function add($message, $code = null){
        $this->data[$code] = new GatorNotice($message, $code);
    }

    public function has($priority = null){
        return !empty($this->data);//or array filter by priority
    }

    public function all(){
        return $this->data;
    }

    public function dismissAll(){
        $this->data = array();
    }
}
