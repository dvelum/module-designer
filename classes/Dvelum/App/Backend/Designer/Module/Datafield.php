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

class Datafield extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Data_Store
     */
    protected $object;

    /**
     * @return bool
     */
    protected function checkObject(): bool
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);
        return true;
    }

    /**
     * Set object property
     */
    public function setPropertyAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);
        $property = $this->request->post('name', 'string', false);
        $value = $this->request->post('value', 'string', false);

        if (!$id || !$this->object->fieldExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $field = $this->object->getField($id);

        if (!$field->isValidProperty($property)) {
            $this->response->error();
            return;
        }

        if ($property === 'name' && !$this->object->renameField($field->name, $value)) {
            $this->response->error();
            return;
        }

        $field->set($property, $value);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Get object properties
     */
    public function listAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);

        if (!method_exists($this->_object, 'fieldExists')) {
            $this->response->error(get_class($this->object) . '[' . $this->object->getName() . '] deprecated type');
            return;
        }

        if (!$id || !$this->object->fieldExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $field = $this->object->getField($id);
        $config = $field->getConfig();
        $properties = $config->__toArray();

        if (isset($properties['isExtended'])) {
            unset($properties['isExtended']);
        }
        $this->response->success();
    }
}