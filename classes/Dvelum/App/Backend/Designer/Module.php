<?php
namespace Dvelum\App\Backend\Designer;
use Dvelum\Config;
use Dvelum\Designer\Project;
use Dvelum\Designer\Storage;
use Dvelum\Lang;
use Dvelum\Request;
use Dvelum\Response;
use Dvelum\Store\Factory;
use Dvelum\Store\Session;

abstract class Module
{
    /**
     * @var Lang\Dictionary
     */
    protected $lang;
    /**
     * @var Dvelum\Db\Adapter
     */
    protected $db;
    /**
     * Designer config
     * @var Config\ConfigInterface
     */
    protected $designerConfig;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var \Designer_Storage_Adapter_Abstract
     */
    protected $storage;
    /**
     * @var Config\ConfigInterface
     */
    protected $appConfig;

    protected $project = null;
    /**
     * @var \Page
     */
    protected $page;
    /**
     * @var Request $request
     */
    protected $request;
    /**
     * @var Response $response
     */
    protected $response;

    /**
     * Module constructor.
     * @param Request $request
     * @param Response $response
     * @throws \Exception
     */
    public function __construct(Request $request, Response $response)
    {
        $this->appConfig = Config::storage()->get('main.php');
        $this->lang = Lang::lang();
        $this->request = $request;
        $this->response = $response;
        /**
         * @var \Dvelum\Orm\Service $service
         */
        $service = \Dvelum\Service::get('orm');
        $this->db = $service->getModelSettings()->get('defaultDbManager')->getDbConnection('default');

        $this->designerConfig = Config::storage()->get('designer.php');
        $this->session = Factory::get(Factory::SESSION, 'Designer');
        $this->storage = Storage::factory($this->designerConfig->get('storage'), $this->designerConfig);

        $this->page = \Page::getInstance();
        $this->response = \Dvelum\Response::factory();
    }

    /**
     * Check if project is loaded
     */
    protected function checkLoaded() : bool
    {
        if (!$this->session->keyExists('loaded') || !$this->session->get('loaded')) {
            $this->response->error($this->lang->get('MSG_PROJECT_NOT_LOADED'));
            return false;
        }
        return true;
    }

    /**
     * Get project object from
     * session storage
     * @return Project
     */
    protected function getProject() : Project
    {
        if (is_null($this->project)) {
            $this->project = unserialize($this->session->get('project'));
        }
        return $this->project;
    }

    /**
     * Store project data
     */
    protected function storeProject()
    {
        $this->session->set('project', serialize($this->getProject()));
    }

    /**
     * Check requested object
     * Get requested object from project
     * @return \Ext_Object
     */
    protected function getObject()
    {
        $name = $this->request->post('object', 'string', '');
        $project = $this->getProject();

        if (!strlen($name) || !$project->objectExists($name)) {
            $this->response->error($this->lang->get('WRONG_REQUEST'));
            $this->response->send();
            exit();
        }

        return $project->getObject($name);
    }
}