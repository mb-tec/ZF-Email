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
 * @license     GPL-2.0
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
                    $oRenderer = $oSm->get('mbtec.zf-email.renderer.service');
                    $oTransport = $oSm->get('mbtec.zf-email.transport.service');

                    return new Service\Email($oRenderer, $oTransport);
                },
                'mbtec.zf-email.renderer.service' => function (ServiceManager $oSm) {
                    $aConfig = (array)$oSm->get('config')['mbtec']['zf-email']['renderer'];
                    $oViewRenderer = $oSm->get('ViewRenderer');

                    return new Service\Renderer($aConfig, $oViewRenderer);
                },
                'mbtec.zf-email.transport.service' => function (ServiceManager $oSm) {
                    $aConfig = (array)$oSm->get('config')['mbtec']['zf-email']['transport'];

                    return new Service\Transport($aConfig);
                },
            ],
        ];
    }
}
