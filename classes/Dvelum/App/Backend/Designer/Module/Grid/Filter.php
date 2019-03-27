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

namespace Dvelum\App\Backend\Designer\Module\Grid;

use Dvelum\App\Backend\Designer\Module;

class Filter extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Grid
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
     * Get object properties
     */
    public function listAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);

        if (!$id || !$this->object->getFiltersFeature()->filterExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $filter = $this->object->getFiltersFeature()->getFilter($id);
        $config = $filter->getConfig();
        $properties = $config->__toArray();

        $unset = ['isExtended', 'listeners', 'type'];
        foreach ($unset as $property) {
            if (isset($properties[$property])) {
                unset($properties[$property]);
            }
        }

        $this->response->success($properties);
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
        $value = $this->request->post('value', 'raw', false);

        if (!$id || !$this->_object->getFiltersFeature()->filterExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $object = $this->object->getFiltersFeature()->getFilter($id);
        if (!$object->isValidProperty($property)) {
            $this->response->error();
            return;
        }

        $object->set($property, $value);
        $this->storeProject();

        $this->response->success();
    }
}