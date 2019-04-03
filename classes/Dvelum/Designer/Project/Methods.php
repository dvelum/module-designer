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

use Dvelum\Designer\Project\Methods\Item;

/**
 * Method manager for Designer project
 * @author Kirill Egorov 2013-2019  DVelum project http://code.google.com/p/dvelum/ , http://dvelum.net
 * @package Dvelum\Designer
 */
class Methods
{
    protected $methods = [];

    /**
     * Get methods list
     * @return Item[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Add object method
     * @param string $object
     * @param string $method
     * @param array $params , optional
     * @param string $code , optional
     * @return Item | false
     */
    public function addMethod(string $object, string $method, array $params = [], $code = '')
    {
        if ($this->methodExists($object, $method)) {
            return false;
        }

        $methodObject = new Item($method);

        if (!empty($params)) {
            $methodObject->addParams($params);
        }

        if (!empty($code)) {
            $methodObject->setCode($code);
        }

        $this->methods[$object][$method] = $methodObject;
        return $methodObject;
    }

    /**
     * Get object methods
     * @param string $object
     * @return array
     */
    public function getObjectMethods(string $object)
    {
        if (isset($this->methods[$object]) && !empty($this->methods[$object])) {
            return $this->methods[$object];
        } else {
            return array();
        }
    }

    /**
     * Get method
     * @param string $object
     * @param string $method
     * @return Item | null
     */
    public function getObjectMethod(string $object, string $method): ?Item
    {
        if (!$this->methodExists($object, $method)) {
            return null;
        }

        return $this->methods[$object][$method];
    }

    /**
     * Check if method Exists
     * @param string $object
     * @param string $method
     * @return bool
     */
    public function methodExists(string $object, string $method): bool
    {
        return (isset($this->methods[$object]) && isset($this->methods[$object][$method]));
    }

    /**
     * Remove method
     * @param string $object
     * @param string $method
     */
    public function removeMethod(string $object, string $method): void
    {
        if ($this->methodExists($object, $method)) {
            unset($this->methods[$object][$method]);
        }
    }

    /**
     * Remove all object methods
     * @param string $object
     * @return void
     */
    public function removeObjectMethods(string $object): void
    {
        unset($this->methods[$object]);
    }

    /**
     * Remove all project methods
     */
    public function removeAll(): void
    {
        $this->methods = [];
    }

    /**
     * Update method
     * @param string $object
     * @param string $method
     * @param array $params
     * @param string $code
     * @return bool
     */
    public function updateMethod(string $object, string $method, array $params, $code): bool
    {
        if (!$this->methodExists($object, $method)) {
            return false;
        }

        $mObject = $this->methods[$object][$method];
        $mObject->setCode($code);
        $mObject->setParams($params);

        return true;
    }

    /**
     * Rename method
     * @param string $object
     * @param string $oldName
     * @param string $newName
     * @return bool
     */
    public function renameMethod(string $object, string $oldName, string $newName): bool
    {
        if (!$this->methodExists($object, $oldName)) {
            return false;
        }

        $mObject = $this->methods[$object][$oldName];
        $mObject->setName($newName);
        unset($this->methods[$object][$oldName]);
        $this->methods[$object][$newName] = $mObject;
        return true;
    }

    /**
     * Set method code
     * @param string $object
     * @param string $method
     * @param string $code
     * @return bool
     */
    public function setMethodCode(string $object, string $method, string $code): bool
    {
        if (!$this->methodExists($object, $method)) {
            return false;
        }

        $this->methods[$object][$method]->setCode($code);
        return true;
    }
}