<?php

namespace MBtecZfEmail;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use MBtecZfEmail\Service\Email;
use MBtecZfEmail\Service\Renderer;
use MBtecZfEmail\Service\Transport;

/**
 * Class        Module
 * @package     MBtecZfEmail
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class Module implements AutoloaderProviderInterface, ConfigProviderInterface, ServiceProviderInterface
{
    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
        ];
    }

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
                'mbtec.zfemail.email.service' => function ($sm) {
                    $rendererService = $sm->get('mbtec.zfemail.renderer.service');
                    $transportService = $sm->get('mbtec.zfemail.transport.service');

                    return new Email($rendererService, $transportService);
                },
                'mbtec.zfemail.renderer.service' => function ($sm) {
                    $config = $sm->get('config');
                    $viewRenderer = $sm->get('ViewRenderer');

                    return new Renderer($config['mbtec']['zfemail']['renderer'], $viewRenderer);
                },
                'mbtec.zfemail.transport.service' => function ($sm) {
                    $config = $sm->get('config');

                    return new Transport($config['mbtec']['zfemail']['transport']);
                },
            ],
        ];
    }
}
