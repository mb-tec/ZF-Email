<?php

namespace MBtecZfEmail\Service;

use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Html2Text\Html2Text;

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
    protected $oRenderer = null;
    protected $oHtml2Text = null;

    protected $aRendererConfig = [];

    /**
     * Renderer constructor.
     *
     * @param PhpRenderer $oRenderer
     * @param array       $aRendererConfig
     */
    public function __construct(PhpRenderer $oRenderer, array $aRendererConfig)
    {
        $this->aRendererConfig = $aRendererConfig;
        $this->oRenderer = $oRenderer;
    }

    /**
     * @param       $sTpl
     * @param array $aVars
     * @param array $aOpts
     *
     * @return array
     */
    public function renderTemplate($sTpl, array $aVars, array $aOpts)
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

        return $aMailData;
    }

    /**
     * @param       $sTpl
     * @param array $aVars
     *
     * @return array
     */
    protected function getMailData($sTpl, array $aVars = [])
    {
        $sSubject = $sHtml = $sPlain = null;

        $aTplData = explode('######', $this->renderMail($sTpl, $aVars));
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
     * @param       $sTpl
     * @param array $aVars
     *
     * @return string
     */
    protected function renderMail($sTpl, array $aVars = [])
    {
        $oViewModel = new ViewModel($aVars);
        $oViewModel->setTemplate($sTpl);

        return $this->oRenderer->render($oViewModel);
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