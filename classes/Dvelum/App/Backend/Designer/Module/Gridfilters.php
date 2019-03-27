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

class Gridfilters extends Module
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
     * Get object properties
     */
    public function listAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        
        $filters = $this->object->getFiltersFeature();
        $config = $filters->getConfig();
        $properties = $config->__toArray();

        if (isset($properties['filters'])) {
            unset($properties['filters']);
        }

        $this->response->success($properties);
    }

    /**
     * Set object property
     */
    public function setPropertyAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        
        $property = $this->request->post('name', 'string', false);
        $value = $this->request->post('value', 'raw', false);
        $object = $this->object->getFiltersFeature();
        $object->set($property,$value);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Get filters list
     */
    public function filterListAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        $filters = $this->object->getFiltersFeature()->getFilters();

        $data = [];

        if (!empty($filters)) {
            foreach ($filters as $k => $v) {
                $data[] = [
                    'id' => $k,
                    'dataIndex' => $v->dataIndex,
                    'active' => $v->active,
                    'type' => $v->getType()
                ];
            }
        }
        $this->response->success($data);
    }

    /**
     * Add filter
     */
    public function addFilterAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        $filterId = $this->request->post('id', 'pagecode', '');

        if (!strlen($filterId)) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        if ($this->object->getFiltersFeature()->filterExists($filterId)) {
            $this->response->error($this->lang->get('SB_UNIQUE'));
            return;
        }

        $filter = \Ext_Factory::object('Grid_Filter_String');
        $filter->setName($this->object->getName() . '_filter_' . $filterId);
        $filter->active = true;

        if (!$this->object->getFiltersFeature()->addFilter($filterId, $filter)) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove grid column
     */
    public function removeFilterAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        $fId = $this->request->post('id', 'pagecode', '');
        if (!strlen($fId)) {
            $this->response->error($this->lang->get('INVALID_VALUE').' code 1');
            return;
        }

        if (!$this->object->getFiltersFeature()->filterExists($fId)) {
            $this->response->error($this->lang->get('INVALID_VALUE').' code 2');
            return;
        }

        $this->object->getFiltersFeature()->removeFilter($fId);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Change grid filter type
     */
    public function changeFilterTypeAction()
    {
        if(!$this->checkLoaded() || !$this->checkObject()){
            return;
        }
        $type = $this->request->post('type', 'string', '');
        $filterId = $this->request->post('filterid', 'pagecode', false);

        if (!$filterId) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        if (strlen($type)) {
            $name = 'Grid_Filter_' . ucfirst($type);
        } else {
            $name = 'Grid_Filter_String';
        }

        $oldFilter = $this->object->getFiltersFeature()->getFilter($filterId);
        $newFilter = \Ext_Factory::object($name);

        \Ext_Factory::copyProperties($oldFilter, $newFilter);
        $newFilter->setName($oldFilter->getName());


        switch ($type) {
            case 'date':
                if (empty($newFilter->dateFormat)) {
                    $newFilter->dateFormat = "Y-m-d";
                }

                if (empty($newFilter->afterText)) {
                    $newFilter->afterText = '[js:] appLang.FILTER_AFTER_TEXT';
                }

                if (empty($newFilter->beforeText)) {
                    $newFilter->beforeText = '[js:] appLang.FILTER_BEFORE_TEXT';
                }

                if (empty($newFilter->onText)) {
                    $newFilter->onText = '[js:] appLang.FILTER_ON_TEXT';
                }

                break;

            case 'datetime' :
                if (empty($newFilter->dateFormat)) {
                    $newFilter->dateFormat = "Y-m-d";
                }

                $newFilter->date = '{format: "Y-m-d"}';
                $newFilter->time = '{format: "H:i:s",increment:1}';

                if (empty($newFilter->afterText)) {
                    $newFilter->afterText = '[js:] appLang.FILTER_AFTER_TEXT';
                }

                if (empty($newFilter->beforeText)) {
                    $newFilter->beforeText = '[js:] appLang.FILTER_BEFORE_TEXT';
                }

                if (empty($newFilter->onText)) {
                    $newFilter->onText = '[js:] appLang.FILTER_ON_TEXT';
                }

                break;

            case 'list':
                $newFilter->phpMode = true;
                break;

            case 'boolean':
                $newFilter->noText = '[js:] appLang.NO';
                $newFilter->yesText = '[js:] appLang.YES';
                break;
        }

        if (!$this->object->getFiltersFeature()->setFilter($filterId, $newFilter)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $this->storeProject();
        $this->response->success();
    }
}