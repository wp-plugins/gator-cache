<?php 
/**
 * JqJsonResponse
 * 
 * Simple resp wrapper for some jquery stuff.
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
class JqJsonResponse
{
    protected $params = array();

    public function send($success = false){
        $this->params['success'] = $success  ? '1' : '0';
        $response = $this->buildResponse();
        $this->killBuffers();
        ob_start();
        header('Content-Type: application/json');
        die($response);
    }

    public function setParam($key, $payload){
        $this->params[$key] = $payload;
        return $this;
    }

    protected function buildResponse(){
        $payload = array();
        foreach($this->params as $key => $val){
            $payload[] = '"' . $key . '":' . json_encode($val);
        }
        return '{' . implode(',', $payload) . '}';
    }

    protected function killBuffers(){
        for($ct = count(ob_list_handlers()),$xx=0;$xx<$ct;$xx++){
            ob_end_clean();
        }
    }
}
