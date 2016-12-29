<?php

namespace MBtecZfEmail\Service;

use Zend\Mail;
use Zend\Mime;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Html2Text\Html2Text;

/**
 * Class        Renderer
 * @package     MBtecZfEmail\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GPL-2.0
 * @link        http://mb-tec.eu
 */
class Renderer
{
    protected $oRenderer = null;
    protected $oConverter = null;
    protected $oMessage = null;
    protected $oHtml2Text = null;

    protected $aRendererConfig = [];

    /**
     * Renderer constructor.
     *
     * @param array       $aRendererConfig
     * @param PhpRenderer $oRenderer
     */
    public function __construct(array $aRendererConfig, PhpRenderer $oRenderer)
    {
        $this->aRendererConfig = $aRendererConfig;
        $this->oRenderer = $oRenderer;
    }

    /**
     * @param array $aMailData
     * @param array $aOptions
     * @param array $aReceivers
     * @param array $aAtts
     *
     * @return Mail\Message
     */
    protected function assembleMailMessage(array $aMailData, array $aOptions, array $aReceivers, array $aAtts = [])
    {
        $aConfig = $this->aRendererConfig;

        $oMessage = new Mail\Message();
        $oMessage->setEncoding('UTF-8');

        $this
            ->setFrom($oMessage, $aConfig, $aOptions)
            ->setTo($oMessage, $aReceivers, $aConfig)
            ->addBcc($oMessage, $aConfig)
            ->setSubject($oMessage, $aMailData['subject'], $aConfig)
            ->setBody($oMessage, $aAtts, $aMailData['html'], $aMailData['plain']);

        return $oMessage;
    }

    /**
     * @param Mail\Message $oMessage
     * @param array        $aConfig
     * @param array        $aOptions
     *
     * @return $this
     */
    protected function setFrom(Mail\Message $oMessage, array $aConfig, array $aOptions)
    {
        $aSenderData = $this->getSenderData($aConfig, $aOptions);

        $oAddressList = new Mail\AddressList();
        $oAddressList->add(
            new Mail\Address($aSenderData['email'], (isset($aSenderData['name']) ? $aSenderData['name'] : null))
        );

        $oMessage->setFrom($oAddressList);

        return $this;
    }

    /**
     * @param array $aConfig
     * @param array $aOptions
     *
     * @return array
     */
    protected function getSenderData(array $aConfig, array $aOptions)
    {
        if (isset($aOptions['use_default_sender']) && $aOptions['use_default_sender']) {
            $sFromEmail = isset($aConfig['mail_from_email'])
                ? $aConfig['mail_from_email']
                : null;

            $sFromName = isset($aConfig['mail_from_name'])
                ? $aConfig['mail_from_name']
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
     * @param array        $aConfig
     *
     * @return $this
     */
    protected function setTo(Mail\Message $oMessage, array $aReceivers, array $aConfig)
    {
        $oAddressList = new Mail\AddressList();

        foreach ($aReceivers as $aReceiver) {
            $sRecEmail = isset($aConfig['email_receiver_override'])
                ? $aConfig['email_receiver_override']
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
     * @param array        $aConfig
     *
     * @return $this
     */
    protected function addBcc(Mail\Message $oMessage, array $aConfig)
    {
        if (isset($aConfig['mail_bcc']) && is_array($aConfig['mail_bcc'])) {
            foreach ($aConfig['mail_bcc'] as $sBccAddress) {
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
     * @param array        $aConfig
     *
     * @return $this
     */
    protected function setSubject(Mail\Message $oMessage, $sSubject, array $aConfig)
    {
        if (isset($aConfig['subject_prefix']) && $aConfig['subject_prefix'] != '') {
            $sSubject = $aConfig['subject_prefix'] . $sSubject;
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

    /**
     * @param       $sTpl
     * @param array $aVars
     * @param array $aOpts
     * @param array $aRecs
     * @param array $aAtts
     *
     * @return Mail\Message
     */
    public function renderTemplate($sTpl, array $aVars, array $aOpts, array $aRecs, array $aAtts = [])
    {
        $aMailData = $this->getMailData($sTpl, $aVars);

        // Add Footer
        if (isset($aOpts['add_footer']) && $aOpts['add_footer']) {
            $templateNameData = explode('/', $sTpl);
            $sFooterTpl = sprintf('%s/email/_footer', $templateNameData[0]);

            $aFooterData = $this->getMailData($sFooterTpl, $aVars);

            $aMailData['html'] .= $aFooterData['html'];
            $aMailData['plain'] .= $aFooterData['plain'];
        }

        return $this->assembleMailMessage($aMailData, $aOpts, $aRecs, $aAtts);
    }

    /**
     * @param       $sTpl
     * @param array $aData
     *
     * @return string
     */
    protected function renderMail($sTpl, array $aData = [])
    {
        $oViewModel = new ViewModel($aData);
        $oViewModel->setTemplate($sTpl);

        return $this->oRenderer->render($oViewModel);
    }

    /**
     * @param       $sTpl
     * @param array $aVariables
     *
     * @return array
     */
    protected function getMailData($sTpl, array $aVariables = [])
    {
        $sSubject = $sHtml = $sPlain = null;

        $aTplData = explode('######', $this->renderMail($sTpl, $aVariables));
        $aTplData = array_map('trim', $aTplData);

        foreach ($aTplData as $sRow) {
            $aParts = array_map('trim', explode(':', $sRow, 2));

            switch (strtolower($aParts[0])) {
                case 'subject':
                    $sSubject = $aParts[1];
                    break;

                case 'html':
                    $sHtml = $aParts[1];
                    break;

                case 'plain':
                    $sPlain = $aParts[1];
                    break;

                default:
            }
        }

        if ($sSubject === null) {
            $sSubject = 'Missing subject';
        }

        if ($sHtml === null) {
            $sHtml = 'Missing html content';
        }

        if ($sPlain === null) {
            $oHtmlConverter = $this->getHtml2Text();
            $oHtmlConverter->setHtml($sHtml);
            $sPlain = $oHtmlConverter->getText();
        }

        return [
            'subject' => $sSubject,
            'html' => $sHtml,
            'plain' => $sPlain,
        ];
    }

    /**
     * @return Html2Text
     */
    protected function getHtml2Text()
    {
        if (!is_object($this->oHtml2Text)) {
            $this->oHtml2Text = new Html2Text();
        }

        return $this->oHtml2Text;
    }
}
