<?php

namespace MBtec\Email\Service;

use Exception;
use Zend\Mail\Message;
use Zend\Mail\Transport as ZendTransport;
use Zend\Log\Logger;
use MBtec\Log\LogService;

/**
 * Class        Transport
 * @package     MBtec\Email\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class Transport
{
    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->_transport = $this->_getTransport($config);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function send(Message $message)
    {
        $this->_transport->send($message);
    }

    /**
     * @return mixed
     */
    public function getTransport()
    {
        return $this->_transport;
    }

    /**
     * @param array $config
     * @return mixed
     */
    protected static function _getTransport(array $config)
    {
        $type = '\Zend\Mail\Transport\\' . ucfirst($config['type']);
        $transport = new $type();

        if (isset($config['options'])) {
            $optionsClass = $type . 'Options';
            if (class_exists($optionsClass) && method_exists($transport, 'setOptions')) {
                $transport->setOptions(
                    new $optionsClass($config['options'])
                );
            }
        }

        return $transport;
    }
}