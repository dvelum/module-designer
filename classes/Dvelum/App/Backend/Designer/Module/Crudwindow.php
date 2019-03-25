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
use Dvelum\Orm;

class Crudwindow extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Property_Component_Window_System_Crud
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
     * Import fields into the form object
     */
    public function importFieldsAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $importObject = $this->request->post('importobject', 'string', false);
        $importFields = $this->request->post('importfields', 'array', []);

        if (!$importObject || empty($importFields) || !Orm\Record\Config::configExists($importObject)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $importObjectConfig = Orm\Record\Config::factory($importObject);

        foreach ($importFields as $name) {
            if ($importObjectConfig->fieldExists($name)) {
                $this->importOrmField($name, $importObjectConfig);
            }
        }

        $this->object->objectName = $importObject;
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Conver field from ORM format and add to the project
     * @param string $name
     * @param Orm\Record\Config $importObject
     * @throws \Exception
     */
    protected function importOrmField(string $name, Orm\Record\Config $importObjectConfig)
    {
        $tabName = $this->object->getName() . '_generalTab';

        if (!$this->project->objectExists($tabName)) {
            $tab = \Ext_Factory::object('Panel');
            $tab->setName($tabName);
            $tab->frame = false;
            $tab->border = false;
            $tab->layout = 'anchor';
            $tab->bodyPadding = 3;
            $tab->bodyCls = 'formBody';
            $tab->anchor = '100%';
            $tab->scrollable = true;
            $tab->title = Lang::lang()->get('GENERAL');
            $tab->fieldDefaults = "{
                labelAlign: 'right',
                labelWidth: 160,
                anchor: '100%'
            }";

            $this->project->addObject($this->object->getName(), $tab);
        }

        $tabsArray = [
            'Component_Field_System_Medialibhtml',
            'Component_Field_System_Related',
            'Component_Field_System_Objectslist'
        ];

        $newField = \Backend_Designer_Import::convertOrmFieldToExtField($name,
            $importObjectConfig->getFieldConfig($name));

        if ($newField !== false) {
            $fieldClass = $newField->getClass();
            if ($fieldClass == 'Component_Field_System_Objectslist' || $fieldClass == 'Component_Field_System_Objectlink') {
                $newField->controllerUrl = $this->object->controllerUrl;
            }

            $newField->setName($this->object->getName() . '_' . $name);

            if (in_array($fieldClass, $tabsArray, true)) {
                $this->project->addObject($this->object->getName(), $newField);
            } else {
                $this->project->addObject($tabName, $newField);
            }
        }
    }
}