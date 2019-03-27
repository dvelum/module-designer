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
namespace Dvelum\App\Backend\Designer\Module\Column;

use Dvelum\App\Backend\Designer\Module;

/**
 * Class Events
 * @package Dvelum\App\Backend\Designer\Module\Column
 */
abstract class Events extends Module
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
    protected function checkInput(): bool
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return false;
        }
        return true;
    }

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

    /**
     * @return bool
     * @throws \Exception
     */
    protected function checkColumn(): bool
    {
        $object = $this->object;
        $column = $this->request->post('columnId', 'string', false);

        if ($column === false || $object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('Cant find column'));
            return false;
        }
        $columnObject = $object->getColumn($column);
        $this->column = $columnObject;
        return false;
    }

    /**
     * @param $config
     * @return string
     */
    protected function convertParams($config) : string
    {
        if (empty($config)) {
            return '';
        }

        $paramsArray = [];

        foreach ($config as $name => $type) {
            $paramsArray[] = '<span style="color:green;">' . $type . '</span> ' . $name;
        }

        return implode(' , ', $paramsArray);
    }

    /**
     * @return string|null
     */
    protected function getEvent() : ?string
    {
        $event = $this->request->post('event', 'string', false);
        if (!strlen($event) || $event === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return null;
        }
        return $event;
    }
}