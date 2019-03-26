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
/**
 * Operations with forms
 */

use Dvelum\App\Backend\Designer\Module;
use Dvelum\Config\ConfigInterface;
use Dvelum\Orm;

class Form extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Grid
     */
    protected $object;

    protected function checkObject() : bool
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Form'){
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);
        return true;
    }

    /**
     * Import fields into the form object
     */
    public function importFieldsAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        $importObject = $this->request->post('importobject', 'string', false);
        $importFields = $this->request->post('importfields', 'array', array());

        if (!$importObject || empty($importFields) || $this->_project->objectExists($importObject)){
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $importObjectConfig = Orm\Record\Config::factory($importObject);

        foreach ($importFields as $name)
            if ($importObjectConfig->fieldExists($name))
                $this->importOrmField($name, $importObjectConfig);

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Import DB fields into the form object
     */
    public function importDbFieldsAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        $connection = $this->request->post('connection', 'string', false);
        $table = $this->request->post('table', 'string', false);
        $conType = $this->request->post('type', 'integer', false);

        $importFields = $this->request->post('importfields', 'array', array());

        if ($connection === false || !$table || empty($importFields) || $conType === false)
        {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $conManager = new \Dvelum\Db\Manager($this->appConfig);

        try {
            $db = $conManager->getDbConnection($connection, $conType);
        } catch (\Exception $e) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $columns = $db->getMeta()->getColumnsAsArray($table);

        if (empty($columns)){
            $this->response->error($this->lang->get('CANT_CONNECT'));
            return;
        }


        foreach ($importFields as $name)
            if (isset($columns[$name]) && !empty($columns[$name]))
                $this->importDbField($name, $columns[$name]);

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Convert field from ORM format and add to the project
     * @param string $name
     * @param ConfigInterface $importObjectConfig
     */
    protected function importOrmField($name, ConfigInterface $importObjectConfig)
    {
        $newField = \Backend_Designer_Import::convertOrmFieldToExtField($name, $importObjectConfig->getFieldConfig($name));
        if ($newField !== false) {
            $newField->setName($this->object->getName() . '_' . $name);
            $this->project->addObject($this->object->getName(), $newField);
        }

    }

    /**
     * Conver DB column into Ext field
     * @param string $name
     * @param array $config
     */
    protected function importDbField($name, $config)
    {
        $newField = \Backend_Designer_Import::convertDbFieldToExtField($config);
        if ($newField !== false) {
            $newField->setName($this->object->getName() . '_' . $name);
            $this->project->addObject($this->object->getName(), $newField);
        }
    }
}