<?php

namespace MBtec\Email\Service;

/**
 * Class        HtmlConverter
 * @package     MBtec\Email\Service
 * @author      Matthias Büsing <info@mb-tec.eu>
 * @copyright   2016 Matthias Büsing
 * @license     GNU General Public License
 * @link        http://mb-tec.eu
 */
class HtmlConverter
{
    protected $_html;
    protected $_text;
    protected $_width = 75;
    protected $_search = array(
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/[ ]{2,}/',                             // Runs of spaces, pre-handling
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
        //'/<!-- .* -->/',                         // Comments -- which strip_tags might have problem a with
        '/<h[123][^>]*>(.*?)<\/h[123]>/ie',      // H1 - H3
        '/<h[456][^>]*>(.*?)<\/h[456]>/ie',      // H4 - H6
        '/<p[^>]*>/i',                           // <P>
        '/<br[^>]*>/i',                          // <br>
        '/<b[^>]*>(.*?)<\/b>/ie',                // <b>
        '/<strong[^>]*>(.*?)<\/strong>/ie',      // <strong>
        '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
        '/<em[^>]*>(.*?)<\/em>/i',               // <em>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
        '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
        '/<li[^>]*>/i',                          // <li>
        '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/ie',
                                                 // <a href="">
        '/<hr[^>]*>/i',                          // <hr>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
        '/<th[^>]*>(.*?)<\/th>/ie',              // <th> and </th>
        '/&(nbsp|#160);/i',                      // Non-breaking space
        '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i',
		                                         // Double quotes
        '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
        '/&gt;/i',                               // Greater-than
        '/&lt;/i',                               // Less-than
        '/&(amp|#38);/i',                        // Ampersand
        '/&(copy|#169);/i',                      // Copyright
        '/&(trade|#8482|#153);/i',               // Trademark
        '/&(reg|#174);/i',                       // Registered
        '/&(mdash|#151|#8212);/i',               // mdash
        '/&(ndash|minus|#8211|#8722);/i',        // ndash
        '/&(bull|#149|#8226);/i',                // Bullet
        '/&(pound|#163);/i',                     // Pound sign
        '/&(euro|#8364);/i',                     // Euro sign
        '/&[^&;]+;/i',                           // Unknown/unhandled entities
        '/[ ]{2,}/'                              // Runs of spaces, post-handling
    );

    protected $_replace = array(
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        ' ',                                    // Runs of spaces, pre-handling
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // <style>s -- which strip_tags supposedly has problems with
        //'',                                     // Comments -- which strip_tags might have problem a with
        "strtoupper(\"\n\n\\1\n\n\")",          // H1 - H3
        "ucwords(\"\n\n\\1\n\n\")",             // H4 - H6
        "\n\n\t",                               // <P>
        "\n",                                   // <br>
        'strtoupper("\\1")',                    // <b>
        'strtoupper("\\1")',                    // <strong>
        '_\\1_',                                // <i>
        '_\\1_',                                // <em>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\t* \\1\n",                            // <li> and </li>
        "\n\t* ",                               // <li>
        '$this->_buildLinkList("\\1", "\\2")',
                                                // <a href="">
        "\n-------------------------\n",        // <hr>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        "strtoupper(\"\t\t\\1\n\")",            // <th> and </th>
        ' ',                                    // Non-breaking space
        '"',                                    // Double quotes
        "'",                                    // Single quotes
        '>',
        '<',
        '&',
        '(c)',
        '(tm)',
        '(R)',
        '--',
        '-',
        '*',
        '£',
        'EUR',                                  // Euro sign.  ?
        '',                                     // Unknown/unhandled entities
        ' '                                     // Runs of spaces, post-handling
    );

    protected $_allowedTags = '';
    protected $_isConverted = false;
    protected $_linkList = '';
    protected $_linkCount = 0;

    /**
     * @return MBtec_Controller_Action_Helper_ConvertHtmlToText
     */
    public function reset()
    {
        $this->_html = null;
        $this->_text = null;
        $this->_allowedTags = '';
        $this->_isConverted = false;
        $this->_linkList = '';
        $this->_linkCount = 0;

        return $this;
    }

    /**
     * @param null $allowedTags
     * @return MBtec_Controller_Action_Helper_ConvertHtmlToText
     */
    public function setAllowedTags($allowedTags = null)
    {
        $this->_allowedTags = $allowedTags;

        return $this;
    }

    /**
     * @param string $html
     * @return mixed
     */
    public function direct($html = '')
    {
        return $this->convert($html);
    }

    /**
     * @param string $html
     * @return mixed
     */
    public function convert($html = '')
    {
        $this->_html = $html;

        return $this->_getText();
    }

    /**
     * @param $html
     * @return MBtec_Controller_Action_Helper_ConvertHtmlToText
     */
    protected function _isConverted($html)
    {
        $this->_html = $html;
        $this->_isConverted = false;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function _getText()
    {
        if ( !$this->_isConverted ) {
            $this->_convert();
        }

        return $this->_text;
    }

    /**
     * @return MBtec_Controller_Action_Helper_ConvertHtmlToText
     */
    protected function _convert()
    {
        // Variables used for building the link list
        $this->_linkCount = 0;
        $this->_linkList = '';

        $text = trim(stripslashes($this->_html));

        // Run our defined search-and-replace
        $text = preg_replace($this->_search, $this->_replace, $text);

        $textRows = explode("\n", $text);
        $textRows = array_map('ltrim', $textRows);
        $text = implode("\n", $textRows);

        // Strip any other HTML tags
        $text = strip_tags($text, $this->_allowedTags);

        // Bring down number of empty lines to 2 max
        $text = preg_replace("/\n\s+\n/", "\n\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);

        if ( '' != $this->_linkList ) {
            $text .= "\n\nLinks:\n------\n" . $this->_linkList;
        }

        // Wrap the text to a readable format
        if ( $this->_width > 0 ) {
        	$text = wordwrap($text, $this->_width);
        }

        $this->_text = $text;

        $this->_isConverted = true;

        return $this;
    }

    /**
     * @param $link
     * @param $display
     * @return string
     */
    function _buildLinkList($link, $display)
    {
		if ( substr($link, 0, 7) == 'http://' ||
             substr($link, 0, 8) == 'https://' ||
             substr($link, 0, 7) == 'mailto:' ) {
            $this->_linkCount++;
            $this->_linkList .= "[" . $this->_linkCount . "] $link\n";
            $additional = ' [' . $this->_linkCount . ']';
		}
        elseif ( substr($link, 0, 11) == 'javascript:' ) {
			// Don't count the link; ignore it
			$additional = '';
		    // what about href="#anchor" ?
        }
        else {
            $this->_linkCount++;
            $this->_linkList .= "[" . $this->_linkCount . "] " . $this->url;
            if ( substr($link, 0, 1) != '/' ) {
                $this->_linkList .= '/';
            }
            $this->_linkList .= "$link\n";
            $additional = ' [' . $this->_linkCount . ']';
        }

        return $display . $additional;
    }
}
