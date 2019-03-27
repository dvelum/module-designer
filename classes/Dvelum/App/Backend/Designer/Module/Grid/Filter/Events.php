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

namespace Dvelum\App\Backend\Designer\Module\Grid\Filter;

use Dvelum\App\Backend\Designer\Module;
use Dvelum\Filter;

class Events extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /*
     * @var \Ext_Grid
     */
    protected $object;
    /**
     * @var \Ext_Grid_Filter
     */
    protected $filter;

    /**
     * @return bool
     */
    public function checkFilter() : bool 
    {
        $object = $this->object;
        $filter = $this->request->post('id', 'string', false);

        if ($filter === false || $object->getClass() !== 'Grid' || !$object->getFiltersFeature()->filterExists($filter)) {
           $this->response->error('Cant find filter');
           return false;
        }
        $filterObject = $object->getFiltersFeature()->getFilter($filter);

        if (!$filterObject instanceof \Ext_Grid_Filter) {
            $this->response->error('Invalid filter type');
            return false;
        }
        $this->filter = $filterObject;
        return true;
    }

    /**
     * @return bool
     */
    protected function checkObject() : bool 
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Grid') {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
        }
        $this->project = $project;
        $this->object = $project->getObject($name);
    }

    /**
     * @return bool
     */
    protected function checkInput():bool{
        if(!$this->checkLoaded() || !$this->checkObject() || !$this->checkFilter()){
            return false;
        }
        return true;
    }

    protected function convertParams($config)
    {
        if (empty($config)) {
            return '';
        }

        foreach ($config as $pName => $pType) {
            $paramsArray[] = '<span style="color:green;">' . $pType . '</span> ' . $pName;
        }

        return implode(' , ', $paramsArray);
    }

    /**
     * Get events for object
     */
    public function objectEventsAction()
    {
        if(!$this->checkInput()){
            return;
        }
        
        $objectName = $this->filter->getName();

        $objectEvents = $this->project->getEventManager()->getObjectEvents($objectName);

        $events = $this->filter->getConfig()->getEvents();

        $result = [];
        $id = 1;
        foreach ($events as $name => $config) {
            if (isset($objectEvents[$name]) && !empty($objectEvents[$name])) {
                $hasCode = true;
            } else {
                $hasCode = false;
            }

            $result[] = [
                'id' => $id,
                'object' => $objectName,
                'event' => $name,
                'params' => $this->convertParams($config),
                'has_code' => $hasCode
            ];
            $id++;
        }

        $this->response->success($result);
    }

    protected function getEvent()
    {
        $event = $this->request->post('event', 'string', false);
        if (!strlen($event) || $event === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return null;
        }
        return $event;
    }

    public function eventCodeAction()
    {
        if(!$this->checkInput()){
            return;
        }
        
        $project = $this->getProject();
        $event = $this->getEvent();
        if(empty($event)){
            return;
        }

        $eventManager = $project->getEventManager();

        if ($eventManager->eventExists($this->filter->getName(), $event)) {
            $code = $eventManager->getEventCode($this->filter->getName(), $event);
        } else {
            $code = '';
        }

        $this->response->success(['code' => $code]);
    }

    public function saveEventAction()
    {
        if(!$this->checkInput()){
            return;
        }
        
        $project = $this->getProject();

        $event = $this->getEvent();
        if(empty($event)){
            return;
        }

        $code = $this->request->post('code', 'raw', '');
        $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);
        if (empty($buffer)) {
            $buffer = false;
        }
        $events = $this->filter->getConfig()->getEvents();


        $project->getEventManager()->setEvent($this->filter->getName(), $event, $code, $events->$event, false, $buffer);
        $this->storeProject();
        $this->response->success();
    }


    public function removeEventAction()
    {
        if(!$this->checkInput()){
            return;
        }
        
        $project = $this->getProject();
        $event = $this->getEvent();
        if(empty($event)){
            return;
        }
        $project->getEventManager()->removeObjectEvent($this->filter->getName(), $event);
        $this->storeProject();
        $this->response->success();
    }
}