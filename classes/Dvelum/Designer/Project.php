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

use Dvelum\Designer\Project\Code;
use Dvelum\Designer\Project\Container;
use Dvelum\Designer\Project\Events;
use Dvelum\Designer\Project\Methods;
use Dvelum\Tree\Tree;
/**
 * Designer project class.
 * @author Kirill Yegorov 2011-2019
 * @package Designer
 */
class Project
{
    static protected $reservedNames = ['_Component_', '_Layout_'];
    const COMPONENT_ROOT = '_Component_';
    const LAYOUT_ROOT = '_Layout_';

    protected static $containers = [
        'Panel',
        'Tabpanel',
        'Toolbar',
        'Form_Fieldset',
        'Form_Fieldcontainer',
        'Form_Checkboxgroup',
        'Form_Radiogroup',
        'Form',
        'Window',
        'Grid',
        'Docked',
        'Tree',
        'Menu',
        'Container',
        'Buttongroup',
        // menu containers
        'Button',
        'Button_Split',
        'Menu_Checkitem',
        'Menu_Item',
        'Menu_Separator'
    ];

    public static $hasDocked = [
        'Panel',
        'Tabpanel',
        'Form',
        'Window',
        'Grid',
        'Tree'
    ];

    public static $hasMenu = [
        'Button',
        'Button_Split',
        'Menu_Checkitem',
        'Menu_Item',
        'Menu_Separator'
    ];

    public static $defines = [
        'Window',
        'Model'
    ];

    public static $configContainers = [
        'Form',
        'Fieldcontainer',
        'Fieldset',
        'Window'
    ];

    protected static $nonDraggable = [
        'Window',
        'Model',
    ];

    public static $storeClasses = [
        'Data_Store',
        'Data_Store_Tree',
        'Data_Store_Buffered',
        'Store'
    ];

    /**
     * Objects tree
     * @var Tree $tree
     */
    protected $tree;

    /**
     * Project config
     * @var array
     */
    protected $config = [
        'namespace' => 'appComponents',
        'runnamespace' => 'appApplication',
        'files' => [],
        'langs' => []
    ];
    /**
     * Events Manager
     * @var Events
     */
    protected $eventManager = false;
    /**
     * Methods Manager
     * @var Methods
     */
    protected $methodManager = false;
    /**
     * JS Action Code
     * @var string
     */
    protected $_actionJs = '';

    public function __construct()
    {
        $this->tree = new Tree();
        $this->initContainers();
    }

    /**
     * Init system layout
     */
    protected function initContainers()
    {
        $this->tree->addItem(self::COMPONENT_ROOT, false, new Container('Components'), -1000);
        $this->tree->sortItems(self::COMPONENT_ROOT);

        $this->tree->addItem(self::LAYOUT_ROOT, false, new Container('Application'), -500);
        $this->tree->sortItems(self::LAYOUT_ROOT);
    }

    /**
     * Check if object is Window comonent
     * @param string $class
     * @return boolean
     */
    static public function isWindowComponent($class)
    {
        if (strpos($class, 'Component_Window') !== false)
            return true;
        else
            return false;
    }

    /**
     * Check if object can has parent
     * @param string $class
     * @return boolean
     */
    static public function isDraggable($class)
    {
        if (in_array($class, self::$nonDraggable, true) || self::isWindowComponent($class))
            return false;
        else
            return true;
    }

    /**
     * Check if object is container
     * @param string $class
     * @return boolean
     */
    static public function isContainer($class)
    {
        if (in_array($class, self::$containers, true) || self::isWindowComponent($class))
            return true;
        else
            return false;
    }

    static public function isVisibleComponent($class)
    {
        if (in_array($class, self::$storeClasses, true) || $class == 'Model' && strpos($class, 'Data_') !== false) {
            return false;
        }
        return true;
    }

    /**
     * Add Ext_Object to the project
     * @param string $parent - parant object name or "0" for root
     * @param \Ext_Object $object
     * @return boolean - success flag
     */
    public function addObject($parent, \Ext_Object $object)
    {
        if (strlen($parent)) {
            if (in_array($object->getClass(), self::$nonDraggable, true) && $parent !== self::COMPONENT_ROOT) {
                $parent = self::LAYOUT_ROOT;
                $object->isExtendedComponent();
            }

            if (!$this->objectExists($parent)) {
                $parent = self::LAYOUT_ROOT;
                $object->isExtendedComponent();
            }
        }
        return $this->tree->addItem($object->getName(), $parent, $object);
    }

