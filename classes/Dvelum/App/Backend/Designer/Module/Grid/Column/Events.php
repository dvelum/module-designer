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

namespace Dvelum\App\Backend\Designer\Module\Grid\Column;

use Dvelum\App\Backend\Designer\Module;
use Dvelum\Filter;

/**
 * Events for grid columns
 */
class Events extends Module\Column\Events
{
    /**
     * Get events for object
     */
    public function objectEventsAction()
    {
        $eObject = $this->getEventObject();
        $objectEvents = $this->project->getEventManager()->getObjectEvents($eObject);

        $events = $this->column->getConfig()->getEvents();

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
                'object' => $eObject,
                'event' => $name,
                'params' => $this->convertParams($config),
                'has_code' => $hasCode
            ];
            $id++;
        }
        $this->response->success($result);
    }

    /**
     * Get event code (JS code)
     */
    public function eventCodeAction()
    {
        $project = $this->getProject();
        $event = $this->getEvent();

        $eventManager = $project->getEventManager();

        $eObject = $this->getEventObject();

        if ($eventManager->eventExists($eObject, $event)) {
            $code = $eventManager->getEventCode($eObject, $event);
        } else {
            $code = '';
        }

        $this->response->success(['code' => $code]);
    }

    /**
     * Generate event object name
     */
    protected function getEventObject()
    {
        return $this->object->getName() . '.column.' . $this->column->getName();
    }

    /**
     * Update column event
     */
    public function saveEventAction()
    {
        $project = $this->getProject();

        $event = $this->getEvent();
        $code = $this->request->post('code', 'raw', '');
        $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);
        if (empty($buffer)) {
            $buffer = false;
        }
        $events = $this->column->getConfig()->getEvents();

        $eObject = $this->getEventObject();

        $project->getEventManager()->setEvent($eObject, $event, $code, $events->$event, false, $buffer);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove column event
     */
    public function removeEventAction()
    {
        $project = $this->getProject();
        $event = $this->getEvent();
        $eObject = $this->getEventObject();
        $project->getEventManager()->removeObjectEvent($eObject, $event);
        $this->storeProject();
        $this->response->success();
    }
}