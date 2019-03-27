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

namespace Dvelum\App\Backend\Designer\Module\Grid\Column\Filter;

use Dvelum\App\Backend\Designer\Module\Column;
use Dvelum\Filter;

/**
 * Class Events
 * @package Dvelum\App\Backend\Designer\Module\Grid\Column\Filter
 */
class Events extends Column\Events
{
    /**
     * @var \Ext_Grid_Filter
     */
    protected $filter;

    /**
     * @return bool
     */
    protected function checkInput(): bool
    {
        if (!$this->checkFilter()) {
            return false;
        }
        return parent::checkInput();
    }

    /**
     * Check if column has filter
     */
    protected function checkFilter(): bool
    {
        $filter = $this->column->filter;

        if (empty($filter) || !$filter instanceof \Ext_Grid_Filter) {
            $this->response->error($this->lang->get('WRONG_REQUEST' . ' invalid filter'));
            return false;
        }
        $this->filter = $filter;
        return true;
    }

    /**
     * Generate event object name
     */
    protected function getEventObject(): string
    {
        return $this->object->getName() . '.filter.' . $this->filter->getName();
    }

    /**
     * Get events for object
     */
    public function objectEventsAction()
    {
        $eObject = $this->getEventObject();

        $objectEvents = $this->project->getEventManager()->getObjectEvents($eObject);

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
     * Get action code for event
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
     * Save event handler
     */
    public function saveeventAction()
    {
        $project = $this->getProject();

        $event = $this->getEvent();
        $code = $this->request->post('code', 'raw', '');
        $buffer = $this->request->post('buffer', Filter::FILTER_INTEGER, false);
        if (empty($buffer)) {
            $buffer = false;
        }
        $events = $this->filter->getConfig()->getEvents();
        $eObject = $this->getEventObject();

        $project->getEventManager()->setEvent($eObject, $event, $code, $events->$event, false, $buffer);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove event handler
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