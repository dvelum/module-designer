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

class Store extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Store
     */
    protected $object;

    /**
     * @return bool
     */
    protected function checkObject(): bool
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();

        if (!strlen($name) || !$project->objectExists($name) || !in_array($project->getObject($name)->getClass(),
                \Designer_Project::$storeClasses, true)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);
        return true;
    }

    public function importOrmFieldsAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $objectName = $this->request->post('objectName', 'string', false);
        $fields = $this->request->post('fields', 'array', false);

        $data = \Backend_Designer_Import::checkImportORMFields($objectName, $fields);

        if (!$data) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if (!empty($data)) {
            foreach ($data as $field) {
                $this->object->addField($field);
            }
        }

        $this->storeProject();

        $this->response->success();
    }

    /**
     * Get list of store fields
     */
    public function storeFieldsAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $name = $this->request->post('object', 'string', '');
        $name = trim(str_replace(\Designer_Project_Code::$NEW_INSTANCE_TOKEN, '', $name));

        $project = $this->getProject();

        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error('Undefined Store object');
            return;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);

        if ($this->object->isInstance()) {
            $this->object = $this->object->getObject();
        }

        $fields = $this->object->fields;

        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        } elseif (is_array($fields) && !empty($fields)) {
            foreach ($fields as $name => &$field) {
                $field = $field->getConfig()->__toArray(true);
            }
            unset ($field);
        }
        $this->response->success($fields);
    }

    /**
     * Get list of object store fields
     */
    public function listStoreFieldsAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $name = $this->request->post('object', 'string', '');

        $project = $this->getProject();
        $object = $project->getObject($name);

        $store = $object->store;

        if ($store instanceof \Ext_Helper_Store) {
            if ($store->getType() == \Ext_Helper_Store::TYPE_JSCODE) {
                $this->response->success([]);
                return;
            } else {
                $store = $store->getValue();
            }
        }
        $store = trim(str_replace(\Designer_Project_Code::$NEW_INSTANCE_TOKEN, '', $store));

        if (!strlen($store) || !$project->objectExists($store)) {
            $this->response->error('Undefined Store object');
            return;
        }

        $store = $project->getObject($store);
        $this->response->success($this->prepareList($store));
    }

    protected function prepareList(\Ext_Object $object): array
    {
        if ($object->isInstance()) {
            $object = $object->getObject();
        }

        $fields = [];

        // Do not show model fields. It cause misleading
        //        $model = $object->model;
        //
        //        if(strlen($model)){
        //            $model = $project->getObject($model);
        //            $fields = $model->fields;
        //        }

        if (empty($fields)) {
            $fields = $object->fields;
        }

        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        } elseif (is_array($fields) && !empty($fields)) {
            foreach ($fields as $name => &$field) {
                $field = $field->getConfig()->__toArray(true);
            }
            unset ($field);
        }
        return $fields;
    }

    /**
     * Get list of store fields
     */
    public function listfieldsAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $name = $this->request->post('object', 'string', '');
        $name = trim(str_replace(\Designer_Project_Code::$NEW_INSTANCE_TOKEN, '', $name));

        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error('Undefined Store object');
            return;
        }

        $object = $project->getObject($name);
        $this->response->success($this->prepareList($object));
    }

    /**
     * @param $store
     * @param \Designer_Project $project
     * @return array
     */
    protected function extractFields($store, \Designer_Project $project): array
    {
        if (empty($store)) {
            return [];
        }

        if ($store->isInstance()) {
            $store = $store->getObject();
        }

        $fields = [];

        if ($store->isValidProperty('fields')) {
            $fields = $store->fields;

            if (empty($fields)) {
                $fields = [];
            }

            if (is_string($fields)) {
                $fields = json_decode($fields, true);
            }
        }

        if ($store->isValidProperty('model') && strlen($store->model) && $project->objectExists($store->model)) {
            $model = $project->getObject($store->model);

            if ($model->isValidProperty('fields')) {
                $modelFields = $model->fields;

                if (is_string($modelFields)) {
                    $modelFields = json_decode($modelFields, true);
                }

                if (!empty($modelFields)) {
                    $fields = array_merge($fields, $modelFields);
                }
            }
        }

        $data = [];
        if (!empty($fields)) {
            foreach ($fields as $item) {
                if (is_object($item)) {
                    $data[] = ['name' => $item->name, 'type' => $item->type];
                } else {
                    $data[] = ['name' => $item['name'], 'type' => $item['type']];
                }
            }

        }
        return $data;
    }

    public function allStoreFieldsAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $name = $this->request->post('object', 'string', '');

        $project = $this->getProject();

        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error('Undefined object');
            return;
        }

        $object = $project->getObject($name);
        $store = $object->store;

        if ($store instanceof \Ext_Helper_Store) {
            if ($store->getType() == \Ext_Helper_Store::TYPE_JSCODE) {
                $this->response->success([]);
                return;
            } else {
                $store = $store->getValue();
            }
        }
        $store = trim(str_replace(\Designer_Project_Code::$NEW_INSTANCE_TOKEN, '', $store));

        if (!strlen($store) || !$project->objectExists($store)) {
            $this->response->error('Undefined object');
            return;
        }

        $store = $project->getObject($store);
        $this->response->success($this->extractFields($store, $project));
    }

    public function allFieldsAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $name = $this->request->post('object', 'string', '');

        $name = trim(str_replace('[new:]', '', $name));

        $project = $this->getProject();

        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error('Undefined object');
            return;
        }

        $store = $project->getObject($name);
        $this->response->success($this->extractFields($store, $project));
    }

    public function importDbFieldsAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $connectionId = $this->request->post('connectionId', 'string', false);
        $table = $this->request->post('table', 'string', false);
        $conType = $this->request->post('type', 'integer', false);
        $fields = $this->request->post('fields', 'array', false);

        if ($connectionId === false || !$table || empty($fields) || $conType === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $conManager = new \Dvelum\Db\Manager($this->appConfig);

        try {
            $db = $conManager->getDbConnection($connectionId, $conType);
        } catch (\Exception $e) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $data = \Backend_Designer_Import::checkImportDBFields($db, $fields, $table);

        if (!$data) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if (!empty($data)) {
            foreach ($data as $field) {
                $this->object->addField($field);
            }
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Add store field
     */
    public function addFieldAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);

        if (!$id || $this->object->fieldExists($id)) {
            $this->response->error($this->lang->get('FIELD_EXISTS'));
            return;
        }


        if ($this->object->addField(array('name' => $id, 'type' => 'string'))) {
            $o = $this->object->getField($id);
            $this->storeProject();
            $this->response->success(array('name' => $o->name, 'type' => $o->type));
        } else {
            $this->response->error($this->lang->get('CANT_EXEC'));
        }
    }

    /**
     * Remove store field
     */
    public function removeFieldAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);

        if (!$id) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if ($this->object->fieldExists($id)) {
            $this->object->removeField($id);
        }

        $this->storeProject();
        $this->response->success();
    }
}