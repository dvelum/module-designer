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
use Dvelum\App\Backend\Designer\Import;

/**
 * Class Model
 * @package Dvelum\App\Backend\Designer\Module
 */
class Model extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Grid
     */
    protected $object;

    protected function checkObject(): bool
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Model') {
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

        $data = Import::checkImportORMFields($objectName, $fields);

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

        $conManager = new \Dvelum\App\Backend\Orm\Connections($this->appConfig->get('db_configs'));
        $cfg = $conManager->getConnection($conType, $connectionId);
        if (!$cfg) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }
        $cfg = $cfg->__toArray();

        $data = \Backend_Designer_Import::checkImportDBFields($cfg, $fields, $table);

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
     * List fields
     */
    public function listFieldsAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $this->checkLoaded();
        $name = $this->request->post('object', 'string', '');

        $project = $this->getProject();

        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error('Undefined model object');
            return;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);

        $result = [];
        $fields = $this->object->getFields();

        foreach ($fields as $field) {
            if ($field instanceof \Ext_Object) {
                $result[] = $field->getConfig()->__toArray(true);
            } elseif ($field instanceof \stdClass) {
                $result[] = $field = get_object_vars($field);
            }
        }
        $this->response->success($result);
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
            $this->response->error($this->lang->get('FIELD_EXISTS'));
            return;
        }

        if ($this->object->fieldExists($id)) {
            $this->object->removeField($id);
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

        if ($this->object->addField(['name' => $id, 'type' => 'string'])) {
            $o = $this->object->getField($id);
            $this->storeProject();
            $this->response->success([
                'name' => $o->name,
                'type' => $o->type
            ]);
        } else {
            $this->response->error($this->lang->get('CANT_EXEC'));
            return;
        }
    }
}