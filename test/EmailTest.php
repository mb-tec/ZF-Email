<?php

namespace MBtecZfEmail\Test;

use Exception;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;
use MBtecZfEmail\Service\Renderer;
use MBtecZfEmail\Service\Transport;
use MBtecZfEmail\Service\Email;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class        UrlTest
 * @package     MBtecZfFullUrl\Test
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GPL-2.0
 * @link        http://mb-tec.eu
 */
class EmailTest extends TestCase
{
    /**
     *
     */
    protected function setUp()
    {
        if (!isset($GLOBALS['receiver'])) {
            $GLOBALS['receiver'] = 'info@mb-tec.eu';
        }

        if (!isset($GLOBALS['receiver_override'])) {
            $GLOBALS['receiver_override'] = 'nite74@web.de';
        }

        if (!isset($GLOBALS['receiver_bcc'])) {
            $GLOBALS['receiver_bcc'] = null;
        }
    }

    /**
     *
     */
    public function testSimpleMail()
    {
        $aConfig = [
            'mbtec' => [
                'zf-email' => [
                    'transport' => [
                        'type' => 'sendmail',
                    ],
                    'renderer' => [
                        'subject_prefix' => 'Test: ',
                        'mail_from_name' => 'Test',
                        'mail_from_email' => 'info@mb-tec.eu',
                        'mail_bcc' => [
                            $GLOBALS['receiver_bcc'],
                        ],
                    ],
                ],
            ],
        ];

        $bSuccess = false;
        try {
            $this->getEmailService($aConfig)
                ->addReceiver($GLOBALS['receiver'])
                ->setTemplate('test/email/simpleMail')
                ->send();

            $bSuccess = true;
        } catch (Exception $oEx) {
        }

        $this->assertEquals($bSuccess, true);
    }

    /**
     *
     */
    public function testMailOverride()
    {
        $aConfig = [
            'mbtec' => [
                'zf-email' => [
                    'transport' => [
                        'type' => 'sendmail',
                    ],
                    'renderer' => [
                        'subject_prefix' => 'Test: ',
                        'mail_from_name' => 'Test',
                        'mail_from_email' => 'info@mb-tec.eu',
                        'mail_bcc' => [
                            $GLOBALS['receiver_bcc'],
                        ],
                        'email_receiver_override' => $GLOBALS['receiver_override'],
                    ],
                ],
            ],
        ];

        $bSuccess = false;
        try {
            $this->getEmailService($aConfig)
                ->addReceiver($GLOBALS['receiver'])
                ->setTemplate('test/email/simpleMailOverride')
                ->send();

            $bSuccess = true;
        } catch (Exception $oEx) {
        }

        $this->assertEquals($bSuccess, true);
    }

    /**
     *
     */
    public function testMailAttachments()
    {
        $aConfig = [
            'mbtec' => [
                'zf-email' => [
                    'transport' => [
                        'type' => 'sendmail',
                    ],
                    'renderer' => [
                        'subject_prefix' => 'Test: ',
                        'mail_from_name' => 'Test',
                        'mail_from_email' => 'info@mb-tec.eu',
                    ],
                ],
            ],
        ];

        $bSuccess = false;
        try {
            $this->getEmailService($aConfig)
                ->addReceiver($GLOBALS['receiver'])
                ->setTemplate('test/email/simpleMailAttachments')
                ->addAttachmentData('HELLOWORLD.txt', file_get_contents(__DIR__ . '/attachments/HELLOWORLD.txt'))
                ->addAttachmentFile('HELLOWORLD.pdf', __DIR__ . '/attachments/HELLOWORLD.pdf')
                ->addAttachmentFile('HELLOWORLD.odt', __DIR__ . '/attachments/HELLOWORLD.odt')
                ->send();

            $bSuccess = true;
        } catch (Exception $oEx) {
        }

        $this->assertEquals($bSuccess, true);
    }

    /**
     * @param array $aConfig
     *
     * @return Email
     */
    protected function getEmailService(array $aConfig)
    {
        $aRendererConfig = $aConfig['mbtec']['zf-email']['renderer'];
        $aTransportConfig = $aConfig['mbtec']['zf-email']['transport'];

        $aTplMap = [
            'test/email/simpleMail' => __DIR__ . '/email/simpleMail.phtml',
            'test/email/simpleMailOverride' => __DIR__ . '/email/simpleMailOverride.phtml',
            'test/email/simpleMailAttachments' => __DIR__ . '/email/simpleMailAttachments.phtml',
            'test/email/_footer' => __DIR__ . '/email/_footer.phtml',
        ];

        $oResolver = new TemplateMapResolver($aTplMap);

        $oViewRenderer = new PhpRenderer();
        $oViewRenderer->setResolver($oResolver);

        $oRenderer = new Renderer($aRendererConfig, $oViewRenderer);
        $oTransport = new Transport($aTransportConfig);

        $oEmail = new Email($oRenderer, $oTransport);

        return $oEmail;
    }
}
