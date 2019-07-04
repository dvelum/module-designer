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
use Dvelum\App\Dictionary\Manager;
use Dvelum\Config;
use Dvelum\Designer\Factory;
use Dvelum\File;
use Dvelum\App\Session\User;

class Viewframe extends Module
{
    public function indexAction()
    {
        if (!$this->session->keyExists('loaded') || !$this->session->get('loaded')) {
            $this->response->error('Project is not loaded');
            exit;
        }

        $designerConfig = Config::storage()->get('designer.php');
        $backendConfig = Config::storage()->get('backend.php');

        //$adminTheme = $backendConfig->get('theme');
        // change theme
        $designerTheme = $designerConfig->get('application_theme');
        $backendConfig->set('theme', $designerTheme);

        $this->page->setTemplatesPath('system/' . $designerTheme . '/');


        $res = \Dvelum\Resource::factory();
        $backendScripts = Config::storage()->get('js_inc_backend.php');
        if ($backendScripts->getCount()) {
            $js = $backendScripts->get('js');
            if (!empty($js)) {
                foreach ($js as $file => $config) {
                    $res->addJs($file, $config['order'], $config['minified']);
                }
            }

            $css = $backendScripts->get('css');
            if (!empty($css)) {
                foreach ($css as $file => $config) {
                    $res->addCss($file, $config['order']);
                }
            }
        }

       // Model::factory('Medialib')->includeScripts();

        $res->addJs('/js/app/system/SearchPanel.js');
        $res->addJs('/js/app/system/HistoryPanel.js', 0);
        //$res->addJs('/js/lib/ext_ux/RowExpander.js', 0);
        $res->addJs('/js/app/system/RevisionPanel.js', 1);
        $res->addJs('/js/app/system/EditWindow.js', 2);
        $res->addJs('/js/app/system/ContentWindow.js', 3);
        $res->addJs('/resources/dvelum-module-designer/js/designer/viewframe/main.js', 4);
        $res->addJs('/resources/dvelum-module-designer/js/designer/lang/' . $designerConfig['lang'] . '.js', 5);

        $project = $this->getProject();
        $projectCfg = $project->getConfig();

        \Ext_Code::setRunNamespace($projectCfg['runnamespace']);
        \Ext_Code::setNamespace($projectCfg['namespace']);

        $grids = $project->getGrids();

        if (!empty($grids)) {
            foreach ($grids as $name => $object) {
                if ($object->isInstance()) {
                    continue;
                }

                $cols = $object->getColumns();
                if (!empty($cols)) {
                    foreach ($cols as $column) {
                        $column['data']->projectColId = $column['id'];
                    }
                }

                $object->addListener('columnresize', '{
                     fn:function( ct, column, width,eOpts){
                        app.application.onGridColumnResize("' . $name . '", ct, column, width, eOpts);
                     }
                }');

                $object->addListener('columnmove', '{
                    fn:function(ct, column, fromIdx, toIdx, eOpts){
                        app.application.onGridColumnMove("' . $name . '", ct, column, fromIdx, toIdx, eOpts);
                    }
                }');
            }
        }

        $dManager = Manager::factory();
        $key = 'vf_' . md5($dManager->getDataHash() . serialize($project));

        $templates = $designerConfig->get('templates');
        $replaces = array(
            array('tpl' => $templates['wwwroot'], 'value' => $this->appConfig->get('wwwroot')),
            array('tpl' => $templates['adminpath'], 'value' => $this->appConfig->get('adminPath')),
            array('tpl' => $templates['urldelimiter'], 'value' => $this->appConfig->get('urlDelimiter')),
        );

        $includes = Factory::getProjectIncludes($key, $project, true, $replaces, true);

        if (!empty($includes)) {
            foreach ($includes as $file) {
                if (File::getExt($file) == '.css') {
                    $res->addCss($file, 100);
                } else {
                    $res->addJs($file, false, false);
                }
            }
        }

        $names = $project->getRootPanels();

        $basePaths = [];

        $parts = explode('/', $this->appConfig->get('wwwroot'));
        if (is_array($parts) && !empty($parts)) {
            foreach ($parts as $item) {
                if (!empty($item)) {
                    $basePaths[] = $item;
                }
            }
        }

        $basePaths[] = $this->appConfig['adminPath'];
        $basePaths[] = 'designer';
        $basePaths[] = 'sub';

        //' . $project->getCode($replaces) . '
        $initCode = '
        app.delimiter = "' . $this->appConfig['urlDelimiter'] . '";
        app.admin = "' . $this->appConfig->get('wwwroot') . $this->appConfig->get('adminPath') . '";
        app.wwwRoot = "' . $this->appConfig->get('wwwroot') . '";
    
        var applicationClassesNamespace = "' . $projectCfg['namespace'] . '";
        var applicationRunNamespace = "' . $projectCfg['runnamespace'] . '";
        var designerUrlPaths = ["' . implode('","', $basePaths) . '"];
    
        var canDelete = true;
        var canPublish = true;
        var canEdit = true;
    
        app.permissions = Ext.create("app.PermissionsStorage");
        var rights = ' . json_encode(User::factory()->getModuleAcl()->getPermissions()) . ';
        app.permissions.setData(rights);
    
        Ext.onReady(function(){
            app.application.mainUrl = app.createUrl(designerUrlPaths);
            ';

        if (!empty($names)) {
            foreach ($names as $name) {
                if ($project->getObject($name)->isExtendedComponent()) {

                    /*if($project->getObject($name)->getConfig()->defineOnly)
                        continue;
                    */
                    $initCode .= \Ext_Code::appendRunNamespace($name) . ' = Ext.create("' . \Ext_Code::appendNamespace($name) . '",{});';
                }
                $initCode .= '
                    app.viewFrame.add(' . \Ext_Code::appendRunNamespace($name) . ');
                ';
            }
        }

        $initCode .= '
             app.application.fireEvent("projectLoaded");
       });';

        $res->addInlineJs($initCode);

        $backendConfig = Config::storage()->get('backend.php');
        $tpl = \Dvelum\View::factory();
        $tpl->lang = $this->appConfig['language'];
        $tpl->development = $this->appConfig['development'];
        $tpl->resource = $res;
        $tpl->useCSRFToken = $backendConfig->get('use_csrf_token');
        $tpl->theme = $designerTheme;

        $this->response->put($tpl->render($this->page->getTemplatesPath() . 'designer/viewframe.php'));
        $this->response->send();
    }
}