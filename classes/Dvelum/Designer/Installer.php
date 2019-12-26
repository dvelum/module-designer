<?php
namespace Dvelum\Designer;

use Dvelum\App\Model\Permissions;
use Dvelum\Config\ConfigInterface;
use Dvelum\App\Session\User;
use Dvelum\Orm\Model;

class Installer extends \Dvelum\Externals\Installer
{
    /**
     * Install
     * @param ConfigInterface $applicationConfig
     * @param ConfigInterface $moduleConfig
     * @return bool
     * @throws \Exception
     */
    public function install(ConfigInterface $applicationConfig, ConfigInterface $moduleConfig) : bool
    {
        // Add permissions
        $userInfo = User::factory()->getInfo();
        /**
         * @var Permissions $permissionsModel
         */
        $permissionsModel = Model::factory('Permissions');
        if (!$permissionsModel->setGroupPermissions($userInfo['group_id'], 'Designer', 1, 1, 1, 1)) {
            return false;
        }
        return true;
    }

    /**
     * Uninstall
     * @param ConfigInterface $applicationConfig
     * @param ConfigInterface $moduleConfig
     * @return bool
     */
    public function uninstall(ConfigInterface $applicationConfig, ConfigInterface $moduleConfig) : bool
    {

    }
}