    /**
     * Get project events Manager
     * @return Events
     */
    public function getEventManager(): Events
    {
        if ($this->eventManager === false)
            $this->eventManager = new Events();
        return $this->eventManager;
    }

    /**
     * Get project methods Manager
     * @return Methods
     */
    public function getMethodManager() : Methods
    {
        if ($this->methodManager === false)
            $this->methodManager = new Methods();
        return $this->methodManager;
    }

    /**
     * Remove object from project
     * @param string $name
     * @return bool - success flag
     */
    public function removeObject(string $name) : bool
    {
        $eventManager = $this->getEventManager();
        $methodsManager = $this->getMethodManager();

        $eventManager->removeObjectEvents($name);
        $methodsManager->removeObjectMethods($name);

        $items = $this->tree->getChildrenRecursive($name);

        if (!empty($items)) {
            foreach ($items as $id) {
                $eventManager->removeObjectEvents($id);
                $methodsManager->removeObjectMethods($id);
                $this->tree->removeItem($id);
            }
        }
        $this->tree->removeItem($name);
        return true;
    }

    /**
     * Replace object
     * @param string $name - old object name
     * @param \Ext_Object $newObject
     */
    public function replaceObject($name, \Ext_Object $newObject) : void
    {
        $this->tree->updateItem($name, $newObject);
    }

    /**
     * Change object parent
     * @param string $name - object name
     * @param string $newParent - new parent object name
     * @return bool - success flag
     */
    public function changeParent($name, $newParent) : bool
    {
        return $this->tree->changeParent($name, $newParent);
    }

    /**
     * Get project config
     * @return array
     */
    public function getConfig() : array
    {
        return $this->config;
    }

