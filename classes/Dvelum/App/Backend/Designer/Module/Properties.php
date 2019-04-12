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

namespace Dvelum\App\Backend\Designer\Module;

use Dvelum\App\Backend\Designer\Module;
use Dvelum\App\Dictionary\Manager;
use Dvelum\Config;
use Dvelum\File;
use Dvelum\Filter;
use Dvelum\Utils;

class Properties extends Module
{
    /**
     * Get object properties
     */
    public function listAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }
        $object = $this->getObject();

        $class = $object->getClass();
        $properties = $object->getConfig()->__toArray();

        /*
         * Hide unused properties
         */
        switch ($class) {
            case 'Docked':
                unset($properties['items']);
                break;
            case 'Object_Instance':
                unset($properties['defineOnly']);
                unset($properties['listeners']);
                break;
        }
        //unset($properties['isExtended']);
        unset($properties['extend']);

        if (isset($properties['dockedItems'])) {
            unset($properties['dockedItems']);
        }

        if (isset($properties['menu'])) {
            unset($properties['menu']);
        }

        if (isset($properties['store'])) {
            $properties['store'] = '';
        }

        $this->response->success($properties);
    }

    /**
     * Set object property
     */
    public function setPropertyAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $object = $this->getObject();
        $project = $this->getProject();

        $property = $this->request->post('name', 'string', false);
        $value = $this->request->post('value', 'raw', false);

        if (!$object->isValidProperty($property)) {
            $this->response->error('');
            return;
        }

        if ($property === 'isExtended') {
            $parent = $project->getParent($object->getName());
            if ($parent) {
                $this->response->error($this->lang->get('CANT_EXTEND_CHILD'));
                return;
            }
        }

        $object->set($property, $value);

        $this->storeProject();

        $this->response->success();
    }

    /**
     * Get list of existing ORM dictionaries
     */
    public function listDictionariesAction()
    {
        $manager = Manager::factory();
        $list = $manager->getList();
        $data = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                $data[] = ['id' => $v, 'title' => $v];
            }
        }

        $this->response->json($data);
    }

    /**
     * Get list of store filds
     */
    public function storeFieldsAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $object = $this->getObject();
        $project = $this->getProject();

        if (!$object->isValidProperty('store')) {
            $this->response->json([]);
            return;
        }

        $storeName = str_replace([\Designer_Project_Code::$NEW_INSTANCE_TOKEN, ' '], '', $object->store);

        if (!$project->objectExists($storeName)) {
            $this->response->json([]);
            return;
        }

        $store = $project->getObject($storeName);

        if ($store instanceof \Ext_Object_Instance) {
            $store = $store->getObject();
        }

        $fields = [];


        if ($store->isValidProperty('model') && strlen($store->model) && $project->objectExists($store->model)) {
            $model = $project->getObject($store->model);

            if ($model->isValidProperty('fields')) {
                $fields = $model->fields;
                if (is_string($fields)) {
                    $fields = json_decode($model->fields, true);
                }
            }
        }

        if (empty($fields) && $store->isValidProperty('fields')) {
            $fields = $store->fields;

            if (empty($fields)) {
                $fields = [];
            }

            if (is_string($fields)) {
                $fields = json_decode($fields, true);
            }
        }

        $data = [];
        if (!empty($fields)) {
            foreach ($fields as $item) {
                if (is_object($item)) {
                    $data[] = ['id' => $item->name];
                } else {
                    $data[] = ['id' => $item['name']];
                }
            }
        }

        $this->response->success($data);
    }

    /**
     * Get list of existing form field adapters
     */
    public function listAdaptersAction()
    {
        $data = [];
        $autoloaderPaths = Config::storage()->get('autoloader.php')->get('paths');
        $files = [];
        $classes = [];

        foreach ($autoloaderPaths as $path) {
            $scanPath = $path . '/' . $this->designerConfig->get('field_components');
            if (is_dir($scanPath)) {
                $files = array_merge($files, File::scanFiles($scanPath, ['.php'], true, File::Files_Only));
                if (!empty($files)) {
                    foreach ($files as $item) {
                        $class = Utils::classFromPath(str_replace($autoloaderPaths, '', $item));
                        if (!in_array($class, $classes)) {
                            $data[] = [
                                'id' => $class,
                                'title' => str_replace($scanPath . '/', '', substr($item, 0, -4))
                            ];
                            array_push($classes, $class);
                        }
                    }
                }
            }
        }
        $this->response->json($data);
    }

    /**
     * Change field type
     */
    public function changetypeAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $object = $this->getObject();
        $type = $this->request->post('type', 'string', false);
        $adapter = $this->request->post('adapter', 'string', false);
        $dictionary = $this->request->post('dictionary', 'string', false);

        if ($type === 'Form_Field_Adapter') {
            $newObject = \Ext_Factory::object($adapter);
            /*
             * Invalid adapter
             */
            if (!$adapter || !strlen($adapter) || !class_exists($adapter)) {
                $this->response->error($this->lang->get('INVALID_VALUE'),
                    ['adapter' => $this->lang->get('INVALID_VALUE')]);
                return;
            }


            if ($adapter === 'Ext_Component_Field_System_Dictionary') {
                /*
                 * Inavalid dictionary
                 */
                if (!$dictionary || !strlen($dictionary)) {
                    $this->response->error($this->lang->get('INVALID_VALUE'),
                        ['dictionary' => $this->lang->get('INVALID_VALUE')]);
                    return;
                }

                $newObject->dictionary = $dictionary;
                $newObject->displayField = 'title';
                $newObject->valueField = 'id';
            }
        } else {
            $newObject = \Ext_Factory::object($type);
            /*
             * No changes
             */
            if ($type === $object->getClass()) {
                $this->response->success();
                return;
            }
        }

        \Ext_Factory::copyProperties($object, $newObject);
        $newObject->setName($object->getName());
        $this->getProject()->replaceObject($object->getName(), $newObject);
        $this->storeProject();

        $this->response->success();
    }

    public function storeLoadAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $object = $this->getObject();
        $data = [];

        $store = $object->store;

        if (empty($store) || is_string($store)) {
            if (strpos($store, \Designer_Project_Code::$NEW_INSTANCE_TOKEN) !== false) {
                $data = [
                    'type' => 'instance',
                    'store' => trim(str_replace(\Designer_Project_Code::$NEW_INSTANCE_TOKEN, '', $store))
                ];
            } else {
                $data = [
                    'type' => 'store',
                    'store' => $store
                ];
            }

        } elseif ($store instanceof \Ext_Helper_Store) {
            $data = [
                'type' => $store->getType(),
            ];
            switch ($store->getType()) {
                case \Ext_Helper_Store::TYPE_STORE:
                    $data['store'] = $store->getValue();
                    break;
                case \Ext_Helper_Store::TYPE_INSTANCE:
                    $data['instance'] = $store->getValue();
                    break;
                case \Ext_Helper_Store::TYPE_JSCODE:
                    $data['call'] = $store->getValue();
                    break;
            }
        }
        $this->response->success($data);
    }

    public function storeSaveAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $object = $this->getObject();

        $storeHelper = new \Ext_Helper_Store();

        $type = $this->request->post('type', 'string', false);

        if (!in_array($type, $storeHelper->getTypes(), true)) {
            $this->response->error($this->lang->get('FILL_FORM'), ['type' => $this->lang->get('INVALID_VALUE')]);
            return;
        }

        $storeHelper->setType($type);

        switch ($type) {
            case \Ext_Helper_Store::TYPE_STORE:
                $storeHelper->setValue($this->request->post('store', Filter::FILTER_RAW, ''));
                break;
            case \Ext_Helper_Store::TYPE_INSTANCE:
                $storeHelper->setValue($this->request->post('instance', Filter::FILTER_RAW, ''));
                break;
            case \Ext_Helper_Store::TYPE_JSCODE:
                $storeHelper->setValue($this->request->post('call', Filter::FILTER_RAW, ''));
                break;
        }

        $object->store = $storeHelper;
        $this->storeProject();
        $this->response->success();
    }

}
