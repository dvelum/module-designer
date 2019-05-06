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

namespace Dvelum\App\Backend\Designer\Generator\Component;

use Dvelum\App\Backend\Designer\Generator\Component;
use Dvelum\Designer\Project;

use \Ext_Factory;

class Window implements Component
{
    /**
     * (non-PHPdoc)
     * @see Backend_Designer_Generator_Component::addComponent()
     */
	public function addComponent(Project $project, $id , $parentId = false)
	{
	    $windowName = $project->uniqueId($id);
	    $dockedName = $project->uniqueId($windowName.'__docked');
	    $toolbarName = $project->uniqueId($windowName.'_bottom_toolbar');
	    $fillName = $project->uniqueId($windowName.'_footer_fill');
	    $saveName = $project->uniqueId($windowName.'_footer_saveBtn');
	    $cancelName = $project->uniqueId($windowName.'_footer_cancelBtn');
	    $formName = $project->uniqueId($windowName.'_form');
	    
	    $editWindow = Ext_Factory::object('Window');
        $editWindow->setName($windowName);
        $editWindow->extendedComponent(true);
        $editWindow->width = 450;
        $editWindow->height = 500;
        $editWindow->modal = false;
        $editWindow->resizable = true;
        $editWindow->layout = 'fit';
        
        
        $form = Ext_Factory::object('Form');
        $form->setName($formName);
        $form->bodyCls = 'formBody';
        $form->bodyPadding = 5;
        $form->fieldDefaults = '{anchor:"100%",labelWidth:150}';
        $form->scrollable = true;
                 
        $dockObject = Ext_Factory::object('Docked');
        $dockObject->setName($dockedName);
        
        $toolbar = Ext_Factory::object('Toolbar');
        $toolbar->setName($toolbarName);
        $toolbar->dock = 'bottom';
        $toolbar->ui = 'footer';
               
        $fill = Ext_Factory::object('Toolbar_Fill');
        $fill->setName($fillName);
        
        $saveBtn = Ext_Factory::object('Button');
        $saveBtn->setName($saveName);
        $saveBtn->minWidth = 80;
        $saveBtn->text = '[js:]appLang.SAVE';
        
        $cancelBtn = Ext_Factory::object('Button');
        $cancelBtn->setName($cancelName);
        $cancelBtn->minWidth = 80;
        $cancelBtn->text = '[js:]appLang.CANCEL';
        
        if(!$project->addObject(Designer_Project::COMPONENT_ROOT, $editWindow))
            return false;

        if(!$project->addObject($windowName, $dockObject))
            return false;

        if(!$project->addObject($dockedName, $toolbar))
            return false;

        if(!$project->addObject($toolbarName, $fill))
            return false;
 
        if(!$project->addObject($toolbarName, $saveBtn))
            return false;

        if(!$project->addObject($toolbarName, $cancelBtn))
            return false;
        
        if(!$project->addObject($windowName, $form))
            return false;
        
        /*
         * Project events
         */
        $eventManager = $project->getEventManager();
        
        $eventManager->setEvent($cancelName, 'click', 'this.close();');
        $eventManager->setEvent($saveName, 'click', 'this.onSaveData();');
        
        $eventManager->setEvent($windowName, 'dataSaved', '' , '' , true);
        
        /*
         * Project methods
         */
        $methodsManager =  $project->getMethodManager();
        $m = $methodsManager->addMethod(
            $windowName, 
            'onSaveData' , 
            [] ,
            '
      // remove alert, update submit url, set params
      Ext.Msg.alert(appLang.MESSAGE, "Save button click");

     /*
      // form submit template
      var me = this;
	  this.childObjects.'.$formName.'.getForm().submit({
			clientValidation: true,
			waitMsg:appLang.SAVING,
			method:"post",
			url:"[%wroot%][%admp%][%-%]my_controller[%-%]edit",
			params:{
           
          },
			success: function(form, action) {
   		 		if(!action.result.success){
   		 			Ext.Msg.alert(appLang.MESSAGE, action.result.msg);
   		 		} else{
   		 			me.fireEvent("dataSaved");
   		 			me.close();
   		 		}
   	        },
   	        failure: app.formFailure
   	  });
    */
            '
        );
        $m->setDescription('Save data');
        return true;
	}
}