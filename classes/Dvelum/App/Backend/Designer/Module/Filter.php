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

/**
 * Operations with filters
 */
class Filter extends Properties
{
    protected function getObject()
    {
        $o = parent::getObject();
        return $o->getViewObject();
    }

    /**
     * Change field type
     */
    public function changeTypeAction()
    {
        if (!$this->checkLoaded()) {
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
            if (!$adapter || !strlen($adapter) || !class_exists($adapter)) {
                $this->response->error($this->lang->get('INVALID_VALUE'),
                    array('adapter' => $this->lang->get('INVALID_VALUE')));
                return;
            }

            if ($adapter === 'Ext_Component_Field_System_Dictionary') {
                /*
                 * Invalid dictionary
                 */
                if (!$dictionary || !strlen($dictionary)) {
                    $this->response->error($this->lang->get('INVALID_VALUE'),
                        array('dictionary' => $this->lang->get('INVALID_VALUE')));
                    return;
                }

                $newObject->dictionary = $dictionary;

            }
        } else {
            $newObject = \Ext_Factory::object($type);
            /*
             * No changes
             */
            if ($type === $object->getClass()) {
                $this->response->success();
                return;
            }
        }

        \Ext_Factory::copyProperties($object, $newObject);
        $newObject->setName($object->getName());
        parent::getObject()->setViewObject($newObject);

        $this->storeProject();
        $this->response->success();
    }
}