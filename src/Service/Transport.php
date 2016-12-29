<?php

namespace MBtecZfEmail\Service;

use Zend\Mail\Message;

/**
 * Class        Transport
 * @package     MBtecZfEmail\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GPL-2.0
 * @link        http://mb-tec.eu
 */
class Transport
{
    /** @var \Zend\Mail\Transport\TransportInterface */
    protected $oTransport = null;

    /**
     * Transport constructor.
     *
     * @param array $aConfig
     */
    public function __construct(array $aConfig)
    {
        $this->oTransport = $this->initTransport($aConfig);
    }

    /**
     * @param Message $oMessage
     */
    public function send(Message $oMessage)
    {
        $this->oTransport->send($oMessage);
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function getTransport()
    {
        return $this->oTransport;
    }

    /**
     * @param array $aConfig
     *
     * @return \Zend\Mail\Transport\TransportInterface
     */
    protected static function initTransport(array $aConfig)
    {
        $sType = '\Zend\Mail\Transport\\' . ucfirst($aConfig['type']);
        $oTransport = new $sType();

        if (isset($aConfig['options'])) {
            $sOptionsClass = $sType . 'Options';
            if (class_exists($sOptionsClass) && method_exists($oTransport, 'setOptions')) {
                $oTransport->setOptions(
                    new $sOptionsClass($aConfig['options'])
                );
            }
        }

        return $oTransport;
    }
}
