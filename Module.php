<?php

namespace MBtecZfEmail;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use MBtec\Email\Service\Email;
use MBtec\Email\Service\Renderer;
use MBtec\Email\Service\Transport;

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
     * Return MBtec\Email autoload config.
     *
     * @see AutoloaderProviderInterface::getAutoloaderConfig()
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
     * Return the MBtec\Email module config.
     *
     * @see ConfigProviderInterface::getConfig()
     * @return array
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
                'mbtec.email.email.service' => function ($sm) {
                    $rendererService = $sm->get('mbtec.email.renderer.service');
                    $transportService = $sm->get('mbtec.email.transport.service');

                    return new Email($rendererService, $transportService);
                },
                'mbtec.email.renderer.service' => function ($sm) {
                    $config = $sm->get('config');
                    $viewRenderer = $sm->get('ViewRenderer');

                    return new Renderer($config['mbtec']['email']['renderer'], $viewRenderer);
                },
                'mbtec.email.transport.service' => function ($sm) {
                    $config = $sm->get('config');

                    return new Transport($config['mbtec']['email']['transport']);
                },
            ],
        ];
    }
}
