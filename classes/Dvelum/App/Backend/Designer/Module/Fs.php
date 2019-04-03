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
use Dvelum\Designer\Manager;

class Fs extends Module
{
    /**
     * Files list
     */
    public function fsListAction()
    {
        $node = $this->request->post('node', 'string', '');
        $manager = new Manager($this->appConfig);
        $this->response->json($manager->getProjectsList($node));
    }

    /**
     * Create config sub folder
     */
    public function fsMakeDirAction()
    {
        $name = $this->request->post('name', 'string', '');
        $path = $this->request->post('path', 'string', '');

        $name = str_replace([DIRECTORY_SEPARATOR], '', $name);

        if (!strlen($name)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' [code 1]');
            return;
        }

        $newPath = Config::storage()->getWrite() . $this->designerConfig->get('configs');

        if (strlen($path)) {
            if (!is_dir($newPath . $path)) {
                $this->response->error($this->lang->get('WRONG_REQUEST') . ' [code 2]');
                return;
            }
            $newPath .= $path . DIRECTORY_SEPARATOR;
        }

        $newPath .= DIRECTORY_SEPARATOR . $name;

        if (@mkdir($newPath, 0775)) {
            $this->response->success();
        } else {
            $this->response->error($this->lang->get('CANT_WRITE_FS') . ' ' . $newPath);
            return;
        }
    }

    /**
     * Create new report
     */
    public function fsmakefileAction()
    {
        $name = $this->request->post('name', 'string', '');
        $path = $this->request->post('path', 'string', '');

        if (!strlen($name)) {
            $this->response->error($this->lang->get('WRONG_REQUEST') . ' [code 1]');
            return;
        }

        $writePath = Config::storage()->getWrite();
        $configsPath = $this->designerConfig->get('configs');
        $actionsPath = $this->designerConfig->get('actionjs_path');

        if (strlen($path)) {
            $savePath = $writePath . $configsPath . $path . DIRECTORY_SEPARATOR . $name . '.designer.dat';
            $relPath = $path . DIRECTORY_SEPARATOR . $name . '.designer.dat';
            $actionFilePath = $actionsPath . str_replace($configsPath, '', $path) . DIRECTORY_SEPARATOR . $name . '.js';
        } else {
            $savePath = $writePath . $configsPath . $name . '.designer.dat';
            $relPath = DIRECTORY_SEPARATOR . $name . '.designer.dat';
            $actionFilePath = $actionsPath . $name . '.js';
        }

        $relPath = str_replace('//', '/', $relPath);
        $savePath = str_replace('//', '/', $savePath);

        if (file_exists($savePath)) {
            $this->response->error($this->lang->get('FILE_EXISTS'));
            return;
        }

        $obj = new \Designer_Project();
        $obj->actionjs = $actionFilePath;

        $dir = dirname($savePath);

        if (!file_exists($dir)) {
            try {
                mkdir($dir, 0775, true);
            } catch (\Error $e) {
                $this->response->error($this->lang->get('CANT_WRITE_FS') . ' ' . $dir);
                return;
            }
        }

        if ($this->storage->save($savePath, $obj)) {
            $this->response->success(['file' => $relPath]);
        } else {
            $this->response->error($this->lang->get('CANT_WRITE_FS') . ' ' . $savePath);
        }
    }
}