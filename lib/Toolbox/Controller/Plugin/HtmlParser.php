<?php

namespace Toolbox\Controller\Plugin;

use \Pimcore\Tool;

class HtmlParser extends \Zend_Controller_Plugin_Abstract
{
    /**
     * @var bool
     */
    protected $enabled = TRUE;

    /**
     * @return bool
     */
    public function dispatchLoopShutdown()
    {
        if (!Tool::isHtmlResponse($this->getResponse())) {
            return FALSE;
        }

        $body = $this->getResponse()->getBody();
        $htmlData = $this->getEventData();

        if (isset($htmlData['header']) && !empty($htmlData['header'])) {
            $headEndPosition = stripos($body, "</head>");
            if ($headEndPosition !== FALSE) {
                $body = substr_replace($body, $htmlData['header'] . "</head>", $headEndPosition, 7);
            }
        }
        if (isset($htmlData['footer']) && !empty($htmlData['footer'])) {
            $bodyEndPosition = stripos($body, "</body>");
            if ($bodyEndPosition !== FALSE) {
                $body = substr_replace($body, $htmlData['footer'] . "</body>", $bodyEndPosition, 7);
            }
        }

        $this->getResponse()->setBody($body);
    }

    /**
     * @return bool|string
     */
    private function getEventData()
    {
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;

        if (!$viewRenderer) {
            return FALSE;
        }

        $assetHelper = new \Toolbox\Tool\Asset();
        $assetHelper->setIsBackEnd($viewRenderer->editmode)->setIsFrontEnd(!$viewRenderer->editmode)->setBaseUrl('');

        \Pimcore::getEventManager()->trigger('toolbox.addAsset', $assetHelper);

        return $assetHelper->getHtmlData();
    }

}