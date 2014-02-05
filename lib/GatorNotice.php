<?php 
/**
 * Gator Notice
 * 
 * An abstract of a notification notice.
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
class GatorNotice
{
    protected $id;
    protected $message;
    protected $priority;
    
    public function __construct($message, $id = null){
        $this->message = $message;
        if(isset($id)){
            $this->id = $id;
        }
    }

    public function getMessage(){
        return $this->message;
    }

    public function getCode(){
        return isset($this->id) ? $this->id : '0';//gotta love error code zero
    }
}
