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
use Dvelum\Orm\Record;

class Orm extends Module
{
    /**
     * Get list of objects from ORM
     */
    public function listAction()
    {
        $manager = new Record\Manager();
        $objects = $manager->getRegisteredObjects();
        $data = [];

        if (!empty($objects)) {
            foreach ($objects as $name) {
                $data[] = [
                    'name' => $name,
                    'title' => Orm\Record\Config::factory($name)->getTitle()
                ];
            }
        }
        $this->response->success($data);
    }

    /**
     * Get list of ORM object fields
     */
    public function fieldsAction()
    {
        $objectName = Request::post('object', 'string', false);
        if (!$objectName) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        try {
            $config = Record\Config::factory($objectName);
        } catch (\Exception $e) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $fields = $config->getFieldsConfig();
        if (empty($fields)) {
            $this->response->success([]);
        }

        $data = [];

        foreach ($fields as $name => $cfg)
        {
            $type = $cfg['db_type'];
            $field = $config->getField($name);

            if ($field->isLink()){
                if ($field->isDictionaryLink()){
                    $type = $this->lang->get('DICTIONARY_LINK') . '"' . $config->getField($name)->getLinkedDictionary() . '"';
                } else {
                    $obj = $field->getLinkedObject();
                    $oName = $obj . '';
                    try {
                        $oCfg = Record\Config::factory($obj);
                        $oName .= ' (' . $oCfg->get('title') . ')';
                    } catch (\Exception $e) {
                        //empty on error
                    }
                    $type = $this->lang->get('OBJECT_LINK') . ' - ' . $oName;
                }
            }

            $data[] = [
                'name' => $name,
                'title' => $cfg['title'],
                'type' => $type
            ];
        }
        $this->response->success($data);
    }
}