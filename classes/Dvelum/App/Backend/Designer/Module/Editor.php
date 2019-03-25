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

use Dvelum\Filter;

/**
 * Operations with Column editor
 */
class Editor extends Properties
{
    protected function getColumn()
    {
        /*
         * Grid
         */
        $o = parent::getObject();
        $col = $this->request->post('column', 'string', false);

        if ($col === false || !$o->columnExists($col)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return null;
        }
        return $o->getColumn($col);
    }

    protected function getObject()
    {
        $column = $this->getColumn();
        if(is_null($column)){
            return null;
        }
        $object = $column->editor;

        if (empty($object)) {
            $object = \Ext_Factory::object('Form_Field_Text');
            $object->setName(parent::getObject()->getName() . '_' . $column->getName() . '_editor');
            $column->editor = $object;
            $this->storeProject();
        }
        return $object;
    }

    protected function setEditor(\Ext_Object $editor)
    {
        $this->getColumn()->editor = $editor;
    }

    /**
     * Remove column editor
     */
    public function removeAction()
    {
        $this->getProject()->getEventManager()->removeObjectEvents($this->getObject()->getName());
        $this->getColumn()->editor = '';
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Change field type
     */
    public function changeTypeAction()
    {
        if(!$this->checkLoaded()){
            return;
        }

        $object = $this->getObject();

        $type = $this->request->post('type', 'string', false);
        $adapter = $this->request->post('adapter', 'string', false);
        $dictionary = $this->request->post('dictionary', 'string', false);

        if ($type === 'Form_Field_Adapter') {
            $newObject = \Ext_Factory::object($adapter);
            /*
             * Invalid adapter
             */
            if (!$adapter || !strlen($adapter) || !class_exists($adapter)){
                $this->response->error($this->lang->get('INVALID_VALUE'), array('adapter' => $this->lang->get('INVALID_VALUE')));
                return;
            }

            if ($adapter === 'Ext_Component_Field_System_Dictionary') {
                /*
                 * Inavalid dictionary
                 */
                if (!$dictionary || !strlen($dictionary)){
                    $this->response->error($this->lang->get('INVALID_VALUE'), array('dictionary' =>$this->lang->get('INVALID_VALUE')));
                    return;
                }
                $newObject->dictionary = $dictionary;

            }
        } else {
            $newObject = \Ext_Factory::object($type);
            /*
             * No changes
             */
            if ($type === $object->getClass()){
                $this->response->success();
                return;
            }
        }

        \Ext_Factory::copyProperties($object, $newObject);
        $newObject->setName($object->getName());

        $this->getProject()->getEventManager()->removeObjectEvents($newObject->getName());

        $this->setEditor($newObject);
        $this->storeProject();
        $this->response->success();
    }


    /**
     * Get events for object
     */
    public function objectEventsAction()
    {
        $project = $this->getProject();
        $object = $this->getObject();
        $objectName = $object->getName();
        $objectEvents = $project->getEventManager()->getObjectEvents($objectName);

        $events = $object->getConfig()->getEvents();

        $result = [];
        $id = 1;
        foreach ($events as $name => $config) {
            if (isset($objectEvents[$name]) && !empty($objectEvents[$name]))
                $hasCode = true;
            else
                $hasCode = false;

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

    protected function convertParams($config)
    {
        if (empty($config))
            return '';

        $paramsArray = [];

        foreach ($config as $pName => $pType)
            $paramsArray[] = '<span style="color:green;">' . $pType . '</span> ' . $pName;

        return implode(' , ', $paramsArray);
    }

    protected function getEvent()
    {
        $event = $this->request->post('event', 'string', false);
        if (!strlen($event) || $event === false){
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            $this->response->send();
            exit();
        }
        return $event;
    }

    public function eventCodeAction()
    {
        $objectName = $this->getObject()->getName();
        $project = $this->getProject();

        $event = $this->getEvent();

        $eventManager = $project->getEventManager();

        if ($eventManager->eventExists($objectName, $event))
            $code = $eventManager->getEventCode($objectName, $event);
        else
            $code = '';

        $this->response->success(['code' => $code]);
    }


    public function saveEventAction()
    {
        $objectName = $this->getObject()->getName();
        $project = $this->getProject();

        $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);
        if (empty($buffer)) {
            $buffer = false;
        }

        $event = $this->getEvent();
        $code = $this->request->post('code', 'raw', '');

        $events = $this->getObject()->getConfig()->getEvents();

        $project->getEventManager()->setEvent($objectName, $event, $code, $events->$event, false, $buffer);
        $this->storeProject();

        $this->response->success();
    }

    public function removeEventAction()
    {
        $event = $this->getEvent();
        $name = $this->getObject()->getName();
        $project = $this->getProject();

        $project->getEventManager()->removeObjectEvent($name, $event);
        $this->storeProject();
        $this->response->success();
    }
}