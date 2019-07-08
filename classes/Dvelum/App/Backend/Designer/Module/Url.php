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

use Dvelum\Config;
use Dvelum\File;
use Dvelum\Utils;

class Url extends Module
{
    /**
     * Files list
     */
    public function fsListAction()
    {
        $path = $this->request->post('node', 'string', '');
        $path = str_replace('.', '', $path);

        $autoloaderConfig = Config::storage()->get('autoloader.php');
        $classPaths = $autoloaderConfig['paths'];

        $files = [];

        foreach ($classPaths as $item) {
            if (is_dir($item . $path)) {
                $list = File::scanFiles($item . $path, ['.php'], false, File::Files_Dirs);
                if (!empty($list)) {
                    $pathLen = strlen($item);
                    foreach ($list as &$v) {
                        $v = substr($v, $pathLen);
                    }
                    unset($v);
                    $files = array_merge($files, $list);
                }
            }
        }

        if (empty($files)) {
            $this->response->success([]);
            return;
        }

        sort($files);
        $list = [];

        foreach ($files as $filePath) {
            $text = basename($filePath);

            $obj = new stdClass();
            $obj->id = $filePath;
            $obj->text = $text;

            if ($obj->text === 'Controller.php') {
                $controllerName = str_replace('/', '_', substr($filePath, 1, -4));
                $obj->url = Backend_Designer_Code::getControllerUrl($controllerName);
            } else {
                $obj->url = '';
            }

            if (File::getExt($filePath) === '.php') {
                if ($text !== 'Controller.php' || $path === '/') {
                    continue;
                }

                $obj->leaf = true;
            } else {
                $obj->expanded = false;
                $obj->leaf = false;
            }
            $list[] = $obj;
        }
        $this->response->json($list);
    }


    public function actionsAction()
    {
        $controller = $this->request->post('controller', 'string', '');
        if (!strlen($controller)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        if (strpos($controller, '.php') !== false) {
            $controller = Utils::classFromPath($controller, true);
        }

        $actions = \Backend_Designer_Code::getPossibleActions($controller);
        $this->response->success($actions);
    }


    public function imgDirListAction()
    {
        $path = $this->request->post('node', 'string', '');
        $path = str_replace('.', '', $path);

        $dirPath = $this->appConfig->get('wwwPath');

        if (!is_dir($dirPath . $path)) {
            $this->response->json([]);
            return;
        }

        $files = File::scanFiles($dirPath . $path, false, false, File::DIRS_ONLY);

        if (empty($files)) {
            $this->response->json([]);
            return;
        }

        sort($files);
        $list = [];

        foreach ($files as $filePath) {
            $text = basename($filePath);
            if ($text === '.svn') {
                continue;
            }

            $obj = new stdClass();
            $obj->id = str_replace($dirPath, '', $filePath);
            $obj->text = $text;
            $obj->url = '/' . $obj->id;

            if (is_dir($filePath)) {
                $obj->expanded = false;
                $obj->leaf = false;
            } else {
                $obj->leaf = true;
            }
            $list[] = $obj;
        }
        $this->response->json($list);
    }

    public function imgListAction()
    {
        $templates = $this->designerConfig->get('templates');

        $dirPath = $this->appConfig->get('wwwPath');
        $dir = $this->request->post('dir', 'string', '');

        if (!is_dir($dirPath . $dir)) {
            $this->response->json([]);
            return;
        }

        $files = File::scanFiles($dirPath . $dir, ['.jpg', '.png', '.gif', '.jpeg'], false, File::FILES_ONLY);

        if (empty($files)) {
            $this->response->json([]);
            return;
        }

        sort($files);
        $list = [];

        foreach ($files as $filePath) {
            // ms fix
            $filePath = str_replace('\\', '/', $filePath);

            $text = basename($filePath);
            if ($text === '.svn') {
                continue;
            }

            $list[] = [
                'name' => $text,
                'url' => str_replace($dirPath . '/', $this->appConfig->get('wwwroot'), $filePath),
                'path' => str_replace($dirPath . '/', $templates['wwwroot'], $filePath),
            ];
        }
        $this->response->success($list);
    }
}