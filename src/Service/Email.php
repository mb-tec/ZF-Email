<?php

namespace MBtecZfEmail\Service;

/**
 * Class        Email
 * @package     MBtecZfEmail\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GPL-2.0
 * @link        http://mb-tec.eu
 */
class Email
{
    protected $oTransport = null;
    protected $oMessage = null;
    protected $oRenderer = null;
    protected $aReceivers = [];
    protected $aAttachments = [];

    protected $sTpl = null;
    protected $aVariables = [];
    protected $aOptions = [
        'use_default_sender' => true,
        'add_footer' => true,
    ];

    /**
     * Email constructor.
     *
     * @param Renderer  $oRenderer
     * @param Message   $oMessage
     * @param Transport $oTransport
     */
    public function __construct(Renderer $oRenderer, Message $oMessage, Transport $oTransport)
    {
        $this->oRenderer = $oRenderer;
        $this->oMessage = $oMessage;
        $this->oTransport = $oTransport;
    }

    /**
     * @param      $sEmail
     * @param null $sName
     *
     * @return $this
     */
    public function addReceiver($sEmail, $sName = null)
    {
        $aRceiver = [$sEmail];
        if (is_string($sName)) {
            $aRceiver[] = $sName;
        }

        $this->aReceivers[] = $aRceiver;

        return $this;
    }

    /**
     * @param $bBool
     *
     * @return $this
     */
    public function setDefaultSender($bBool)
    {
        $this->aOptions['use_default_sender'] = (bool)$bBool;

        return $this;
    }

    /**
     * @param      $sSenderMail
     * @param null $sSenderName
     *
     * @return $this
     */
    public function setSender($sSenderMail, $sSenderName = null)
    {
        $this->aOptions['sender_mail'] = (string)$sSenderMail;

        if ($sSenderName) {
            $this->aOptions['sender_name'] = (string)$sSenderName;
        }

        return $this;
    }

    /**
     * @param $sTpl
     *
     * @return $this
     */
    public function setTemplate($sTpl)
    {
        $this->sTpl = $sTpl;

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
     * @param $mVar
     * @param $mVal
     *
     * @return $this
     */
    public function setVariable($mVar, $mVal)
    {
        $this->aVariables[$mVar] = $mVal;

        return $this;
    }

    /**
     * @param $mVar
     * @param $mVal
     *
     * @return $this
     */
    public function setOption($mVar, $mVal)
    {
        $this->aOptions[$mVar] = $mVal;

        return $this;
    }

    /**
     * @param $sFileName
     * @param $fFilePath
     *
     * @return $this
     */
    public function addAttachmentFile($sFileName, $fFilePath)
    {
        $this->aAttachments[] = [
            'file_path' => $fFilePath,
            'name' => $sFileName,
        ];

        return $this;
    }

    /**
     * @param $sFileName
     * @param $sFileData
     *
     * @return $this
     */
    public function addAttachmentData($sFileName, $sFileData)
    {
        $this->aAttachments[] = [
            'file_data' => $sFileData,
            'name' => $sFileName,
        ];

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function send()
    {
        $aMailData = $this->oRenderer->renderTemplate($this->sTpl, $this->aVariables, $this->aOptions);
        $oMessage = $this->oMessage->createMessage($aMailData, $this->aOptions, $this->aReceivers, $this->aAttachments);

        $this->oTransport->send($oMessage);

        $this->reset();

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->sTpl = null;
        $this->aVariables = [];
        $this->aOptions = [
            'use_default_sender' => true,
            'add_footer' => true,
        ];
        $this->aReceivers = [];
        $this->aAttachments = [];

        return $this;
    }
}
