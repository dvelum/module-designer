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

class Code extends Module
{
    /**
     * Get JS code for object
     */
    public function objectCodeAction()
    {
        $object = $this->request->post('object', 'string', '');
        $project = $this->getProject();

        if (!$project->objectExists($object)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }


        $projectCfg = $project->getConfig();
        \Ext_Code::setRunNamespace($projectCfg['runnamespace']);
        \Ext_Code::setNamespace($projectCfg['namespace']);

        $templates = $this->designerConfig->get('templates');
        $replaces = [
            [
                'tpl' => $templates['wwwroot'],
                'value' => $this->appConfig->get('wwwroot')
            ],
            [
                'tpl' => $templates['adminpath'],
                'value' => $this->appConfig->get('adminPath')
            ],
            [
                'tpl' => $templates['urldelimiter'],
                'value' => $this->appConfig->get('urlDelimiter')
            ]
        ];

        $code = $project->getObjectCode($object, $replaces);
        $this->response->success($code);
    }

    /**
     * Get JS code for project
     */
    public function projectCodeAction()
    {
        $project = $this->getProject();
        $projectCfg = $project->getConfig();
        $templates = $this->designerConfig->get('templates');
        $replaces = array(
            array(
                'tpl' => $templates['wwwroot'],
                'value' => $this->appConfig->get('wwwroot')
            ),
            array(
                'tpl' => $templates['adminpath'],
                'value' => $this->appConfig->get('adminPath')
            ),
            array(
                'tpl' => $templates['urldelimiter'],
                'value' => $this->appConfig->get('urlDelimiter')
            )
        );
        \Ext_Code::setRunNamespace($projectCfg['runnamespace']);
        \Ext_Code::setNamespace($projectCfg['namespace']);

        $this->response->success($project->getCode($replaces));
    }
}