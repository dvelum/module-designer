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

class Storesubproperty extends Module\Store
{
    public function propListAction()
    {
        if (!$this->checkObject()) {
            return;
        }

        $proxyType = '';
        $writerType = '';
        $readerType = '';

        $proxy = $this->object->proxy;

        if (!empty($proxy)) {
            $class = explode('_', $proxy->getClass());
            $proxyType = $class[(count($class) - 1)];

            $reader = $this->object->proxy->reader;
            if (!empty($reader)) {
                $class = explode('_', $reader->getClass());
                $readerType = $class[(count($class) - 1)];
            }

            $writer = $this->object->proxy->writer;

            if (!empty($writer)) {
                $class = explode('_', $writer->getClass());
                $writerType = $class[(count($class) - 1)];
            }
        }

        $results = [
            ['name' => 'proxy', 'value' => strtolower($proxyType)],
            ['name' => 'reader', 'value' => strtolower($readerType)],
            ['name' => 'writer', 'value' => strtolower($writerType)],
        ];

        $this->response->success($results);
    }

    public function listAction()
    {
        if (!$this->checkObject()) {
            return;
        }

        $sub = $this->request->post('sub', 'string', '');
        if (!in_array($sub, ['proxy', 'reader', 'writer'])) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        switch ($sub) {
            case 'proxy':

                if ($this->object->proxy === '') {
                    $proxy = \Ext_Factory::object('Data_Proxy_Ajax');
                    $proxy->reader = \Ext_Factory::object('Data_Reader_Json');
                    $proxy->writer = '';
                    $this->object->proxy = $proxy;
                    $this->storeProject();
                }

                $properties = $this->object->proxy->getConfig()->__toArray();
                unset($properties['reader']);
                unset($properties['writer']);
                $this->response->success($properties);
                break;

            case 'reader':

                if ($this->object->proxy->reader) {
                    $this->response->success($this->object->proxy->reader->getConfig()->__toArray());
                } else {
                    $this->response->success([]);
                }
                break;

            case 'writer':
                if (!isset($this->object->proxy->writer) || empty($this->object->proxy->writer)) {
                    $this->response->success([]);
                }

                $this->response->success($this->object->proxy->writer->getConfig()->__toArray());
                break;
        }
    }

    /**
     * Change sub property object type
     */
    public function changeTypeAction()
    {
        if (!$this->checkObject()) {
            return;
        }

        $sub = $this->request->post('sub', 'string', '');
        $type = $this->request->post('type', 'string', '');

        if (!in_array($sub, ['proxy', 'reader', 'writer']) || !strlen($type)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $config = [];

        if ($sub == 'proxy') {
            $obj = $this->object->proxy;
        } else {
            $obj = $this->object->proxy->$sub;
        }

        if (!empty($obj)) {
            $config = $obj->getConfig()->__toArray();
        }

        if ($sub == 'proxy') {
            $this->object->proxy = \Ext_Factory::object('Data_' . ucfirst($sub) . '_' . ucfirst($type), $config);
            $this->object->proxy->type = strtolower($type);

            if ($this->object->proxy->getClass() === 'Data_Proxy_Ajax') {
                $this->object->proxy->startParam = 'pager[start]';
                $this->object->proxy->limitParam = 'pager[limit]';
                $this->object->proxy->sortParam = 'pager[sort]';
                $this->object->proxy->directionParam = 'pager[dir]';
                $this->object->proxy->simpleSortMode = true;
            }
        } else {
            $this->object->proxy->$sub = \Ext_Factory::object('Data_' . ucfirst($sub) . '_' . ucfirst($type), $config);
            $this->object->proxy->$sub->type = strtolower($type);

            if ($this->object->proxy->getClass() === 'Data_Reader_Json') {
                $this->object->proxy->$sub->rootProperty = 'data';
                $this->object->proxy->$sub->totalProperty = 'count';
                $this->object->proxy->$sub->idProperty = 'id';
            }
        }
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Set sub object property
     */
    public function setPropertyAction() : void
    {
        if (!$this->checkObject()) {
            return;
        }
        $property = $this->request->post('name', 'raw', false);
        $value = $this->request->post('value', 'raw', false);
        $sub = $this->request->post('sub', 'string', '');


        if (!in_array($sub, ['proxy', 'reader', 'writer']) || !strlen($property)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if ($sub == 'proxy') {
            $obj = $this->object->proxy;
        } else {
            $obj = $this->object->proxy->$sub;
        }

        if (!$obj->isValidProperty($property)) {
            $this->response->error('');
            return;
        }

        $obj->$property = $value;

        $this->storeProject();
        $this->response->success();
    }
}