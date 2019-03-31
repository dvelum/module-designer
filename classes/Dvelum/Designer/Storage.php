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

namespace Dvelum\Designer;

use Dvelum\Cache\CacheInterface;
use Dvelum\Config\ConfigInterface;
use Dvelum\Designer\Storage\Adapter\AbstractAdapter;

class Storage
{
    /**
     * @property CacheInterface|bool
     */
    protected static $cache = false;
    protected static $instances = [];

    /**
     * Storage adapter
     * @property AbstractAdapter
     */
    protected $adapter = null;
    /**
     * Adapter Class name
     * @property string
     */
    protected $adapterClass = null;

    /**
     * Set cache core
     * @param CacheInterface $manager
     */
    static public function setCache(CacheInterface $manager)
    {
        self::$cache = $manager;
    }

    /**
     * @param $adapter - Adapter name
     * @param ConfigInterface|null $config
     * @return self
     */
    static public function factory($adapter, ?ConfigInterface $config = null) : self
    {
        if (!isset(self::$instances[$adapter]))
            self::$instances[$adapter] = new self($adapter, $config);

        return self::$instances[$adapter];
    }

    /**
     * Storage constructor.
     * @param string $adapter
     * @param ConfigInterface|null $config
     */
    protected function __construct(string $adapter, ?ConfigInterface $config = null)
    {
        $className = '\\Dvelum\\Designer\\Storage\\Adapter\\' . ucfirst($adapter);

        if (!class_exists($className))
            trigger_error('Invalid Adapter');

        $this->adapter = new $className($config);
        $this->adapterClass = $className;

        if (!$this->adapter instanceof AbstractAdapter)
            trigger_error('Invalid Adapter');
    }

    protected function __clone(){}

    /**
     * Get Adapter object
     * @return AbstractAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Calculate cache index
     * @param string $id
     * @return string
     */
    public function cacheIndex($id)
    {
        return md5('db_query_' . $this->adapterClass . '_' . $id);
    }

    /**
     * Load Designer_Project
     * @param string $id
     * @return Project|null
     */
    public function load($id) : ?Project
    {
        $cacheIndex = $this->cacheIndex($id);

        if (self::$cache) {
            $project = self::$cache->load($cacheIndex);
            if ($project && $project instanceof Project) {
                return $project;
            }
        }

        $project = $this->adapter->load($id);

        if (!$project instanceof Project)
            return null;

        if (self::$cache)
            self::$cache->save($project, $cacheIndex);

        return $project;
    }

    /**
     * Import project from contents
     * @param $id
     * @return mixed
     */
    public function import($id)
    {
        return $this->adapter->import($id);
    }

    /**
     * Save Designer_Project
     * @param string $id
     * @param Project $obj
     * @param boolean $export
     * @return boolean
     */
    public function save($id, Project $obj, $export = false)
    {
        if (!$this->adapter->save($id, $obj, $export))
            return false;

        if (self::$cache) {
            self::$cache->save($obj, $this->cacheIndex($id));
        }
        return true;
    }

    /**
     * Remove Db_Query
     * @param string $id
     */
    public function delete($id)
    {
        if (self::$cache)
            self::$cache->remove($this->cacheIndex($id));
        return $this->adapter->delete($id);
    }

    /**
     * Get error list
     */
    public function getErrors()
    {
        return $this->adapter->getErrors();
    }
}