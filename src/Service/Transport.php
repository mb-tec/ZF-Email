<?php

namespace MBtecZfEmail\Service;

use Zend\Mail\Message;

/**
 * Class        Transport
 * @package     MBtecZfEmail\Service
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