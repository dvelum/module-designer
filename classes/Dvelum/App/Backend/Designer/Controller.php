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

use Dvelum\App\Backend;
use Dvelum\Config;
use Dvelum\Externals\Module\Manager;
use Dvelum\Filter;
use Dvelum\Request;
use Dvelum\Response;
use Dvelum\Orm\Model;
use Dvelum\Utils;

/**
 * Class Controller
 */
class Controller extends Backend\Controller
{
    /**
     * Designer config
     * @var Config\ConfigInterface $config
     */
    protected $designerConfig;
    /**
     * @var string $version
     */
    protected $version = null;

    /**
     * @var string[] $externalScripts
     */
    static protected $externalScripts = [
        /*
         * External
         */
        '/resources/dvelum-module-designer/js/lib/CodeMirror/lib/codemirror.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/hint/show-hint.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/hint/javascript-hint.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/dialog/dialog.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/search/search.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/search/searchcursor.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/search/match-highlighter.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/addon/selection/active-line.js',
        '/resources/dvelum-module-designer/js/lib/CodeMirror/mode/javascript/javascript.js'

    ];
    /**
     * Source scripts
     * @var string[] $scripts
     */
    static protected $scripts = [

        /*
         * Internal
         */
        '/js/app/system/SearchPanel.js',
        '/resources/dvelum-module-designer/js/designer/importDBWindow.js',
        '/resources/dvelum-module-designer/js/designer/application.js',
        '/resources/dvelum-module-designer/js/designer/urlField.js',
        '/resources/dvelum-module-designer/js/designer/iconField.js',
        '/resources/dvelum-module-designer/js/designer/ormSelectorWindow.js',
        '/resources/dvelum-module-designer/js/designer/iconSelectorWinow.js',
        '/resources/dvelum-module-designer/js/designer/defaultsWindow.js',
        '/resources/dvelum-module-designer/js/designer/typedDefaultsWindow.js',
        '/resources/dvelum-module-designer/js/designer/paramsWindow.js',
        '/resources/dvelum-module-designer/js/designer/instanceWindow.js',
        '/resources/dvelum-module-designer/js/designer/configWindow.js',
        '/resources/dvelum-module-designer/js/designer/configWindow.js',
        '/resources/dvelum-module-designer/js/designer/codeEditor.js',
        '/resources/dvelum-module-designer/js/designer/eventsPanel.js',
        '/resources/dvelum-module-designer/js/designer/methodEditor.js',
        '/resources/dvelum-module-designer/js/designer/methodsPanel.js',
        '/resources/dvelum-module-designer/js/designer/eventsEditor.js',
        '/resources/dvelum-module-designer/js/designer/objects.js',
        '/resources/dvelum-module-designer/js/designer/objects/tree.js',
        '/resources/dvelum-module-designer/js/designer/objects/grid.js',
        '/resources/dvelum-module-designer/js/designer/grid.js',
        '/resources/dvelum-module-designer/js/designer/grid/column.js',
        '/resources/dvelum-module-designer/js/designer/grid/column/FilterWindow.js',
        '/resources/dvelum-module-designer/js/designer/grid/column/ActionsWindow.js',
        '/resources/dvelum-module-designer/js/designer/grid/column/RendererWindow.js',
        '/resources/dvelum-module-designer/js/designer/grid/filters.js',
        '/resources/dvelum-module-designer/js/designer/properties.js',
        '/resources/dvelum-module-designer/js/designer/properties/grid.js',
        '/resources/dvelum-module-designer/js/designer/properties/column.js',
        '/resources/dvelum-module-designer/js/designer/properties/gridFilter.js',
        '/resources/dvelum-module-designer/js/designer/properties/dataField.js',
        '/resources/dvelum-module-designer/js/designer/properties/store.js',
        '/resources/dvelum-module-designer/js/designer/properties/treeStore.js',
        '/resources/dvelum-module-designer/js/designer/properties/model.js',
        '/resources/dvelum-module-designer/js/designer/properties/field.js',
        '/resources/dvelum-module-designer/js/designer/properties/window.js',
        '/resources/dvelum-module-designer/js/designer/properties/crudWindow.js',
        '/resources/dvelum-module-designer/js/designer/properties/form.js',
        '/resources/dvelum-module-designer/js/designer/properties/gridEditor.js',
        '/resources/dvelum-module-designer/js/designer/properties/filterComponent.js',
        '/resources/dvelum-module-designer/js/designer/properties/search.js',
        '/resources/dvelum-module-designer/js/designer/properties/mediaItem.js',
        '/resources/dvelum-module-designer/js/designer/properties/jsObject.js',
        '/resources/dvelum-module-designer/js/designer/model.js',
        '/resources/dvelum-module-designer/js/designer/store.js',
        '/resources/dvelum-module-designer/js/designer/relatedProjectItemsWindow.js',
        '/js/app/system/FilesystemWindow.js',
        '/js/app/system/orm/connections.js',
        '/resources/dvelum-module-designer/js/run.js',

    ];

