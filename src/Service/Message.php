<?php

namespace MBtecZfEmail\Service;

use Zend\Mail;
use Zend\Mime;

/**
 * Class        Message
 * @package     MBtecZfEmail\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class Message
{
    protected $aRendererConfig = [];

    /**
     * Message constructor.
     *
     * @param array $aRendererConfig
     */
    public function __construct(array $aRendererConfig)
    {
        $this->aRendererConfig = $aRendererConfig;
    }


    /**
     * @param array $aMailData
     * @param array $aOpts
     * @param array $aRecs
     * @param array $aAttachments
     *
     * @return Mail\Message
     */
    public function createMessage(array $aMailData, array $aOpts, array $aRecs, array $aAttachments = [])
    {
        $oMessage = new Mail\Message();
        $oMessage->setEncoding('UTF-8');

        $this
            ->setFrom($oMessage, $aOpts)
            ->setTo($oMessage, $aRecs)
            ->addBcc($oMessage)
            ->setSubject($oMessage, $aMailData['subject'])
            ->setBody($oMessage, $aAttachments, $aMailData['html'], $aMailData['plain']);

        return $oMessage;
    }

    /**
     * @param Mail\Message $oMessage
     * @param array        $aOptions
     *
     * @return $this
     */
    protected function setFrom(Mail\Message $oMessage, array $aOptions)
    {
        $aSenderData = $this->getSenderData($aOptions);

        $oAddressList = new Mail\AddressList();
        $oAddressList->add(
            new Mail\Address($aSenderData['email'], (isset($aSenderData['name']) ? $aSenderData['name'] : null))
        );

        $oMessage->setFrom($oAddressList);

        return $this;
    }

    /**
     * @param array $aOptions
     *
     * @return array
     */
    protected function getSenderData(array $aOptions)
    {
        if (isset($aOptions['use_default_sender']) && $aOptions['use_default_sender']) {
            $sFromEmail = isset($this->aRendererConfig['mail_from_email'])
                ? $this->aRendererConfig['mail_from_email']
                : null;

            $sFromName = isset($this->aRendererConfig['mail_from_name'])
                ? $this->aRendererConfig['mail_from_name']
                : null;

            return [
                'email' => $sFromEmail,
                'name' => $sFromName,
            ];
        }

        $sFromEmail = isset($aOptions['sender_mail'])
            ? $aOptions['sender_mail']
            : null;

        $sFromName = isset($aOptions['sender_name'])
            ? $aOptions['sender_name']
            : null;

        return [
            'email' => $sFromEmail,
            'name' => $sFromName,
        ];
    }

    /**
     * @param Mail\Message $oMessage
     * @param array        $aReceivers
     *
     * @return $this
     */
    protected function setTo(Mail\Message $oMessage, array $aReceivers)
    {
        $oAddressList = new Mail\AddressList();

        foreach ($aReceivers as $aReceiver) {
            $sRecEmail = isset($this->aRendererConfig['email_receiver_override'])
                ? $this->aRendererConfig['email_receiver_override']
                : $aReceiver[0];

            $sRecName = isset($aReceiver[1])
                ? $aReceiver[1]
                : null;

            $oAddressList->add(
                new Mail\Address($sRecEmail, $sRecName)
            );
        }

        $oMessage->setTo($oAddressList);

        return $this;
    }

    /**
     * @param Mail\Message $oMessage
     *
     * @return $this
     */
    protected function addBcc(Mail\Message $oMessage)
    {
        if (isset($this->aRendererConfig['mail_bcc']) && is_array($this->aRendererConfig['mail_bcc'])) {
            foreach ($this->aRendererConfig['mail_bcc'] as $sBccAddress) {
                // Avoid sending email copy to "to" AND bcc
                $bFound = false;
                foreach ($oMessage->getTo() as $oTo) {
                    if ($oTo->getEmail() == $sBccAddress) {
                        $bFound = true;
                        break;
                    }
                }
                if (!$bFound) {
                    $oAddressListBcc = new Mail\AddressList();
                    $oAddressListBcc->add(
                        new Mail\Address($sBccAddress)
                    );
                    $oMessage->addBcc($oAddressListBcc);
                }
            }
        }

        return $this;
    }

    /**
     * @param Mail\Message $oMessage
     * @param              $sSubject
     *
     * @return $this
     */
    protected function setSubject(Mail\Message $oMessage, $sSubject)
    {
        if (isset($this->aRendererConfig['subject_prefix']) && $this->aRendererConfig['subject_prefix'] != '') {
            $sSubject = $this->aRendererConfig['subject_prefix'] . $sSubject;
        }

        $oMessage->setSubject($sSubject);

        return $this;
    }

    /**
     * @param Mail\Message $oMessage
     * @param array        $aAttachments
     * @param              $sHtml
     * @param              $sPlain
     *
     * @return $this
     */
    protected function setBody(Mail\Message $oMessage, array $aAttachments, $sHtml, $sPlain)
    {
        if (empty($aAttachments)) {
            $this->setBodyWithoutAttachments($oMessage, $sHtml, $sPlain);

            return $this;
        }

        $this->setBodyWithAttachments($oMessage, $aAttachments, $sHtml, $sPlain);

        return $this;
    }

    /**
     * @param Mail\Message $oMessage
     * @param              $sHtml
     * @param              $sPlain
     *
     * @return $this
     */
    protected function setBodyWithoutAttachments(Mail\Message $oMessage, $sHtml, $sPlain)
    {
        $oContent = $this->getContentMessage($sHtml, $sPlain);
        $oMessage->setBody($oContent);

        $oHeaders = $oMessage->getHeaders();

        if (!$oHeaders->has('Content-Type')) {
            $oHeader = new Mail\Header\ContentType();
            $oHeaders->addHeader($oHeader);
        }

        $oHeaders->get('Content-Type')->setType(Mime\Mime::MULTIPART_ALTERNATIVE);

        return $this;
    }

    /**
     * @param Mail\Message $oMessage
     * @param array        $aAttachments
     * @param              $sHtml
     * @param              $sPlain
     *
     * @return $this
     */
    protected function setBodyWithAttachments(Mail\Message $oMessage, array $aAttachments, $sHtml, $sPlain)
    {
        $oContent = $this->getContentMessage($sHtml, $sPlain);

        $oContentPart = new Mime\Part($oContent->generateMessage());
        $oContentPart->setType(Mime\Mime::MULTIPART_ALTERNATIVE);
        $oContentPart->setBoundary($oContent->getMime()->boundary());

        $oBody = new Mime\Message();
        $oBody->addPart($oContentPart);

        foreach ($aAttachments as $aAttachmentData) {
            if (isset($aAttachmentData['file_path']) && is_readable($aAttachmentData['file_path'])) {
                $aAttachmentData['file_data'] = file_get_contents($aAttachmentData['file_path']);
            }

            if (isset($aAttachmentData['file_data']) && $aAttachmentData['file_data'] != '') {
                $oAttachment = new Mime\Part();
                $oAttachment
                    ->setContent($aAttachmentData['file_data'])
                    ->setType(Mime\Mime::TYPE_OCTETSTREAM)
                    ->setFileName($aAttachmentData['name'])
                    ->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT)
                    ->setEncoding(Mime\Mime::ENCODING_BASE64);

                $oBody->addPart($oAttachment);
            }
        }

        $oMessage->setBody($oBody);

        return $this;
    }

    /**
     * @param $sHtml
     * @param $sPlain
     *
     * @return Mime\Message
     */
    protected function getContentMessage($sHtml, $sPlain)
    {
        $oMessage = new Mime\Message();

        if ($sPlain != '') {
            $oPlain = new Mime\Part();
            $oPlain
                ->setContent($sPlain)
                ->setType(Mime\Mime::TYPE_TEXT)
                ->setCharset('UTF-8')
                ->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);

            $oMessage->addPart($oPlain);
        }

        if ($sHtml != '') {
            $oHtml = new Mime\Part();
            $oHtml
                ->setContent($sHtml)
                ->setType(Mime\Mime::TYPE_HTML)
                ->setCharset('UTF-8')
                ->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);

            $oMessage->addPart($oHtml);
        }

        return $oMessage;
    }
}
