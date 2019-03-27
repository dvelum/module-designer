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

class Filter extends Module
{
    /**
     * @var \Designer_Project
     */
    protected $project;
    /**
     * @var \Ext_Grid
     */
    protected $object;
    /**
     * @var \Ext_Grid_Column_Action
     */
    protected $column;
    
    /**
     * @return bool
     */
    protected function checkObject(): bool
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();
        if (!strlen($name) || !$project->objectExists($name) || $project->getObject($name)->getClass() !== 'Grid') {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return false;
        }

        $this->project = $project;
        $this->object = $project->getObject($name);

        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function checkColumn() : bool
    {
        $object = $this->object;
        $column = $this->request->post('column', 'string', false);

        if ($column === false || $object->getClass() !== 'Grid' || !$object->columnExists($column)) {
            $this->response->error('Cant find column');
            return false;
        }
        
        $columnObject = $object->getColumn($column);
        $this->column = $columnObject;
        return true;
    }

    /**
     * Get column filter type
     */
    public function gettypeAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }
        
        $filter = $this->column->filter;
        $type = '';

        if(!empty($filter) && $filter instanceof \Ext_Grid_Filter){
            $type = $filter->getType();
        }
        $this->response->success(['type'=>$type]);
    }

    /**
     * Set column filter type
     */
    public function settypeAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }

        $type = $this->request->post('type','string', false);

        if(empty($type)){
           $this->response->error($this->lang->get('WRONG_REQUEST'));
           return;
        }

        $filter = $this->column->filter;

        if(empty($filter)){
            $filter = \Ext_Factory::object('Grid_Filter_'.ucfirst($type));
            $filter->setName($this->column->getName().'_filter');
        }else{
            if($type !== $filter->getType()){
                $f = \Ext_Factory::object('Grid_Filter_'.ucfirst($type) , $filter->getConfig()->__toArray(true));
                $filter = $f;
            }
        }

        if($filter->getType() == 'date' && empty($filter->fields)){
            $filter->fields  = '{lt: {text: appLang.FILTER_BEFORE_TEXT}, gt: {text: appLang.FILTER_AFTER_TEXT}, eq: {text: appLang.FILTER_ON_TEXT}}';
        }

        $this->column->filter = $filter;
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Get filter properties
     */
    public function listAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }
        
        $filter = $this->column->filter;
        $data = [];
        if(!empty($filter) && $filter instanceof \Ext_Grid_Filter){
            $data = $filter->getConfig()->__toArray();
        }
        unset($data['type']);
        $this->response->success($data);
    }

    /**
     * Set filter property
     */
    public function setpropertyAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }
        
        $property = $this->request->post('name', 'string', false);
        $value = $this->request->post('value', 'raw', false);

        $filter = $this->column->filter;

        if(empty($filter) ||  !$filter instanceof \Ext_Grid_Filter){
            $this->response->error('undefined filter');
            return;
        }

        if(!$filter->isValidProperty($property)){
            $this->response->error('undefined property '.$property);
            return;
        }

        $filter->set($property, $value);
        $this->storeProject();
        $this->response->success();
    }

    /**
     * Remove column filter
     */
    public function removeFilterAction()
    {
        if (!$this->checkLoaded() || !$this->checkObject() || !$this->checkColumn()) {
            return;
        }
        
        $this->column->filter  = null;
        $this->storeProject();
        $this->response->success();
    }
}