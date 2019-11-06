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
declare(strict_types=1);

namespace Dvelum\App\Backend\Designer;
use Dvelum\Config;
use \ReflectionClass;
use \ReflectionMethod;
use Dvelum\Request;

class Code
{
    /**
     * Get controller url
     * @param string $controllerName
     * @return string
     */
    static public function getControllerUrl($controllerName)
    {
        $frontConfig = Config::storage()->get('frontend.php');

        $appCfg = Config::storage()->get('main.php');
        $designerConfig = Config::storage()->get($appCfg->get('configs') . 'designer.php');
        $templates = $designerConfig->get('templates');

        if (!class_exists($controllerName)) {
            $namespaceController = str_replace('_','\\',$controllerName);
            if(class_exists($namespaceController)){
                $controllerName = $namespaceController;
            }else{
                return '';
            }
        }


        $reflector = new ReflectionClass($controllerName);

        if (!$reflector->isSubclassOf('\\Dvelum\\App\\Backend\\Controller')  && !$reflector->isSubclassOf('\\Dvelum\\App\\Frontend\\Controller')) {
            return '';
        }

        $url = [];

        $manager = new \Dvelum\App\Module\Manager();

        if ($reflector->isSubclassOf('\\Dvelum\\App\\Backend\\Controller')) {
            $url[] = $templates['adminpath'];
            $url[] = $manager->getModuleName($controllerName);
        } elseif ($reflector->isSubclassOf('\\Dvelum\\App\\Frontend\\Controller')) {
            $routerType = $frontConfig->get('router');
            if (strtolower($routerType) == 'cms') {
                $module = self::moduleByClass($controllerName);
                if ($module !== false) {
                    $urlcode = \Dvelum\Orm\Model::factory('Page')->getCodeByModule($module);
                    if ($urlcode !== false) {
                        $url[] = $urlcode;
                    }
                }

            } elseif ($routerType == 'Path') {
                $paths = explode('_', str_replace(array('Frontend_'), '', $controllerName));
                $pathsCount = count($paths) - 1;
                if ($paths[$pathsCount] === 'Controller') {
                    $paths = array_slice($paths, 0, $pathsCount);
                }

                $url = array_merge($url, $paths);
            } elseif ($routerType == 'Config') {
                $urlCode = self::moduleByClass($controllerName);
                if ($urlCode !== false) {
                    $url[] = $urlCode;
                }
            }
        }
        $url[] = '';
        $request = Request::factory();
        $request->setConfigOption('urldelimiter', $templates['urldelimiter']);
        $request->setConfigOption('wwwroot', $templates['wwwroot']);

        $url = $request->url($url, false);
        $request->setConfigOption('urldelimiter', $appCfg['urlDelimiter']);
        $request->setConfigOption('wwwroot', $appCfg['wwwroot']);

        return $url;
    }

    /**
     * Get possible actions from Controller class.
     * Note! Code accelerator (eaccelerator, apc, xcache, etc ) should be disabled to get comment line.
     * Method returns only public methods that ends with "Action"
     * @param string $controllerName
     * @return array like array(
     *        array(
     *            'name' => action name without "Action" postfix
     *            'comment'=> doc comment
     *        )
     * )
     */
    static public function getPossibleActions($controllerName)
    {
        $manager = new \Dvelum\App\Module\Manager();
        $appCfg = Config::storage()->get('main.php');
        $designerConfig = Config::storage()->get($appCfg->get('configs') . 'designer.php');

        $templates = $designerConfig->get('templates');

        $reflector = new ReflectionClass($controllerName);

        if (!$reflector->isSubclassOf('\\Dvelum\\App\\Backend\\Controller')  && !$reflector->isSubclassOf('\\Dvelum\\App\\Frontend\\Controller')) {
            return [];
        }

        $actions = [];
        $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

        $url = [];

        if ($reflector->isSubclassOf('\\Dvelum\\App\\Backend\\Controller')) {
            $url[] = $templates['adminpath'];
            $url[] = $manager->getModuleName($controllerName);
        } elseif ($reflector->isSubclassOf('\\Dvelum\\App\\Frontend\\Controller')) {

            $frontConfig = Config::storage()->get('frontend');

            if (strtolower($frontConfig->get('router')) == 'cms') {

                $module = self::moduleByClass($controllerName);
                if ($module !== false) {
                    $urlcode = \Dvelum\Orm\Model::factory('Page')->getCodeByModule($module);
                    if ($urlcode !== false) {
                        $url[] = $urlcode;
                    }
                }
            } elseif ($appCfg['frontend_router_type'] == 'path') {
                $paths = explode('_', str_replace(array('Frontend_'), '', $controllerName));
                $pathsCount = count($paths) - 1;

                if ($paths[$pathsCount] === 'Controller') {
                    $paths = array_slice($paths, 0, $pathsCount);
                }

                $url = array_merge($url, $paths);
            } elseif ($appCfg['frontend_router_type'] == 'config') {
                $urlCode = self::moduleByClass($controllerName);
                if ($urlCode !== false) {
                    $url[] = $urlCode;
                }
            }
        }

        if (!empty($methods)) {
            $request = Request::factory();
            $request->setConfigOption('wwwRoot', $templates['wwwroot']);

            foreach ($methods as $method) {
                if (substr($method->name, -6) !== 'Action') {
                    continue;
                }

                $actionName = substr($method->name, 0, -6);
                $paths = $url;
                $paths[] = $actionName;

                $actions[] = array(
                    'name' => $actionName,
                    'code' => $method->name,
                    'url' => $request->url($paths, false),
                    'comment' => self::clearDocSymbols($method->getDocComment())
                );
            }

            $request->setConfigOption('wwwRoot', $appCfg['wwwroot']);
        }
        return $actions;
    }

    static protected function moduleByClass($class)
    {
        $modules = Config::storage()->get('main.php')->get('frontend_modules');
        if (!empty($modules)) {
            foreach ($modules as $k => $config) {
                if ($config['class'] === $class) {
                    return $k;
                }
            }
        }
        return false;
    }

    /**
     * Clear string from comment symbols
     * @param string $string
     */
    static protected function clearDocSymbols($string)
    {
        return str_replace(['/*', '*/', '*'], '', $string);
    }
}