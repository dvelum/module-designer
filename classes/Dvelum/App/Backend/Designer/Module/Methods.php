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
use Dvelum\Designer\Project;

class Methods extends Module
{
    /**
     * Get list of Project methods
     */
    public function listAction()
    {
        $project = $this->getProject();
        $methods = $project->getMethodManager()->getMethods();

        $result = [];
        foreach ($methods as $object => $list) {
            foreach ($list as $item) {
                if ($this->getProject()->objectExists($object)) {
                    $result[] = $this->methodToArray($item, $object);
                }
            }
        }
        $this->response->success($result);
    }

    /**
     * Conver method object into array
     * @param Project\Methods\Item $method
     * @param string $objectName
     * @return array
     * @throws \Exception
     */
    protected function methodToArray(Project\Methods\Item $method, string $objectName) : array
    {
        $object = $this->getProject()->getObject($objectName);
        $code = $method->getCode();
        return [
            'object' => $objectName,
            'method' => $method->getName(),
            'params' => $method->getParamsAsDescription(),
            'has_code' => (!empty($code)),
            'description' => $method->getDescription(),
            'enabled' => $object->isExtendedComponent()
        ];
    }

    /**
     * Get list of object methods
     */
    public function objectMethodsAction()
    {
        $project = $this->getProject();
        $name = $this->request->post('object', 'string', '');

        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->success([]);
            return;
        }

        $object = $project->getObject($name);
        $objectName = $object->getName();
        $objectMethods = $project->getMethodManager()->getObjectMethods($objectName);

        $result = [];

        foreach ($objectMethods as $item) {
            $result[] = $this->methodToArray($item, $name);
        }

        $this->response->success($result);
    }

    /**
     * Add new object method
     */
    public function addMethodAction()
    {
        $project = $this->getProject();
        $objectName = $this->request->post('object', 'string', '');
        $objectMethodSrc = $this->request->post('method', 'string', '');
        $objectMethod = Filter::filterValue(Filter::FILTER_ALPHANUM, $objectMethodSrc);

        if (!strlen($objectName) || !$project->objectExists($objectName)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if (!strlen($objectMethodSrc)) {
            $this->response->error($this->lang->get('CANT_BE_EMPTY'));
            return;
        }

        if ($objectMethodSrc !== $objectMethod) {
            $this->response->error($this->lang->get('INVALID_VALUE'));
            return;
        }

        $methodsManager = $project->getMethodManager();

        if ($methodsManager->methodExists($objectName, $objectMethod)) {
            $this->response->error($this->lang->get('SB_UNIQUE'));
            return;
        }

        if (!$methodsManager->addMethod($objectName, $objectMethod)) {
            $this->response->error($this->lang->get('CANT_EXEC'));
            return;
        }

        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove object method
     */
    public function removeMethodAction()
    {
        $project = $this->getProject();
        $objectName = $this->request->post('object', 'string', '');
        $objectMethod = $this->request->post('method', Filter::FILTER_ALPHANUM, '');

        $methodManager = $project->getMethodManager();

        if (!strlen($objectName) || !$project->objectExists($objectName) || !strlen($objectMethod) || !$methodManager->methodExists($objectName, $objectMethod)) {
           $this->response->error($this->lang->get('WRONG_REQUEST'));
           return;
        }

        $methodManager->removeMethod($objectName, $objectMethod);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Get method data (name , params , code)
     */
    public function methoddataAction()
    {
        $project = $this->getProject();
        $objectName = $this->request->post('object', 'string', '');
        $objectMethod = $this->request->post('method', Filter::FILTER_ALPHANUM, '');

        $methodManager = $project->getMethodManager();

        if (!strlen($objectName) || !$project->objectExists($objectName) || !strlen($objectMethod) || !$methodManager->methodExists($objectName, $objectMethod)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $method = $methodManager->getObjectMethod($objectName, $objectMethod);
        $this->response->success($method->toArray());
    }

    /**
     * Update method data
     */
    public function updateAction()
    {
        $project = $this->getProject();
        $objectName = $this->request->post('object', 'string', '');
        $objectMethod = $this->request->post('method', Filter::FILTER_ALPHANUM, '');

        $newName = $this->request->post('method_name', Filter::FILTER_ALPHANUM, '');
        $description = $this->request->post('description', Filter::FILTER_STRING, '');
        $code = $this->request->post('code', Filter::FILTER_RAW, '');
        $params = $this->request->post('params', Filter::FILTER_STRING, '');

        $methodManager = $project->getMethodManager();

        if (!strlen($objectName) || !$project->objectExists($objectName) || !strlen($objectMethod) || !$methodManager->methodExists($objectName, $objectMethod)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if (!strlen($newName)) {
            $this->response->error(
                $this->lang->get('FILL_FORM'),
                ['method_name' => $this->lang->get('CANT_BE_EMPTY')]
            );
            return;
        }

        if ($objectMethod !== $newName) {
            if ($methodManager->methodExists($objectName, $newName)) {
                $this->response->error($this->lang->get('FILL_FORM'),
                    ['method_name' => $this->lang->get('SB_UNIQUE')]
                );
                return;
            }

            if (!$methodManager->renameMethod($objectName, $objectMethod, $newName)) {
                $this->response->error($this->lang->get('CANT_EXEC') . ' (rename)');
                return;
            }
        }

        $method = $methodManager->getObjectMethod($objectName, $newName);

        $method->setDescription($description);
        $method->setCode($code);
        $paramsArray = [];
        if (!empty($params)) {
            $params = explode(',', trim($params));
            foreach ($params as $v) {
                $param = explode(' ', trim($v));
                if (count($param) == 1) {
                    $paramsArray[] = ['name' => trim($v), 'type' => ''];
                } else {
                    $pName = array_pop($param);
                    $ptype = trim(implode(' ', str_replace('  ', ' ', $param)));
                    $paramsArray[] = ['name' => $pName, 'type' => $ptype];
                }
            }
        }
        $method->setParams($paramsArray);
        $this->storeProject();
        $this->response->success();
    }
}
