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
use Dvelum\Config;
use Dvelum\File;
use Dvelum\Filter;
use Dvelum\Utils;

/**
 * Class Column
 * @package Dvelum\App\Backend\Designer\Module\Grid
 */
class Column extends Module
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
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Grid') {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }
        $this->project = $project;
        $this->object = $project->getObject($name);
        return true;
    }

    /**
     * Get columns list as tree structure
     */
    public function columnListAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $this->response->json($this->object->getColumnsList());
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

        if (!$id || !$this->object->columnExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $column = $this->object->getColumn($id);
        $config = $column->getConfig();
        $properties = $config->__toArray();

        $properties['renderer'] = '';

        if ($config->xtype !== 'actioncolumn') {
            unset($properties['items']);

        } else {
            unset($properties['summaryRenderer']);
            unset($properties['summaryType']);
        }
        $this->response->success($properties);
    }

    /**
     * Set object property
     */
    public function setpropertyAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $id = $this->request->post('id', 'string', false);
        $property = $this->request->post('name', 'string', false);
        $value = $this->request->post('value', 'string', false);

        if (!$id || !$this->object->columnExists($id)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $object = $this->object->getColumn($id);
        if (!$object->isValidProperty($property)) {
            $this->response->error('WRONG_REQUEST');
            return;
        }


        if ($property === 'text') {
            $value = $this->request->post('value', 'raw', false);
        }

        $object->set($property, $value);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Get list of available renderers
     */
    public function renderersAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $data = [];
        $autoloaderCfg = Config::storage()->get('autoloader.php')->__toArray();
        $autoloaderPaths = $autoloaderCfg['paths'];
        $files = [];
        $classes = [];

        $data[] = ['id' => '', 'title' => $this->lang->get('NO')];

        foreach ($autoloaderPaths as $path) {
            $scanPath = $path . '/' . $this->designerConfig->get('components') . '/Renderer';
            if (is_dir($scanPath)) {
                $files = array_merge($files, File::scanFiles($scanPath, ['.php'], true, File::Files_Only));
                if (!empty($files)) {
                    foreach ($files as $item) {
                        $class = Utils::classFromPath(str_replace($autoloaderPaths, '', $item));
                        if (!in_array($class, $classes)) {
                            $data[] = array(
                                'id' => $class,
                                'title' => str_replace($scanPath . '/', '', substr($item, 0, -4))
                            );
                            array_push($classes, $class);
                        }
                    }
                }
            }
        }
        $this->response->json($data);
    }

    /**
     * Get list of accepted dictionaries for cell renderer
     */
    public function dictionariesAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }

        $manager = \Dictionary_Manager::factory();
        $data = [];
        $list = $manager->getList();

        foreach ($list as $path) {
            $data[] = ['id' => $path, 'title' => $path];
        }
        $this->response->success($data);
    }

    /**
     * Change column width
     */
    public function changesizeAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->request->post('object', 'string', false);
        $column = $this->request->post('column', 'string', false);
        $width = $this->request->post('width', 'integer', false);

        $project = $this->getProject();

        if ($object === false || !$project->objectExists($object) || $column === false || $width === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $object = $project->getObject($object);

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $column = $object->getColumn($column);
        $column->width = $width;

        $this->storeProject();

        $this->response->success();
    }

    /**
     * Move column
     */
    public function moveAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->request->post('object', 'string', false);
        $order = $this->request->post('order', 'raw', '');

        $project = $this->getProject();

        if ($object === false || !$project->objectExists($object) || empty($order)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $order = json_decode($order);

        if (!is_array($order)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $object = $project->getObject($object);

        if ($object->getClass() !== 'Grid') {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $object->updateColumnsSortingOrder($order);

        $this->storeProject();

        $this->response->success();
    }

    /**
     * Get list of items for actioncolumn
     */
    public function itemsListAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $designerManager = new \Designer_Manager($this->appConfig);

        $object = $this->object;
        $column = $this->request->post('column', 'string', false);

        if ($column === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
            return;
        }

        $columnObject = $object->getColumn($column);

        if ($columnObject->getClass() !== 'Grid_Column_Action') {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 3');
            return;
        }

        $result = [];
        $actions = $columnObject->getActions();
        if (!empty($actions)) {
            foreach ($actions as $name => $object) {
                $result[] = [
                    'id' => $name,
                    'icon' => \Designer_Factory::replaceCodeTemplates($designerManager->getReplaceConfig(),
                        $object->icon),
                    'tooltip' => $object->tooltip
                ];
            }
        }
        $this->response->success($result);
    }

    public function addActionAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->object;
        $actionName = $this->request->post('name', 'alphanum', false);
        $column = $this->request->post('column', 'string', false);

        if ($actionName === false || $column === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
            return;
        }

        $columnObject = $object->getColumn($column);

        if ($columnObject->getClass() !== 'Grid_Column_Action') {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 3');
            return;
        }

        $actionName = $this->object->getName() . '_action_' . $actionName;

        if ($columnObject->actionExists($actionName)) {
            $this->response->error($this->lang->get('SB_UNIQUE'));
            return;
        }

        $newButton = \Ext_Factory::object('Grid_Column_Action_Button', ['text' => $actionName]);
        $newButton->setName($actionName);

        $columnObject->addAction($actionName, $newButton);
        $this->storeProject();

        $this->response->success();
    }

    public function removeActionAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->object;
        $actionName = $this->request->post('name', 'alphanum', false);
        $column = $this->request->post('column', 'string', false);

        if ($actionName === false || $column === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
            return;
        }

        $columnObject = $object->getColumn($column);

        if ($columnObject->getClass() !== 'Grid_Column_Action') {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 3');
            return;
        }

        $columnObject->removeAction($actionName);

        $this->project->getEventManager()->removeObjectEvents($actionName);

        $this->storeProject();

        $this->response->success();
    }

    public function sortActionsAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->object;
        $order = $this->request->post('order', 'array', []);
        $column = $this->request->post('column', 'string', false);

        if ($column === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
            return;
        }

        $columnObject = $object->getColumn($column);

        if ($columnObject->getClass() !== 'Grid_Column_Action') {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 3');
            return;
        }

        if (!empty($order)) {
            $index = 0;
            foreach ($order as $name) {
                if ($columnObject->actionExists($name)) {
                    $columnObject->setActionOrder($name, $index);
                    $index++;
                }
            }
            if ($index > 0) {
                $columnObject->sortActions();
            }
        }

        $this->storeProject();
        $this->response->success();
    }

    public function rendererLoadAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->object;
        $column = $this->request->post('column', 'string', false);
        $data = [];

        if ($column === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
            return;
        }

        $columnObject = $object->getColumn($column);
        $renderer = $columnObject->renderer;

        if (empty($renderer) || is_string($renderer)) {
            $data = [
                'type' => 'adapter',
                'adapter' => $renderer
            ];
        } elseif ($renderer instanceof \Ext_Helper_Grid_Column_Renderer) {
            $data = [
                'type' => $renderer->getType(),
            ];
            switch ($renderer->getType()) {
                case \Ext_Helper_Grid_Column_Renderer::TYPE_DICTIONARY:
                    $data['dictionary'] = $renderer->getValue();
                    break;
                case \Ext_Helper_Grid_Column_Renderer::TYPE_ADAPTER:
                    $data['adapter'] = $renderer->getValue();
                    break;
                case \Ext_Helper_Grid_Column_Renderer::TYPE_JSCALL:
                    $data['call'] = $renderer->getValue();
                    break;
                case \Ext_Helper_Grid_Column_Renderer::TYPE_JSCODE:
                    $data['code'] = $renderer->getValue();
                    break;
            }
        }

        $this->response->success($data);
    }

    public function rendererSaveAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject()) {
            return;
        }
        $object = $this->object;
        $column = $this->request->post('column', 'string', false);

        if ($column === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 1');
            return;
        }

        if ($object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' code 2');
            return;
        }

        $rendererHelper = new \Ext_Helper_Grid_Column_Renderer();

        $type = $this->request->post('type', 'string', false);

        if (!in_array($type, $rendererHelper->getTypes(), true)) {
            $this->response->error($this->lang->get('FILL_FORM'), ['type' => $this->lang->get('INVALID_VALUE')]);
            return;
        }

        $rendererHelper->setType($type);

        switch ($type) {
            case \Ext_Helper_Grid_Column_Renderer::TYPE_DICTIONARY:
                $rendererHelper->setValue($this->request->post('dictionary', Filter::FILTER_RAW, ''));
                break;
            case \Ext_Helper_Grid_Column_Renderer::TYPE_ADAPTER:
                $rendererHelper->setValue($this->request->post('adapter', Filter::FILTER_RAW, ''));
                break;
            case \Ext_Helper_Grid_Column_Renderer::TYPE_JSCALL:
                $rendererHelper->setValue($this->request->post('call', Filter::FILTER_RAW, ''));
                break;
            case \Ext_Helper_Grid_Column_Renderer::TYPE_JSCODE:
                $rendererHelper->setValue($this->request->post('code', Filter::FILTER_RAW, ''));
                break;

        }

        $columnObject = $object->getColumn($column);
        $columnObject->renderer = $rendererHelper;

        $this->storeProject();
        $this->response->success();
    }
}