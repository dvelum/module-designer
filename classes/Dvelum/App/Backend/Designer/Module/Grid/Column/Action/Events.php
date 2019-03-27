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

namespace Dvelum\App\Backend\Designer\Module\Grid\Column\Action;

use Dvelum\App\Backend\Designer\Module\Column;
use Dvelum\Filter;
/**
 * Events for action column, migrated from DVelum 0.9.x
 */
class Events extends Column\Events
{
    /*
     * @var string|null
     */
    protected $action = null;

    /**
     * @return bool
     */
    protected function checkInput(): bool
    {
        if(!$this->checkAction()){
            return false;
        }
        return parent::checkInput();
    }

    protected  function checkAction()
    {
        $name = $this->request->post('id', 'string', '');

        if(!$this->column->actionExists($name)){
            $this->response->error($this->lang->get('WRONG_REQUEST'.' invalid action'));
            return;
        }
        $this->action = $this->column->getAction($name);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function checkColumn() : bool 
    {
        $object = $this->object;
        $column = $this->request->post('column','string',false);

        if($column === false || $object->getClass()!=='Grid' || !$object->columnExists($column)){
            $this->response->error('Cant find column');
            return false;
        }

        $columnObject = $object->getColumn($column);

        if($columnObject->getClass()!=='Grid_Column_Action'){
            $this->response->error('Invalid column type');
            return false;
        }

        $this->column = $columnObject;
        return true;
    }

    /**
     * Get events for object
     */
    public function objectEventsAction()
    {
        if($this->checkInput()){
            return;
        }
        $objectName = $this->action->getName();

        $objectEvents = $this->project->getEventManager()->getObjectEvents($objectName);

        $events = $this->action->getConfig()->getEvents();

        $result = [];
        $id =1;
        foreach ($events as $name=>$config)
        {
            if(isset($objectEvents[$name]) && !empty($objectEvents[$name]))
                $hasCode = true;
            else
                $hasCode = false;

            $result[] = [
                'id'=>$id,
                'object'=>$objectName,
                'event'=>$name,
                'params'=>$this->convertParams($config),
                'has_code'=>$hasCode
            ];
            $id++;
        }
        $this->response->success($result);
    }

    public function eventCodeAction()
    {
        if($this->checkInput()){
            return;
        }
        
        $project = $this->getProject();
        $event = $this->getEvent();
        if(empty($event)){
            return;
        }

        $eventManager = $project->getEventManager();

        if($eventManager->eventExists($this->action->getName(), $event))
            $code = $eventManager->getEventCode($this->action->getName(), $event);
        else
            $code = '';

        $this->response->success(['code'=>$code]);
    }

    public function saveEventAction()
    {
        if($this->checkInput()){
            return;
        }
        
        $project = $this->getProject();

        $event = $this->getEvent();
        if(empty($event)){
            return;
        }
        $code = $this->request->post('code', 'raw', '');
        $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);
        if(empty($buffer)){
            $buffer = false;
        }
        $events = $this->action->getConfig()->getEvents();

        $project->getEventManager()->setEvent($this->action->getName(), $event, $code , $events->$event, false, $buffer);
        $this->storeProject();
        $this->response->success();
    }

    public function removeEventAction()
    {
        if($this->checkInput()){
            return;
        }
        $project = $this->getProject();
        $event = $this->getEvent();
        if(empty($event)){
            return;
        }
        $project->getEventManager()->removeObjectEvent($this->action->getName() , $event);
        $this->storeProject();
        $this->response->success();
    }

}