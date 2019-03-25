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
use Dvelum\Filter;

class Events extends Module
{
    /**
     * Get all registered events
     */
    public function listAction()
    {
        $project = $this->getProject();
        $eventManager = $project->getEventManager();
        $list = $eventManager->getEvents();

        $result = [];

        foreach ($list as $o => $e) {
            if (empty($e)) {
                continue;
            }

            if ($project->objectExists($o)) {
                $object = $project->getObject($o);
                $eventObject = $object;

                while (method_exists($eventObject, 'getObject')) {
                    $eventObject = $eventObject->getObject();
                }

                $events = $eventObject->getConfig()->getEvents()->__toArray();


                foreach ($e as $eName => $eConfig) {
                    if (isset($events[$eName])) {
                        $eConfig['params'] = $this->convertParams($events[$eName]);
                    } else {
                        $eConfig['params'] = '';
                    }

                    unset($eConfig['code']);
                    $eConfig['is_local'] = true;
                    $result[] = $eConfig;

                }
            } else {
                /*
                 * Sub items with events
                 */
                foreach ($e as $eName => $eConfig) {
                    if (isset($eConfig['params']) && is_array($eConfig['params'])) {
                        $eConfig['params'] = $this->convertParams($eConfig['params']);
                    } else {
                        $eConfig['params'] = '';
                    }

                    unset($eConfig['code']);
                    $eConfig['is_local'] = true;
                    $result[] = $eConfig;
                }
            }
        }
        $this->response->success($result);
    }

    /**
     * @param $config
     * @return string
     */
    protected function convertParams($config): string
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
        $project = $this->getProject();
        $object = $this->getObject();

        $objectName = $object->getName();

        $objectEvents = $project->getEventManager()->getObjectEvents($objectName);

        if ($object->isInstance()) {
            $events = $object->getObject()->getConfig()->getEvents();
        } else {
            $events = $object->getConfig()->getEvents();
        }

        $result = [];
        $id = 1;
        foreach ($events as $name => $config) {
            if (isset($objectEvents[$name]) && !empty($objectEvents[$name])) {
                $hasCode = true;
            } else {
                $hasCode = false;
            }

            $result[$name] = [
                'id' => $id,
                'object' => $objectName,
                'event' => $name,
                'params' => $this->convertParams($config),
                'has_code' => $hasCode,
                'is_local' => false
            ];
            $id++;
        }

        $localEvents = $project->getEventManager()->getLocalEvents($objectName);
        foreach ($localEvents as $name => $description) {
            if (isset($result[$name])) {
                continue;
            }

            $result[$name] = [
                'id' => $name,
                'object' => $objectName,
                'event' => $name,
                'params' => $this->convertParams($description['params']),
                'has_code' => !empty($description['code']),
                'is_local' => true
            ];

        }
        $this->response->success(array_values($result));
    }

    protected function getEvent()
    {
        $event = $this->request->post('event', 'string', false);
        if (!strlen($event) || $event === false) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
        }
        return $event;
    }

    /**
     * Load event description
     */
    public function eventCodeAction()
    {
        $objectName = $this->request->post('object', 'string', '');
        $project = $this->getProject();

        if (!strlen($objectName)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $event = $this->getEvent();

        $eventManager = $project->getEventManager();

        if ($eventManager->eventExists($objectName, $event)) {
            $info = $eventManager->getEventInfo($objectName, $event);
        } else {
            $info = ['code' => ''];
        }

        if (isset($info['params'])) {
            $info['params'] = strip_tags($this->convertParams($info['params']));
        } else {
            $info['params'] = '';
        }

        $this->response->success($info);
    }

    /**
     * Update event description
     */
    public function saveEventAction()
    {
        $project = $this->getProject();

        $name = $this->request->post('object', 'string', '');

        if (!strlen($name)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        if ($project->objectExists($name)) {
            $object = $this->getObject();
        } else {
            $object = false;
        }

        $event = $this->getEvent();

        $eventManager = $project->getEventManager();

        if ($object && $eventManager->isLocalEvent($object->getName(), $event)) {
            $newName = $this->request->post('new_name', Filter::FILTER_ALPHANUM, '');

            if (!strlen($newName)) {
                $this->response->error(
                    $this->lang->get('FILL_FORM'),
                    ['new_name' => $this->lang->get('CANT_BE_EMPTY')]
                );
                return false;
            }

            $params = $this->request->post('params', Filter::FILTER_STRING, '');
            $code = $this->request->post('code', Filter::FILTER_RAW, '');
            $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);

            if (empty($buffer)) {
                $buffer = false;
            }

            if (!$eventManager->eventExists($object->getName(), $event)) {
                $this->response->error($this->lang->get('WRONG_REQUEST'));
                return false;
            }

            if (!empty($params)) {
                $params = explode(',', trim($params));
                $paramsArray = [];
                foreach ($params as $v) {
                    $param = explode(' ', trim($v));
                    if (count($param) == 1) {
                        $paramsArray[trim($v)] = '';
                    } else {
                        $pName = array_pop($param);
                        $ptype = trim(implode(' ', str_replace('  ', ' ', $param)));
                        $paramsArray[$pName] = $ptype;
                    }
                }
                $params = $paramsArray;
            }
            $eventManager->setEvent($object->getName(), $event, $code, $params, true, $buffer);

            if ($newName !== $event) {
                if ($eventManager->eventExists($object,
                        $newName) || $object->getConfig()->getEvents()->isValid($newName)) {
                    $this->response->error($this->lang->get('FILL_FORM'),
                        ['new_name' => $this->lang->get('SB_UNIQUE')]);
                    return;
                }
                $eventManager->renameLocalEvent($object->getName(), $event, $newName);
            }
            $this->storeProject();
            $this->response->success();
        } else {
            // update event action for std event
            $this->setCodeAction();
        }
    }


    public function setCodeAction()
    {
        $objectName = $this->request->post('object', 'string', '');
        $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);

        if (empty($buffer)) {
            $buffer = false;
        }
        $project = $this->getProject();

        if (!strlen($objectName)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $event = $this->getEvent();
        $code = $this->request->post('code', 'raw', '');

        $project->getEventManager()->setEvent($objectName, $event, $code, false, false, $buffer);
        $this->storeProject();

        $this->response->success();
    }

    public function removeEventAction()
    {
        $project = $this->getProject();
        $event = $this->getEvent();
        $object = $this->request->post('object', 'string', '');

        if (!strlen($object)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $eventManager = $project->getEventManager();

        if ($eventManager->isLocalEvent($object, $event)) {
            $eventManager->updateEvent($object, $event, '');
        } else {
            $eventManager->removeObjectEvent($object, $event);
        }
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove local event
     */
    public function removeEventDescriptionAction()
    {
        $project = $this->getProject();
        $event = $this->getEvent();

        $object = $this->request->post('object', 'string', '');

        if (!strlen($object)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $eventManager = $project->getEventManager();
        $eventManager->removeObjectEvent($object, $event);

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Create local event fore extended object
     */
    public function addLocalEventAction()
    {
        $project = $this->getProject();
        $event = Filter::filterValue(Filter::FILTER_ALPHANUM, $this->getEvent());
        $object = $this->getObject();

        $eventManager = $project->getEventManager();

        if ($eventManager->eventExists($object->getName(),
                $event) || $object->getConfig()->getEvents()->isValid($event)) {
            $this->response->error($this->lang->get('SB_UNIQUE'));
            return;
        }

        $eventManager->setEvent($object->getName(), $event, '', '', true);
        $this->storeProject();

        $this->response->success();
    }
}
