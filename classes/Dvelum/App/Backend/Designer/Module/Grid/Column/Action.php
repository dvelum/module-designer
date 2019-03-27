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

namespace Dvelum\App\Backend\Designer\Module\Grid\Column;

use Dvelum\App\Backend\Designer\Module;

class Action extends Module
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
     * @var \Ext_Grid_Column_Action
     */
    protected $column;

    /**
     * @return bool
     */
    protected function checkObject(): bool
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Grid') {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);

        return true;
    }

    protected function checkColumn() : bool
    {
        $object = $this->object;
        $column = $this->request->post('column', 'string', false);

        if ($column === false || $object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error('Cant find column');
            return false;
        }

        $columnObject = $object->getColumn($column);

        if ($columnObject->getClass() !== 'Grid_Column_Action') {
            $this->response->error('Invalid column type');
            return false;
        }
        
        $this->column = $columnObject;
        return true;
    }

    /**
     * Get object properties
     */
    public function listAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }

        $action = $this->request->post('id', 'string', false);

        if ($action === false || !$this->column->actionExists($action)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' invalid action');
        }
        
        $action = $this->column->getAction($action);
        $data = $action->getConfig()->__toArray();
        unset($data['handler']);
        
        $this->response->success($data);
    }

    /**
     * Set object property
     */
    public function setPropertyAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }
        
        $action = $this->request->post('id', 'string', false);

        if ($action === false || !$this->column->actionExists($action)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' invalid action');
            return;
        }

        $action = $this->column->getAction($action);

        $property = $this->request->post('name', 'string', false);
        $value = $this->request->post('value', 'raw', false);


        if (!$action->isValidProperty($property)) {
            $this->response->error('');
            return;
        }

        $action->$property = $value;
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Get column renderers CAP
     * @todo remove request from interface
     */
    public function renderersAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $this->response->success([]);
    }
}