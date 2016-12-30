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
                    $oMessage = $oSm->get('mbtec.zf-email.message.service');
                    $oTransport = $oSm->get('mbtec.zf-email.transport.service');

                    return new Service\Email($oRenderer, $oMessage, $oTransport);
                },
                'mbtec.zf-email.message.service' => function (ServiceManager $oSm) {
                    $aConfig = (array)$oSm->get('config')['mbtec']['zf-email']['renderer'];

                    return new Service\Message($aConfig);
                },
                'mbtec.zf-email.renderer.service' => function (ServiceManager $oSm) {
                    $oViewRenderer = $oSm->get('ViewRenderer');
                    $aConfig = (array)$oSm->get('config')['mbtec']['zf-email']['renderer'];

                    return new Service\Renderer($oViewRenderer, $aConfig);
                },
                'mbtec.zf-email.transport.service' => function (ServiceManager $oSm) {
                    $aConfig = (array)$oSm->get('config')['mbtec']['zf-email']['transport'];

                    return new Service\Transport($aConfig);
                },
            ],
        ];
    }
}
