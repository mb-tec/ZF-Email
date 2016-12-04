<?php

namespace MBtecZfEmail;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class        Module
 * @package     MBtecZfEmail
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class Module implements ConfigProviderInterface, ServiceProviderInterface
{
    /**
     * @return mixed
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'mbtec.zf-email.email.service' => function (ServiceManager $oSm) {
                    $rendererService = $oSm->get('mbtec.zf-email.renderer.service');
                    $transportService = $oSm->get('mbtec.zf-email.transport.service');

                    return new Service\Email($rendererService, $transportService);
                },
                'mbtec.zf-email.renderer.service' => function (ServiceManager $oSm) {
                    $config = $oSm->get('config')['mbtec']['zf-email']['renderer'];
                    $viewRenderer = $oSm->get('ViewRenderer');

                    return new Service\Renderer($config, $viewRenderer);
                },
                'mbtec.zf-email.transport.service' => function (ServiceManager $oSm) {
                    $config = $oSm->get('config')['mbtec']['zf-email']['transport'];

                    return new Service\Transport($config);
                },
            ],
        ];
    }
}
