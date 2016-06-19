<?php

namespace MBtec\Email\Service;

/**
 * Class        Email
 * @package     MBtec\Email\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class Email
{
    protected $_transport = null;
    protected $_renderer = null;
    protected $_receivers = [];
    protected $_attachmentFiles = [];

    protected $_tpl = null;
    protected $_variables = [];
    protected $_options = [];

    /**
     * @param Renderer $renderer
     * @param Transport $transport
     */
    public function __construct(Renderer $renderer, Transport $transport)
    {
        $this->_renderer = $renderer;
        $this->_transport = $transport;
    }

    /**
     * @param $email
     * @param null $name
     * @return $this
     */
    public function addReceiver($email, $name = null)
    {
        $receiver = array($email);
        if (is_string($name)) {
            $receiver[] = $name;
        }

        $this->_receivers[] = $receiver;

        return $this;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setDefaultSender($bool)
    {
        $this->_options['use_default_sender'] = (bool) $bool;

        return $this;
    }

    /**
     * @param $senderMail
     * @param null $senderName
     * @return $this
     */
    public function setSender($senderMail, $senderName = null)
    {
        $this->_options['sender_mail'] = (string) $senderMail;

        if ($senderName) {
            $this->_options['sender_name'] = (string) $senderName;
        }

        return $this;
    }

    /**
     * @param $tpl
     * @return $this
     */
    public function setTemplate($tpl)
    {
        $this->_tpl = $tpl;

        return $this;
    }

    /**
     * @return $this
     */
    public function addDefaultFooter()
    {
        $this->setOption('add_footer', true);

        return $this;
    }

    /**
     * @param $var
     * @param $val
     * @return $this
     */
    public function setVariable($var, $val)
    {
        $this->_variables[$var] = $val;

        return $this;
    }

    /**
     * @param $var
     * @param $val
     * @return $this
     */
    public function setOption($var, $val)
    {
        $this->_options[$var] = $val;

        return $this;
    }

    /**
     * @param $fileName
     * @param $filePath
     * @param $mime
     * @return $this
     */
    public function addAttachmentFile($fileName, $filePath, $mime)
    {
        $this->_attachmentFiles[] = [
            'file_path' => $filePath,
            'name' => $fileName,
            'mime_type' => $mime,
        ];

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function send()
    {
        $this->_transport->send(
            $this->_getMessage()
        );

        $this->reset();

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->_tpl = null;
        $this->_variables = [];
        $this->_options = [];
        $this->_receivers = [];
        $this->_attachmentFiles = [];

        return $this;
    }

    /**
     * @return \Zend\Mail\Message
     */
    protected function _getMessage()
    {
        return $this->_renderer->renderTemplate(
            $this->_tpl, $this->_variables, $this->_options, $this->_receivers, $this->_attachmentFiles
        );
    }
}