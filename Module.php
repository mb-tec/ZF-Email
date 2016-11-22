<?php

namespace MBtecZfEmail;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;

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
                'mbtec.zf-email.email.service' => function ($sm) {
                    $rendererService = $sm->get('mbtec.zf-email.renderer.service');
                    $transportService = $sm->get('mbtec.zf-email.transport.service');

                    return new Service\Email($rendererService, $transportService);
                },
                'mbtec.zf-email.renderer.service' => function ($sm) {
                    $config = $sm->get('config')['mbtec']['zf-email']['renderer'];
                    $viewRenderer = $sm->get('ViewRenderer');

                    return new Service\Renderer($config, $viewRenderer);
                },
                'mbtec.zf-email.transport.service' => function ($sm) {
                    $config = $sm->get('config')['mbtec']['zf-email']['transport'];

                    return new Service\Transport($config);
                },
            ],
        ];
    }
}
