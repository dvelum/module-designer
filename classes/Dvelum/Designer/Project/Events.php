<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2019  Kirill Yegorov
 *  
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Dvelum\Designer\Project;

/**
 * Event manager for Designer project 
 * @author Kirill Yegorov 2012-2019  DVelum project http://code.google.com/p/dvelum/ , http://dvelum.net
 * @package Dvelum\Designer
 */
class Events
{
	protected $events = [];
	
	public function getEvents()
	{
		return $this->events;
	}
	
	/**
	 * Add/Replace object event
	 * @param string $object
	 * @param string $event
	 * @param string $code
	 * @param string $params , optional default - false
	 * @param boolean $isLocal, optional default - false
	 */
	public function setEvent($object , $event , $code , $params = false, $isLocal = false, $buffer = false)
	{
		if(!isset($this->events[$object]))
			$this->events[$object] = [];
		
		$this->events[$object][$event] = [
			'object'=>$object,
			'event'=>$event,
			'code'=>$code,
			'params'=>$params,
		    'is_local'=>$isLocal,
            'buffer'=> $buffer
		];
	}
	
	/**
	 * Check if event registered
	 * @param string $object
	 * @param string $event
	 * @return boolean
	 */
	public function eventExists($object , $event)
	{
		if(isset($this->events[$object]) && isset($this->events[$object][$event]))
			return true;
		else
			return false;
	}
	
	/**
	 * Check if Object has local events
	 * @param mixed $object
	 * @return boolean
	 */
	public function objectHasLocalEvents($object)
	{
	  $events = $this->getLocalEvents($object);
	  return !empty($events);
	}
	
	/**
	 * Update Event code
	 * @param string $id
	 * @param string $code
	 * @return boolean
	 */
	public function updateEvent($object , $event, $code)
	{
		if(!isset($this->events[$object]) || !isset($this->events[$object][$event]))
			return false;
		$this->events[$object][$event]['code'] = $code;
		return true;
	}
	
	/**
	 * Remove all events for object
	 * @param string $object
	 */
	public function removeObjectEvents($object)
	{
		if(isset($this->events[$object]))
			unset($this->events[$object]);	
	}
	
	/**
	 * Remove event for object
	 * @param string $object
     * @return void
	 */
	public function removeObjectEvent($object , $event)
	{
		if($this->eventExists($object, $event))
			unset($this->events[$object][$event]);
	}
	
	/**
	 * Get events for object
	 * @param string $object
	 * @return array
	 */
	public function getObjectEvents($object)
	{
		$result = array();
		if(isset($this->events[$object]))
			$result = $this->events[$object];
		return $result;
	}
	
	/**
	 * Get event code
	 * @param string $object
	 * @param string $event
	 * @return string
	 */
	public function getEventCode($object , $event)
	{
		if(!$this->eventExists($object, $event))
			return '';
		else 
			return $this->events[$object][$event]['code'];
	}
	
	/**
	 * Get event configuration
	 * @param string $object
	 * @param string $event
	 * @return array | false
	 */
	public function getEventInfo($object , $event)
	{
	  if(!$this->eventExists($object, $event))
	      return false;
	  else
	      return $this->events[$object][$event];
	}
	
	/**
	 * Get object events defined locally
	 * @param string $object
	 * @return array
	 */
	public function getLocalEvents($object)
	{
	  $result = array();
	  if(isset($this->events[$object]) && !empty($this->events[$object]))
	  {
	    foreach ($this->events[$object] as $name => $data)
	    {
	      if(isset($data['is_local']) && $data['is_local'])
	      {
	        $result[$name] = $data;
	      }
	    }
	  }
	  return $result;
	}

	/**
	 * Check if event exists and its local
	 * @param string $object
	 * @param string $event
	 * @return boolean
	 */
	public function isLocalEvent($object , $event)
	{
	  if(!isset($this->events[$object]) || !isset($this->events[$object][$event]))
	    return false;
	  
	  if(isset($this->events[$object][$event]['is_local']) && $this->events[$object][$event]['is_local'])
	    return true;
	  else
	    return false;
	}
	
	/**
	 * rename local event
	 * @param string $object
	 * @param string $event
	 * @param string $newName
	 * @return boolean
	 */
	public function renameLocalEvent($object , $event , $newName)
	{
	  if(!$this->isLocalEvent($object, $event))
	    return false;
	  
	  $cfg = $this->events[$object][$event];
	  $cfg['event'] = $newName;
	  unset($this->events[$object][$event]);
	  $this->events[$object][$newName] = $cfg;

		return true;
	}
	/**
	 * Convert params list to string representation
	 */
	public function paramsToString($list)
	{
		if(empty($list))
			return '';

		$params = array();
		foreach($list as $k=>$v)
		{
			if(!empty($v)){
				$params[] = $v . ' ' . $k;
			}else{
				$params[] = $k;
			}
		}
		return implode(' , ', $params);
	}
}