    /**
     * Set project config options
     * @param array $config
     */
    public function setConfig(array $config)
    {
        foreach ($config as $name => $value) {
            $this->config[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (!isset($this->config[$name]))
            trigger_error('Invalid config property requested');
        return $this->config[$name];
    }

    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * Set item order
     * @param mixed $id
     * @param integer $order
     * @return boolean - success flag
     */
    public function setItemOrder($id, $order)
    {
        return $this->tree->setItemOrder($id, $order);
    }

    /**
     * Resort tree Items
     * @param mixed $parentId - optional, resort only item children
     * default - false (resort all items)
     */
    public function resortItems($parentId = false)
    {
        $this->tree->sortItems($parentId);
    }

    /**
     * Check if object exists
     * @param string $name
     * @return boolean
     */
    public function objectExists($name)
    {
        return $this->tree->itemExists($name);
    }

    /**
     * Get all objects from project tree
     * @return array;  object indexed by name
     */
    public function getObjects()
    {
        $items = $this->tree->getItems();
        $data = array();
        if (!empty($items))
            foreach ($items as $config)
                $data[$config['id']] = $config['data'];
        return $data;
    }

    /**
     * Get extended components
     * @return array
     */
    public function getComponents()
    {
        $data = array();
        if ($this->tree->hasChildren(self::COMPONENT_ROOT)) {
            $items = $this->tree->hasChildren(self::COMPONENT_ROOT);
            foreach ($items as $v) {
                $data[$v['id']] = $v['data'];
            }
        }
        return $data;
    }

    /**
     * Get objects tree
     * @return Tree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Get object by name
     * @param string $name
     * @return \Ext_Object
     * @throws \Exception
     */
    public function getObject(string $name) : \Ext_Object
    {
        $objData = $this->tree->getItem($name);
        return $objData['data'];
    }

    /**
     * Get list of Store objects
     * @return array
     */
    public function getStores()
    {
        $list = $this->getObjectsByClass(['Store', 'Data_Store', 'Data_Store_Tree', 'Data_Store_Buffered', 'Object_Instance']);

        foreach ($list as $k => $v) {
            if ($v->isInstance() && !in_array($v->getObject()->getClass(),
                    ['Store', 'Data_Store', 'Data_Store_Tree', 'Data_Store_Buffered'], true)) {
                unset($list[$k]);
            }
        }
        return $list;
    }

    /**
     * Get list of Model objects
     * @return array
     */
    public function getModels()
    {
        return $this->getObjectsByClass('Model');
    }

    /**
     * Get list of Menu objects
     * @return array
     */
    public function getMenu()
    {
        return $this->getObjectsByClass('Menu');
    }

    /**
     * Get list of Grid objects
     * @return array
     */
    public function getGrids()
    {
        return $this->getObjectsByClass('Grid');
    }

    /**
     * Get objects by class
     * @param string|array $class
     * @return array, indexed by object name
     */
    public function getObjectsByClass($class)
    {
        if (!is_array($class))
            $class = array($class);

        $class = array_map('ucfirst', $class);

        $items = $this->tree->getItems();

        if (empty($items))
            return array();

        $result = array();

        foreach ($items as $config) {
            if (in_array($config['data']->getClass(), $class, true)) {
                if ($config['parent'] == self::COMPONENT_ROOT && $config['data'] instanceof \Ext_Object && !$config['data']->isInstance()) {
                    $config['data']->extendedComponent(true);
                }
                $result[$config['id']] = $config['data'];
            }
        }
        return $result;
    }

    /**
     * Check if object has children.
     * @param string $name
     * @return boolean
     */
    public function hasChildren($name)
    {
        return $this->tree->hasChildren($name);
    }

    /**
     * Check if object has instances
     * @param $name
     * @return bool
     */
    public function hasInstances($name)
    {
        $items = $this->getObjectsByClass('Object_Instance');
        if (!empty($items)) {
            foreach ($items as $object) {
                if ($object->getObject()->getName() == $name)
                    return true;
            }
        }
        return false;
    }

    /**
     * Get object children
     * @param string $name
     * @return array
     */
    public function getChildren($name)
    {
        return $this->tree->getChildren($name);
    }

    /**
     * Get parent object
     * @param string $name - object name
     * @return string | false
     */
    public function getParent($name)
    {
        $parentId = $this->tree->getParentId($name);

        if ($parentId && $this->objectExists($parentId))
            return $parentId;
        else
            return false;
    }

    /**
     * Compile project js code
     * @param array $replace - optional
     * @return string
     */
    public function getCode($replace = array())
    {
        $codeGen = new Code($this);
        if (!empty($replace))
            return Factory::replaceCodeTemplates($replace, $codeGen->getCode());
        else
            return $codeGen->getCode();
    }

    /**
     * Get object javascript source code
     * @param string $name
     * @param array $replace
     * @return string
     */
    public function getObjectCode($name, $replace = []) : string
    {
        $codeGen = new Code($this);

        if (!empty($replace)) {
            $k = array();
            $v = array();
            foreach ($replace as $item) {
                $k[] = $item['tpl'];
                $v[] = $item['value'];
            }
            return str_replace($k, $v, $codeGen->getObjectCode($name));
        } else {
            return $codeGen->getObjectCode($name);
        }
    }

    /**
     * Check if item exists
     * @param $id
     * @return bool
     */
    public function itemExist($id)
    {
        return $this->tree->itemExists($id);
    }

    /**
     * Get item data
     * @param mixed $id
     * @return mixed
     * @throws \Exception
     */
    public function getItemData($id)
    {
        return $this->tree->getItemData($id);
    }

    /**
     * Get root panels list
     * @return array
     */
    public function getRootPanels()
    {
        $list = $this->tree->getChildren('_Layout_');
        $names = [];

        if (empty($list))
            return [];

        foreach ($list as $v) {
            $object = $v['data'];
            $class = $object->getClass();

            if ($class === 'Object_Instance')
                $class = $object->getObject()->getClass();

            if (in_array($class, self::$containers, true) && $class !== 'Window' && $class != 'Menu' && !self::isWindowComponent($class))
                $names[] = $object->getName();
        }
        return $names;
    }

    /**
     * Get Application ActionJs code
     * @return string
     */
    public function getActionJs()
    {
        return $this->actionJs;
    }

    /**
     * Set Application ActionJs code
     * @param $code
     */
    public function setActionJs($code)
    {
        $this->actionJs = $code;
    }

    /**
     * Create unique component id
     * @param string $prefix
     * @return string
     */
    public function uniqueId($prefix)
    {
        if (!$this->objectExists($prefix)) {
            return $prefix;
        }

        $postfix = 1;
        while ($this->objectExists($prefix . $postfix)) {
            $postfix++;
        }
        return $prefix . $postfix;
    }
}