    /**
     * Controller constructor.
     * @param Request $request
     * @param Response $response
     * @throws \Exception
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->designerConfig = Config::storage()->get('designer.php');

        $moduleManager = Manager::factory($this->appConfig);
        $moduleInfo = $moduleManager->getModuleConfig('dvelum', 'module-designer');
        if (!empty($moduleInfo)) {
            $this->version = $moduleInfo['version'];
        }
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return 'Designer';
    }

    /**
     * @return string
     */
    public function getObjectName(): string
    {
        return '';
    }

    /**
     * @throws \Exception
     */
    public function indexAction()
    {
        $configBackend = Config::storage()->get('backend.php');
        // change theme
        $designerTheme = $this->designerConfig->get('theme');
        $configBackend->set('theme', $designerTheme);
        $page = \Dvelum\Page\Page::factory();
        $page->setTemplatesPath('system/' . $designerTheme . '/');

        if($this->designerConfig->get('html_editor')){
            Model::factory('Medialib')->includeScripts();
        }

        $this->resource->addJs('/resources/dvelum-module-designer/js/designer/lang/' . $this->designerConfig->get('lang') . '.js',1);
        $this->resource->addCss('/resources/dvelum-module-designer/css/style.css');
        $this->resource->addCss('/resources/dvelum-module-designer/js/lib/CodeMirror/lib/codemirror.css');
        $this->resource->addCss('/resources/dvelum-module-designer/js/lib/CodeMirror/addon/dialog/dialog.css');
        $this->resource->addCss('/resources/dvelum-module-designer/js/lib/CodeMirror/addon/hint/show-hint.css');
        $this->resource->addCss('/resources/dvelum-module-designer/js/lib/CodeMirror/theme/eclipse.css');




        $dbConfigs = [];
        foreach ($this->appConfig->get('db_configs') as $k => $v) {
            $dbConfigs[] = [
                'id' => $k,
                'title' => $this->lang->get($v['title'])
            ];
        }

        $componentTemplates = Config::storage()->get('designer_templates.php')->__toArray();
        $this->resource->addInlineJs('
              var dbConfigsList = ' . json_encode($dbConfigs) . ';    
              var componentTemplates = ' . json_encode(array_values($componentTemplates)) . ';  
              var designerVersion = "' . $this->version . '";
               app.htmlEditorEnabled = '.((int) $this->designerConfig->get('html_editor')).';
        ');

        $count = 4;

        foreach (self::$externalScripts as $path) {
            $this->resource->addJs($path, $count, true, 'external');
            $count++;
        }

        if (!$this->designerConfig->get('development')) {
            $this->resource->addJs($this->designerConfig->get('compiled_js') . '?v=' . $this->version, $count);
        } else {
            foreach (self::$scripts as $path) {
                $this->resource->addJs($path, $count);
                $count++;
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function subAction()
    {
        $subController = $this->request->getPart(3);
        $subAction = $this->request->getPart(4);

        if ($subController === false || !strlen($subController) || $subAction === false || !strlen($subAction)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $routes = [];
        $routesData = Config::storage()->get('designer_routes.php');
        foreach ($routesData as $k => $v) {
            $routes[strtolower($k)] = $v;
        }

        $subControllerPath = Filter::filterValue('pagecode', $subController);
        if (isset($routes[$subControllerPath])) {
            $subController = $routes[$subControllerPath];
        } else {
            $subController = '\\Dvelum\\App\\Backend\\Designer\\Module\\' . ucfirst(Filter::filterValue('pagecode', $subController));
        }

        $subAction = Filter::filterValue('pagecode', $subAction) . 'Action';

        if (!class_exists($subController) || !method_exists($subController, $subAction)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $sub = new $subController($this->request, $this->response);
        $sub->$subAction();

        if (!$this->response->isSent()) {
            $this->response->send();
        }
    }

    /**
     * Compilation of Layout Designer code
     * System method used by platform developers
     */
    public function compileAction()
    {
        if (!$this->designerConfig->get('development')) {
            die('Use development mode');
        }

        $s = '';
        $totalSize = 0;
        foreach (self::$scripts as $filePath) {
            $s .= file_get_contents($this->appConfig->get('wwwPath') . $filePath) . "\n";
            $totalSize += filesize($this->appConfig->get('wwwPath') . $filePath);
        }

        $time = microtime(true);
        file_put_contents($this->appConfig->get('wwwPath') . $this->designerConfig->get('compiled_js'),
            \Dvelum\App\Code\Minify\Minify::factory()->minifyJs($s));

        echo '
            Compilation time: ' . number_format(microtime(true) - $time, 5) . ' sec<br>
            Files compiled: ' . sizeof(self::$scripts) . ' <br>
            Total size: ' . Utils::formatFileSize($totalSize) . '<br>
            Compiled File size: ' . Utils::formatFileSize(filesize($this->appConfig->get('wwwPath') . $this->designerConfig->get('compiled_js'))) . ' <br>
        ';
        $this->response->send();
    }

    public function debuggerAction()
    {
        $subAction = $this->request->getPart(3);
        if (!$subAction) {
            $subAction = 'index';
        }

        $subAction .= 'Action';
        $subController = '\\Dvelum\\App\\Backend\\Designer\\Debugger';

        if (!method_exists($subController, $subAction)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            return;
        }

        $sub = new $subController($this->request, $this->response);
        $sub->$subAction();

        $this->response->send();
    }
}