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
use Dvelum\Designer\Manager;
use Dvelum\Designer\Project;
use Dvelum\Designer\Project\Container;
use Dvelum\Filter;
use Dvelum\File;
use Dvelum\Tree\Tree;

class Objects extends Module
{
    /**
     * Get panels tree
     */
    public function visualListAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }
        $project = $this->getProject();
        $this->response->json($this->fillContainers($project->getTree()));
    }

    /**
     * Get list of project objects by object type
     */
    public function listAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $acceptedTypes = ['visual', 'stores', 'models', 'menu', 'store_selection'];

        $type = $this->request->post('type', 'string', false);
        $project = $this->getProject();

        if (!in_array($type, $acceptedTypes, true)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $list = [];

        switch ($type) {
            case  'store_selection':
                $addStores = $this->request->post('stores', Filter::FILTER_BOOLEAN, false);
                $addInstances = $this->request->post('instances', Filter::FILTER_BOOLEAN, false);
                $stores = $project->getStores();

                $cfg = $project->getConfig();

                if (!empty($stores)) {
                    foreach ($stores as $object) {
                        $name = $object->getName();
                        $title = $name;
                        $class = $object->getClass();

                        if ($class === 'Data_Store_Tree') {
                            $title .= ' (Tree)';
                        }

                        if ($class === 'Data_Store_Buffered') {
                            $title .= ' (Buffered)';
                        }

                        // append instance token
                        if($object->isExtendedComponent()){
                            if($addInstances){
                                $list[] = [
                                    'id' => $name,
                                    'title' => $name,
                                    'objClass' => $cfg['namespace'].'.'.$name
                                ];
                            }else{
                                continue;
                            }
                        }

                        if ($addStores) {
                            $list[] = [
                                'id' => $name,
                                'title' => $title,
                                'objClass' => $class
                            ];
                        }
                    }
                }
                break;

            case 'stores' :
                $addInstances = $this->request->post('instances', Filter::FILTER_BOOLEAN, false);
                $stores = $project->getStores();

                $list = [];

                $cfg = $project->getConfig();
                if (!empty($stores)) {
                    foreach ($stores as $object) {
                        $name = $object->getName();
                        $title = $name;
                        $class = $object->getClass();

                        if ($class === 'Data_Store_Tree') {
                            $title .= ' (Tree)';
                        }

                        if ($class === 'Data_Store_Buffered') {
                            $title .= ' (Buffered)';
                        }

                        // append instance token
                        if ($addInstances && $object->isExtendedComponent()) {
                            $list[] = [
                                'id' => Project_Code::$NEW_INSTANCE_TOKEN . ' ' . $name,
                                'title' => Project_Code::$NEW_INSTANCE_TOKEN . ' ' . $name,
                                'objClass' => $cfg['namespace'] . '.' . $name
                            ];
                        } else {
                            $list[] = [
                                'id' => $name,
                                'title' => $title,
                                'objClass' => $class
                            ];
                        }
                    }
                }
                break;
            case 'models' :
                $models = array_keys($project->getModels());
                $list = [];
                if (!empty($models)) {
                    foreach ($models as $name) {
                        $list[] = [
                            'id' => $name,
                            'title' => $name,
                            'objClass' => 'Model'
                        ];
                    }
                }
                break;
            case 'menu'  :
                $menu = array_keys($project->getMenu());
                $list = [];
                if (!empty($menu)) {
                    foreach ($menu as $name) {
                        $list[] = ['id' => $name, 'title' => $name, 'objClass' => 'Menu'];
                    }
                }
                break;
        }
        $this->response->success($list);
    }

    /**
     * Fill childs data array for tree panel
     * @param Tree $tree
     * @param mixed $root
     * @return array
     */
    protected function fillContainers(Tree $tree, $root = 0)
    {
        //$exceptions = array('Store', 'Data_Store', 'Data_Store_Tree', 'Data_Store_Buffered', 'Model');
        $result = [];
        $childs = $tree->getChildren($root);

        if (empty($childs)) {
            return [];
        }

        foreach ($childs as $v) {
            $object = $v['data'];

            $item = new \stdClass();
            $item->id = $v['id'];

            /**
             *  Stub for project container
             */
            if ($object instanceof Container) {

                $item->text = $object->getName();
                $item->expanded = true;
                $item->objClass = 'Designer_Project_Container';
                $item->isInstance = false;
                $item->leaf = false;
                //$item->iconCls = self::getIconClass($objectClass);
                $item->allowDrag = false;
                $item->children = [];

                if ($tree->hasChildren($v['id'])) {
                    $item->children = $this->fillContainers($tree, $v['id']);
                }

                $result[] = $item;
                continue;
            }


            $objectClass = $object->getClass();
            $objectName = $object->getName();

            $inst = '';
            $ext = '';

            if ($object->isInstance()) {
                $inst = ' <span class="extInstanceLabel" data-qtip="Object instance">instance of </span>' . $object->getObject()->getName();
            }

            if ($root === Project::COMPONENT_ROOT) {
                $ext = ' <span class="extCmpLabel" data-qtip="Extended component">ext</span> ';
                $objectName = '<span class="extClassLabel">' . $objectName . '</span>';
            }

            $item->text = $ext . $objectName . ' (' . $objectClass . ')' . $inst;
            $item->expanded = true;
            $item->objClass = $objectClass;
            $item->isInstance = $object->isInstance();
            if($item->isInstance){
                /**
                 * @var \Ext_Object_Instance $object
                 */
                $item->instanceOf = $object->getObject()->getName();
            }
            $item->leaf = true;
            $item->iconCls = $this->getIconClass($objectClass);
            $item->allowDrag = Project::isDraggable($objectClass);

            if ($objectClass == 'Docked') {
                $item->iconCls = 'objectDocked';
                $item->text = 'Docked Items';
            } elseif ($objectClass == 'Menu') {
                $item->text = 'Menu Items';
                $item->iconCls = 'menuItemsIcon';
            }

            if (Project::isContainer($objectClass) && !$object->isInstance()) {
                $item->leaf = false;
                $item->children = [];
            }

            if ($tree->hasChildren($v['id'])) {
                $item->children = $this->fillContainers($tree, $v['id']);
            }

            $result[] = $item;
        }
        return $result;
    }

    /**
     * Get css icon clas for object
     * @param string $objClass
     */
    public function getIconClass($objClass)
    {
        $config = [
            'Docked' => 'objectDocked',
            'Text' => 'textFieldIcon',
            'Textarea' => 'textareaIcon',
            'Checkbox' => 'checkboxIcon',
            'Checkboxgroup' => 'checkboxGroupIcon',
            'Container' => 'containerIcon',
            'Time' => 'clockIcon',
            'Date' => 'dateIcon',
            'Display' => 'displayfieldIcon',
            'Fieldset' => 'fieldsetIcon',
            'Fieldcontainer' => 'fieldContainerIcon',
            'File' => 'fileIcon',
            'Htmleditor' => 'htmlEditorIcon',
            'Picker' => 'pickerIcon',
            'Radio' => 'radioIcon',
            'Radiogroup' => 'radioGroupIcon',
            'Number' => 'numberFieldIcon',

            'Panel' => 'panelIcon',
            'Tabpanel' => 'tabIcon',
            'Grid' => 'gridIcon',

            'Form' => 'formIcon',
            'Form_Field_Text' => 'textFieldIcon',
            'Form_Field_Number' => 'textFieldIcon',
            'Form_Field_Hidden' => 'hiddenFieldIcon',
            'Form_Field_Checkbox' => 'checkboxIcon',
            'Form_Field_Textarea' => 'textareaIcon',
            'Form_Field_Htmleditor' => 'htmlEditorIcon',
            'Form_Field_File' => 'fileIcon',
            'Form_Field_Radio' => 'radioIcon',
            'Form_Field_Time' => 'clockIcon',
            'Form_Field_Date' => 'dateIcon',
            'Form_Fieldset' => 'fieldsetIcon',
            'Form_Field_Display' => 'displayfieldIcon',
            'Form_Fieldcontainer' => 'fieldContainerIcon',
            'Form_Checkboxgroup' => 'checkboxGroupIcon',
            'Form_Radiogroup' => 'radioGroupIcon',
            'Form_Field_Combobox' => 'comboboxFieldIcon',
            'Form_Field_Tag' => 'tagIcon',

            'Button' => 'buttonIcon',
            'Button_Split' => 'buttonSplitIcon',
            'Buttongroup' => 'buttonGroupIcon',
            'Tree' => 'treeIcon',
            'Window' => 'windowIcon',
            'Store' => 'storeIcon',
            'Data_Store' => 'storeIcon',
            'Data_Store_Tree' => 'storeIcon',
            'Data_Store_Buffered' => 'storeIcon',
            'Model' => 'modelIcon',
            'Image' => 'imageIcon',

            'Component_Window_System_Crud' => 'objectWindowIcon',
            'Component_Window_System_Crud_Vc' => 'objectWindowIcon',
            'Component_Field_System_Searchfield' => 'olinkIcon',
            'Component_Field_System_Dictionary' => 'comboboxFieldIcon',
            'Component_Field_System_Medialibhtml' => 'textMediaFieldIcon',
            'Component_Field_System_Medialibitem' => 'resourceFieldIcon',
            'Component_Field_System_Related' => 'gridIcon',
            'Component_Field_System_Objectlink' => 'olinkIcon',
            'Component_Field_System_Objectslist' => 'gridIcon',

            'Toolbar' => 'toolbarIcon',
            'Toolbar_Separator' => 'toolbarSeparatorIcon',
            'Toolbar_Spacer' => 'toolbarSpacerIcon',
            'Toolbar_Fill' => 'toolbarFillIcon',
            'Toolbar_Textitem' => 'toolbarTextitemIcon',

            'Menu' => 'menuItemsIcon',
            'Menu_Separator' => 'menuSeparatorIcon',
            'Menu_Item' => 'toolbarTextitemIcon',
            'Menu_Datepicker' => 'dateIcon',
            'Menu_Colorpicker' => 'colorPickerIcon',
            'Menu_Checkitem' => 'checkboxIcon',

            'View' => 'viewViewIcon',
            'Toolbar_Paging' => 'pagingIcon'

        ];

        if (Project::isWindowComponent($objClass)) {
            return 'objectWindowIcon';
        }

        if (isset($config[$objClass])) {
            return $config[$objClass];
        } else {
            if (Project::isContainer($objClass)) {
                return 'objectIcon';
            } else {
                return 'objectLeafIcon';
            }
        }
    }

    /**
     * Sort Objects tree
     */
    public function sortAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }
        $id = $this->request->post('id', 'string', false);
        $newParent = $this->request->post('newparent', 'string', false);

        if (empty($newParent)) {
            $newParent = Project::LAYOUT_ROOT;
        }

        $order = $this->request->post('order', 'array', []);
        $project = $this->getProject();

        if (!$id || !$project->objectExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code1');
            return;
        }

        if (!$project->objectExists($newParent)) {
            $this->response->error('Bad new parent');
            return;
        }

        $itemData = $project->getTree()->getItem($id);

        if (in_array($itemData['data']->getClass(), Project::$storeClasses, true)) {
            if ($newParent != Project::LAYOUT_ROOT && $newParent != Project::COMPONENT_ROOT) {
                $this->response->error('Store can exist only at Layout root or Components root');
                return;
            }
        }

        if ($itemData['data']->isInstance() && $newParent == Project::COMPONENT_ROOT) {
            $this->response->error('Object instance cannot be converted to component');
            return;
        }

        if ($itemData['parent'] == Project::COMPONENT_ROOT && $newParent !== Project::COMPONENT_ROOT && $project->hasInstances($id)) {
            $this->response->error('Component cannot be converted. Object Instances detected');
            return;
        }

        $object = $project->getObject($id);

        if ($newParent == Project::COMPONENT_ROOT) {
            $object->extendedComponent(true);
        } else {
            $object->extendedComponent(false);
        }

        if (!$project->changeParent($id, $newParent)) {
            $this->response->error('Cannot move object');
            return;
        }

        $count = 0;
        foreach ($order as $name) {
            if (!$project->setItemOrder($name, $count)) {
                $this->response->error($this->lang->get('WRONG_REQUEST') . ' code2');
                return;
            }

            $count++;
        }
        $project->resortItems($newParent);

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove object from project
     */
    public function removeAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $id = $this->request->post('id', 'string', false);

        if (!$id || !strlen($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $project = $this->getProject();

        $object = $project->getObject($id);
        $oClass = $object->getClass();
        unset($object);

        if ($project->removeObject($id)) {
            /*
             * Remove object links
             */
            $propertiesToClean = [];
            switch ($oClass) {
                case 'Store':
                    $propertiesToClean[] = 'store';
                    break;
                case 'Model':
                    $propertiesToClean[] = 'model';
                    break;
            }
            if (!empty($propertiesToClean)) {
                $objects = $project->getObjects();
                foreach ($objects as $object) {
                    if (!$object instanceof \Ext_Object) {
                        continue;
                    }
                    // remove object instances
                    if ($object->isInstance() && $object->getObject()->getName() === $id) {
                        $project->removeObject($object->getName());
                    }

                    foreach ($propertiesToClean as $property) {
                        if ($object->isValidProperty($property) && $object->__get($property) === $id) {
                            $object->$property = '';
                        }
                    }
                }
            }
            $project->getEventManager()->removeObjectEvents($id);
            $project->getMethodManager()->removeObjectMethods($id);
            $this->storeProject();
            $this->response->success();
        } else {
            $this->response->error($this->lang->get('CANT_EXEC'));
        }
    }

    /**
     * Get related projects
     * @param Project $project
     * @param array & $list - result
     */
    protected function getRelatedProjects($project, & $list)
    {
        $manager = new Manager($this->appConfig);
        $projectConfig = $project->getConfig();


        if (isset($projectConfig['files']) && !empty($projectConfig['files'])) {
            foreach ($projectConfig['files'] as $file) {
                if (File::getExt($file) === '.js' || File::getExt($file) === '.css') {
                    continue;
                }

                $projectFile = $manager->findWorkingCopy($file);
                $subProject = \Designer_Factory::loadProject($this->designerConfig, $projectFile);
                $list[] = [
                    'project' => $subProject,
                    'file' => $file
                ];
                $this->getRelatedProjects($subProject, $list);
            }
        }
    }

    /**
     * Get related project items Tree list
     */
    public function relatedProjectListAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }
        $project = $this->getProject();

        $relatedProjects = [];
        $this->getRelatedProjects($project, $relatedProjects);

        if (empty($relatedProjects)) {
            $this->response->success([]);
        }

        $result = [];

        foreach ($relatedProjects as $item) {
            $projectConfig = $item['project']->getConfig();

            $o = new \stdClass();
            $o->id = $item['file'];
            $o->text = $item['file'] . ' classes:  ' . $projectConfig['namespace'] . ' run: ' . $projectConfig['runnamespace'];
            $o->expanded = false;
            $o->objClass = '';
            $o->leaf = false;
            $o->iconCls = '';
            $o->allowDrag = false;
            $o->children = $this->fillContainers($item['project']->getTree(), 0);
            $result[] = $o;
        }
        $this->response->json($result);
    }

}