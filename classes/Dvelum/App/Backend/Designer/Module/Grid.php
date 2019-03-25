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

class Grid extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Grid
     */
    protected $object;

    protected function checkObject()
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Grid') {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }
        $this->project = $project;
        $this->object = $project->getObject($name);
    }

    /**
     * Get grid columns as tree list
     */
    public function columnListTreeAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $this->response->json($this->object->getColumnsList());
    }

    /**
     * Get grid columns as simple list
     */
    public function columnListAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $cols = $this->object->getColumns();
        $result = array();

        if (!empty($cols)) {
            foreach ($cols as $name => $data) {
                $object = $data['data'];
                $type = '';
                $className = $object->getClass();
                if ($className !== 'Grid_Column') {
                    $type = strtolower(str_replace('Grid_Column_', '', $className));
                }

                $editor = '';
                if (is_a($object->editor, 'Ext_Object')) {
                    $editor = $object->editor->getClass();
                }

                $filter = '';
                if (!empty($object->filter) && $object->filter instanceof \Ext_Grid_Filter) {
                    $filter = $object->filter->getType();
                }

                $result[] = [
                    'id' => $name,
                    'text' => $object->text,
                    'dataIndex' => $object->dataIndex,
                    'type' => $type,
                    'editor' => $editor,
                    'filter' => $filter,
                    'order' => $data['order']
                ];
            }
        }
        $this->response->success($result);
    }

    /**
     * Sort grid columns
     */
    public function columnSortAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);
        $newParent = $this->request->post('newparent', 'string', false);

        if (!strlen($newParent)) {
            $newParent = 0;
        }

        $order = $this->request->post('order', 'array', array());

        if (!$id || !$this->object->columnExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        $this->object->changeParent($id, $newParent);
        $count = 0;
        foreach ($order as $name) {
            if (!$this->object->setItemOrder($name, $count)) {
                $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
                return;
            }
            $count++;
        }
        $this->object->reindexColumns();
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Add grid column
     */
    public function addColumnAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $colId = $this->request->post('id', 'pagecode', '');

        if (!strlen($colId)) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        if ($this->object->columnExists($colId)) {
            $this->response->error($this->lang->get('SB_UNIQUE'));
            return;
        }

        $column = \Ext_Factory::object('Grid_Column');
        $column->text = $colId;
        $column->itemId = $colId;
        $column->setName($colId);

        if (!$this->object->addColumn($colId, $column, 0)) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove grid column
     */
    public function removeColumnAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $colId = $this->request->post('id', 'string', '');
        if (!strlen($colId)) {
            $this->response->error($this->lang->get('INVALID_VALUE') . ' code 1');
            return;
        }

        if (!$this->object->columnExists($colId)) {
            $this->response->error($this->lang->get('INVALID_VALUE') . ' code 2');
            return;
        }

        $this->object->removeColumn($colId);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Add columns
     */
    public function addColumnsAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $columns = $this->request->post('col', 'raw', false);
        if (empty($columns)) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }
        $columns = json_decode($columns, true);

        foreach ($columns as $v) {
            if ($this->object->columnExists($v['name'])) {
                $this->response->error($this->lang->get('SB_UNIQUE'));
                return;
            }

            switch ($v['type']) {
                case 'boolean':
                    $column = \Ext_Factory::object('Grid_Column_Boolean');
                    break;
                case 'integer':
                case 'float':
                    $column = \Ext_Factory::object('Grid_Column_Number');
                    break;
                case 'date':
                    $column = \Ext_Factory::object('Grid_Column_Date');
                    break;
                default:
                    $column = \Ext_Factory::object('Grid_Column');
            }

            $column->text = $v['name'];
            $column->dataIndex = $v['name'];
            $column->setName($v['name']);

            if (!$this->object->addColumn($v['name'], $column, 0)) {
                $this->response->error($this->lang->get('INVALID_VALUE'));
                return;
            }
        }
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Change grid column type
     */
    public function changeColTypeAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $type = $this->request->post('type', 'string', '');
        $columnId = $this->request->post('columnId', 'string', false);

        if (!$columnId) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if (strlen($type)) {
            $name = 'Grid_Column_' . ucfirst($type);
        } else {
            $name = 'Grid_Column';
        }

        $col = \Ext_Factory::object($name);

        \Ext_Factory::copyProperties($this->object->getColumn($columnId), $col);

        if (!$this->object->updateColumn($columnId, $col)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Save advnced properties
     */
    public function setAdvancedAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $errors = [];

        foreach (\Ext_Grid::$advancedProperties as $key => $type) {
            $value = $this->request->post($key, $type, '');
            if (!$this->object->setAdvancedProperty($key, $value)) {
                $errors[$key] = $this->lang->get('INVALID_VALUE');
            }
        }

        if (empty($errors)) {
            $this->storeProject();
            $this->response->success();
        } else {
            $this->response->error($this->lang->get('INVALID_VALUE'), $errors);
        }
    }

    /**
     * Get advanced properties for grid object
     */
    public function loadAdvancedAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $this->response->success($this->object->getAdvancedProperties());
    }
}