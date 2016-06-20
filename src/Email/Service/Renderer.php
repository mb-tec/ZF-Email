<?php

namespace MBtecZfEmail\Service;

use Zend\Mail;
use Zend\Mime;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;

/**
 * Class        Renderer
 * @package     MBtecZfEmail\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class Renderer
{
    protected $_renderer = null;
    protected $_converter = null;

    protected $_rendererConfig = null;

    /**
     * @param array $rendererConfig
     * @param PhpRenderer $renderer
     */
    public function __construct(array $rendererConfig, PhpRenderer $renderer)
    {
        $this->_rendererConfig = $rendererConfig;
        $this->_renderer = $renderer;
    }

    /**
     * @param       $subject
     * @param       $html
     * @param       $plain
     * @param array $options
     * @param array $receivers
     * @param array $attachments
     *
     * @return Mail\Message
     */
    protected function _assembleMailMessage(
        $subject, $html, $plain, array $options, array $receivers, array $attachments = []
    )
    {
        $config = $this->_rendererConfig;

        $message = new Mail\Message();

        $message->setEncoding('UTF-8');

        // Sender
        if (isset($options['use_default_sender']) && $options['use_default_sender'] === true) {
            $fromEmail = isset($config['mail_from_email'])
                ? $config['mail_from_email']
                : null;

            $fromName = isset($config['mail_from_name'])
                ? $config['mail_from_name']
                : null;
        } else {
            $fromEmail = isset($options['sender_mail'])
                ? $options['sender_mail']
                : null;

            $fromName = isset($options['sender_name'])
                ? $options['sender_name']
                : null;
        }

        $addressListFrom = new Mail\AddressList();
        $addressListFrom->add(
            new Mail\Address($fromEmail, (isset($fromName) ? $fromName : null))
        );
        $message->setFrom($addressListFrom);

        // Receiver
        $addressListTo = new Mail\AddressList();
        foreach ($receivers as $receiver) {
            $recEmail = isset($config['email_receiver_override'])
                ? $config['email_receiver_override']
                : $receiver[0];

            $recName = isset($receiver[1])
                ? $receiver[1]
                : null;

            $addressListTo->add(
                new Mail\Address($recEmail, $recName)
            );
        }
        $message->setTo($addressListTo);

        if (isset($config['mail_bcc']) && is_array($config['mail_bcc'])) {
            foreach ($config['mail_bcc'] as $bccAddress) {
                // Avoid sending email copy to "to" AND bcc
                $found = false;
                foreach ($message->getTo() as $to) {
                    if ($to->getEmail() == $bccAddress) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $addressListBcc = new Mail\AddressList();
                    $addressListBcc->add(
                        new Mail\Address($bccAddress)
                    );
                    $message->addBcc($addressListBcc);
                }
            }
        }

        if (isset($config['subject_prefix']) && $config['subject_prefix'] != '') {
            $subject = $config['subject_prefix'] . $subject;
        }

        $message->setSubject($subject);

        if (empty($attachments)) {
            $message->setBody($html);

            $contentType = Mail\Header\ContentType::fromString('Content-Type: text/html; charset=utf-8');
            $message->getHeaders()->addHeader($contentType);

            $contentTransferEncoding = Mail\Header\ContentTransferEncoding::fromString('Content-Transfer-Encoding: 8bit');
            $message->getHeaders()->addHeader($contentTransferEncoding);
        } else {
            $html = new Mime\Part($html);
            $html->type = Mime\Mime::TYPE_HTML;
            $html->charset = 'UTF-8';
            $html->disposition = Mime\Mime::DISPOSITION_INLINE;
            $html->encoding = Mime\Mime::ENCODING_QUOTEDPRINTABLE;

            $body = new Mime\Message();
            $body->addPart($html);

            foreach ($attachments as $attachmentData) {
                if (isset($attachmentData['file_path']) && is_readable($attachmentData['file_path'])) {
                    $attachmentData['file_data'] = file_get_contents($attachmentData['file_path']);
                }

                if (isset($attachmentData['file_data']) && !empty($attachmentData['file_data'])) {
                    $attachment = new Mime\Part($attachmentData['file_data']);
                    $attachment->type = $attachmentData['mime_type'];
                    $attachment->filename = $attachmentData['name'];
                    $attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
                    $attachment->encoding = Mime\Mime::ENCODING_BASE64;

                    $body->addPart($attachment);
                }
            }

            $message->setBody($body);
        }

        return $message;
    }

    /**
     * @param $tpl
     * @param array $variables
     * @param array $options
     * @param array $receivers
     * @param array $attachmentFiles
     * @return Mail\Message
     */
    public function renderTemplate(
        $tpl, array $variables, array $options, array $receivers, array $attachmentFiles = []
    )
    {
        list($subject, $html, $plain) = $this->_getTemplateData($tpl, $variables);

        // Add Footer
        if (isset($options['add_footer']) && $options['add_footer']) {
            $templateNameData = explode('/', $tpl);
            $footerTpl = sprintf('%s/email/_footer', $templateNameData[0]);

            list($footerSubject, $footerHtml, $footerPlain) = $this->_getTemplateData($footerTpl, $variables);

            $html .= $footerHtml;
            $plain .= $footerPlain;
        }

        return $this->_assembleMailMessage($subject, $html, $plain, $options, $receivers, $attachmentFiles);
    }

    /**
     * Render a given template with given data assigned.
     *
     * @param  string $tpl
     * @param  array $data
     * @return string The rendered content.
     */
    protected function _renderMail($tpl, array $data = [])
    {
        $viewModel = new ViewModel($data);
        $viewModel->setTemplate($tpl);

        return $this->_renderer->render($viewModel);
    }

    /**
     * @param $tpl
     * @param array $variables
     * @return array
     */
    protected function _getTemplateData($tpl, array $variables = [])
    {
        $tplData = explode('######', $this->_renderMail($tpl, $variables));
        $tplData = array_map('trim', $tplData);

        foreach ($tplData as $row) {
            $parts = array_map('trim', explode(':', $row, 2));

            switch (strtolower($parts[0])) {
                case 'subject':
                    $subject = $parts[1];
                    break;
                case 'html':
                    $html = $parts[1];
                    break;
                case 'plain':
                    $plain = $parts[1];
                    break;
                default:
            }
        }

        if (!isset($subject)) {
            $subject = 'Missing subject';
        }

        if (!isset($html)) {
            $html = 'Missing html content';
        }

        if (!isset($plain)) {
            $plain = $this->_getConverter()->convert($html);
        }

        return array(
            $subject, $html, $plain
        );
    }

    /**
     * @return HtmlConverter
     */
    protected function _getConverter()
    {
        if ($this->_converter === null) {
            $this->_converter = new HtmlConverter();
        }

        return $this->_converter->reset();
    }
}