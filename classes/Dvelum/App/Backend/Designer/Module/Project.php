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
use Dvelum\App\Backend\Localization\Manager;
use Dvelum\Config;
use Dvelum\File;
use Dvelum\Designer;

/**
 * Project controller
 * @author Kirill A Rgorov 2011
 * @package Disigner
 * @subpackage Sub
 */
class Project extends Module
{
    /**
     * Check if project is loaded
     */
    public function checkLoadedAction()
    {
        $isLoaded = $this->checkLoaded();
        if ($isLoaded) {
            $this->response->success(['file' => $this->session->get('file')]);
        } else {
            $this->response->error('not loaded');
        }
    }

    /**
     * Load project
     */
    public function loadAction()
    {
        $relFile = $this->request->post('file', 'string', false);

        $paths = Config::storage()->getPaths();
        $cfgPath = $this->designerConfig->get('configs');
        $writePath = Config::storage()->getWrite();
        $writeFile = str_replace('//', '/', $writePath . $cfgPath . $relFile);

        // In accordance with configs merge priority
        krsort($paths);

        $file = false;

        foreach ($paths as $path) {
            $file = str_replace('//', '/', $path . $cfgPath . $relFile);
            if (file_exists($file)) {
                break;
            }
        }

        if (!$file) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        try {
            if ($this->designerConfig->get('vcs_support')) {
                $project = Designer\Factory::importProject($this->designerConfig, $file);
                // fallback to load project from .dat if import from exported .dat.files directory failed
                if (!$project instanceof Designer\Project) {
                    $project = Designer\Factory::loadProject($this->designerConfig, $file);
                }
            } else {
                $project = Designer\Factory::loadProject($this->designerConfig, $file);
            }

            if ($project instanceof Designer\Project) {
                // convert project to 1.x version
//                if ($project->convertTo1x($this->designerConfig->get('js_path'))) {
//                    $this->storeProject();
//                }
            } else {
                throw new \Exception('Cannot load project ' . $file);
            }
        } catch (\Exception $e) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' ' . $e->getMessage());
            return;
        }

        $this->session->set('loaded', true);
        $this->session->set('project', serialize($project));
        $this->session->set('file', $writeFile);

        $this->response->success();
    }

    /**
     * Clear report session
     */
    public function closeAction()
    {
        $this->session->remove('loaded');
        $this->session->remove('project');
        $this->session->remove('file');
        $this->response->success();
    }

    /**
     * Save report
     */
    public function saveAction()
    {
        if (!$this->checkLoaded()) {
            return;
        };

        $dir = dirname($this->session->get('file'));
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true)) {
                $this->response->error($this->lang->get('CANT_WRITE_FS') . ' ' . $dir);
                return;
            }
        }

        $exportProject = $this->designerConfig->get('vcs_support');

        if ($this->storage->save($this->session->get('file'), $this->getProject(), $exportProject)) {
            $this->response->success();
        } else {
            $this->response->error($this->lang->get('CANT_EXEC') . ' ' . implode(', ', $this->storage->getErrors()));
            return;
        }
    }

    /**
     * Get project config
     */
    public function loadConfigAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }

        $config = $this->getProject()->getConfig();

        if (isset($config['files']) && !empty($config['files'])) {
            foreach ($config['files'] as &$item) {
                $item = ['file' => $item];
            }
            unset($item);
        } else {
            $config['files'] = [];
        }

        if (isset($config['langs']) && !empty($config['langs'])) {
            foreach ($config['langs'] as &$item) {
                $item = ['name' => $item];
            }
            unset($item);
        } else {
            $config['langs'] = [];
        }

        $locManager = new Manager($this->appConfig);
        $langs = $locManager->getLangs(false);

        $paths = [];
        foreach ($langs as $v) {
            $pos = strpos($v, '/');
            if (strpos($v, '/') === false) {
                continue;
            }

            $path = substr($v, $pos + 1);
            if (!isset($paths[$path])) {
                $paths[$path] = ['name' => $path];
            }
        }

        $config['langsList'] = array_values($paths);
        $this->response->success($config);
    }

    /**
     * Set project config option
     */
    public function setConfigAction()
    {
        $project = $this->getProject();
        $project->files = [];
        $project->langs = [];

        $names = array_keys($project->getConfig());

        foreach ($names as $name) {

            if ($name == 'files') {
                $value = $this->request->post($name, 'array', []);
                $project->__set($name, $value);
            } elseif ($name == 'langs') {
                $value = $this->request->post($name, 'array', []);
                $project->__set($name, $value);
            } else {
                $value = $this->request->post($name, 'string', false);
                if ($value !== false) {
                    $project->__set($name, $value);
                }
            }
        }
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Add object to the project tree
     */
    public function addobjectAction()
    {
        if (!$this->_checkLoaded()) {
            return;
        }

        $name = $this->request->post('name', 'alphanum', false);
        $class = $this->request->post('class', 'alphanum', false);
        $parent = $this->request->post('parent', 'alphanum', \Designer_Project::LAYOUT_ROOT);
        $class = ucfirst($class);
        $project = $this->getProject();

        if (!strlen($parent)) {
            $parent = \Designer_Project::LAYOUT_ROOT;
        }

        if ($name == false) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }
        /*
         * Check if name starts with digits
         */
        if (intval($name) > 0) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        /*
         * Skip parent for window , store and model
         */
        $rootClasses = ['Window', 'Model', 'Component_JSObject'];
        $isWindowComponent = strpos($class, 'Component_Window_') !== false;

        /*
         * Check if parent object exists and can has childs
         */
        if (!$project->objectExists($parent) || !Designer_Project::isContainer($project->getObject($parent)->getClass())) {
            $parent = \Designer_Project::LAYOUT_ROOT;
        }

        if (!$name || !$class) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if ($project->objectExists($name)) {
            $this->response->error($this->lang->get('SB_UNIQUE'));
            return;
        }

        if (in_array($class, $rootClasses, true) || $isWindowComponent) {
            $parent = \Designer_Project::COMPONENT_ROOT;
        }

        $object = \Ext_Factory::object($class);
        $object->setName($name);

        if ($parent === \Designer_Project::COMPONENT_ROOT) {
            $object->extendedComponent(true);
        } else {
            $object->extendedComponent(false);
        }

        $this->initDefaultProperties($object);

        if ($isWindowComponent) {
            $tab = \Ext_Factory::object('Panel');
            $tab->setName($object->getName() . '_generalTab');
            $tab->frame = false;
            $tab->border = false;
            $tab->layout = 'anchor';
            $tab->bodyPadding = 3;
            $tab->bodyCls = 'formBody';
            $tab->anchor = '100%';
            $tab->fieldDefaults = "{
		            labelAlign: 'right',
		            labelWidth: 160,
		            anchor: '100%'
		     }";
            if (!$project->addObject($parent, $object) || !$project->addObject($object->getName(), $tab)) {
                $this->response->error($this->lang->get('INVALID_VALUE'));
                return;
            }
        } else {
            if (!$project->addObject($parent, $object)) {
                $this->response->error($this->lang->get('INVALID_VALUE'));
                return;
            }
        }

        if (in_array($class, \Designer_Project::$hasDocked, true)) {
            $dockObject = \Ext_Factory::object('Docked');
            $dockObject->setName($name . '__docked');
            $project->addObject($name, $dockObject);
        }

        if (in_array($class, \Designer_Project::$hasMenu, true)) {
            $menuObject = \Ext_Factory::object('Menu');
            $menuObject->setName($name . '__menu');
            $project->addObject($name, $menuObject);
        }

        if (strpos($object->getClass(), 'Form_Field') !== false && $object->getConfig()->isValidProperty('name')) {
            $object->name = $name;
        }


        /**
         * Store auto configuration
         */
        if (strpos($object->getClass(), 'Data_Store') !== false) {
            $object->autoLoad = false;

            $reader = \Ext_Factory::object('Data_Reader_Json');
            $reader->rootProperty = 'data';
            $reader->totalProperty = 'count';

            $proxy = \Ext_Factory::object('Data_Proxy_Ajax');
            $proxy->type = 'ajax';
            $proxy->reader = $reader;
            $proxy->writer = '';
            $proxy->startParam = 'pager[start]';
            $proxy->limitParam = 'pager[limit]';
            $proxy->sortParam = 'pager[sort]';
            $proxy->directionParam = 'pager[dir]';
            $proxy->simpleSortMode = true;
            $object->proxy = $proxy;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Add object instance to the project
     */
    public function addInstanceAction()
    {
        $parent = $this->request->post('parent', 'alphanum', '');
        $name = $this->request->post('name', 'alphanum', false);
        $instance = $this->request->post('instance', 'alphanum', false);

        $errors = [];

        if (empty($name)) {
            $errors['name'] = $this->lang->get('CANT_BE_EMPTY');
        }

        $project = $this->getProject();

        if (!$project->objectExists($instance)) {
            $errors['instance'] = $this->lang->get('INVALID_VALUE');
        }

        $instanceObject = $project->getObject($instance);

        if ($instanceObject->isInstance() /*|| !Designer_Project::isVisibleComponent($instanceObject->getClass())*/) {
            $errors['instance'] = $this->lang->get('INVALID_VALUE');
        }

        /*
         * Skip parent for window , store and model
         */
        $rootClasses = ['Window', 'Store', 'Data_Store', 'Data_Store_Tree', 'Model'];
        $isWindowComponent = (strpos($instanceObject->getClass(), 'Component_Window_') !== false);

        if ($parent === \Designer_Project::COMPONENT_ROOT) {
            $parent = \Designer_Project::LAYOUT_ROOT;
        }

        if (in_array($instanceObject->getClass(), $rootClasses, true) || $isWindowComponent) {
            $parent = \Designer_Project::LAYOUT_ROOT;
        }
        /*
         * Check if parent object exists and can has childs
        */
        if (!$project->objectExists($parent) || !\Designer_Project::isContainer($project->getObject($parent)->getClass())) {
            $parent = \Designer_Project::LAYOUT_ROOT;
        }

        if ($project->objectExists($name)) {
            $errors['name'] = $this->lang->get('SB_UNIQUE');
        }

        if (!empty($errors)) {
            $this->response->error($this->lang->get('FILL_FORM'), $errors);
            return;
        }

        $object = \Ext_Factory::object('Object_Instance');
        $object->setObject($instanceObject);
        $object->setName($name);

        if (!$project->addObject($parent, $object)) {
            $this->response->error($this->lang->get('CANT_EXEC'));
            return;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Generate and add components
     */
    public function addTemplateAction()
    {
        if (!$this->checkLoaded()) {
            return;
        }
        $name = $this->request->post('name', 'alphanum', false);
        $adapter = $this->request->post('adapter', 'string', false);
        $parent = $this->request->post('parent', 'alphanum', 0);

        if (!class_exists($adapter)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' invalid adapter ' . $adapter);
            return;
        }

        $adapterObject = new $adapter();

        if (!$adapterObject instanceof \Backend_Designer_Generator_Component) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' invalid adapter interface');
            return;
        }

        $project = $this->getProject();

        if (!strlen($parent)) {
            $parent = 0;
        }

        /*
         * Check if name starts with digits
         */
        if ($name == false || intval($name) > 0) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        /*
         * Check if parent object exists and can has childs
         */
        if (!$project->objectExists($parent) || !\Designer_Project::isContainer($project->getObject($parent)->getClass())) {
            $parent = 0;
        }

        if (!$adapterObject->addComponent($project, $name, $parent)) {
            $this->response->error($this->lang->get('CANT_EXEC'));
            return;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Set default properties for new object
     * @param \Ext_Object $object
     * @return void
     */
    protected function initDefaultProperties(\Ext_Object $object)
    {
        $oClass = $object->getClass();
        switch ($oClass) {
            case 'Window':
                $object->width = 300;
                $object->height = 300;
                break;
            case 'Button':
            case 'Button_Split':
                $object->text = $object->getName();
                break;
            case 'Grid':
                $object->columnLines = true;
                break;

        }

        if (strpos($oClass, 'Component_Window_') !== false) {
            $object->width = 700;
            $object->height = 700;
        }
    }

    /**
     * Files list
     */
    public function fsListAction()
    {
        $path = $this->request->post('node', 'string', '');
        $path = str_replace('.', '', $path);

        $dirPath = $this->appConfig->get('wwwPath');

        if (!is_dir($dirPath)) {
            $this->response->success([]);
            return;
        }

        $files = File::scanFiles($dirPath . $path, ['.js', '.css'], false, File::Files_Dirs);

        if (empty($files)) {
            $this->response->json([]);
            return;
        }

        $list = [];

        foreach ($files as $filePath) {
            $text = basename($filePath);
            if ($text === '.svn') {
                continue;
            }

            $obj = new stdClass();
            $obj->id = str_replace($dirPath, '', $filePath);
            $obj->text = $text;

            if (is_dir($filePath)) {
                $obj->expanded = false;
                $obj->leaf = false;
            } else {
                $obj->leaf = true;
            }
            $list[] = $obj;
        }
        $this->response->json($list);
    }

    /**
     *
     */
    public function projectListAction()
    {
        $node = $this->request->post('node', 'string', '');
        $manager = new \Designer_Manager($this->appConfig);
        $this->response->json($manager->getProjectsList($node));
    }

    /**
     * Get list of project components that can be instantiated
     */
    public function canInstantiateAction()
    {
        $list = [];
        $project = $this->getProject();
        $items = $project->getChilds(\Designer_Project::COMPONENT_ROOT);

        foreach ($items as $name => $object) {
            $list[] = ['name' => $name];
        }
        $this->response->success($list);
    }
}