<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

/**
 * Class Attention
 * @package Riki\Subscription\Controller\Disengage\Profile
 */

class Attention extends \Riki\Subscription\Controller\Disengage\Profile\AbstractDisengagement
{
    /**
     * Disengagement profile Attention page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath(DisengagementUrl::URL_CUSTOMER_LOGIN);
        }
        if (!$this->validateProfileIdFromSession()) {
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
        }
        $this->_cleanDataAtAttentionPage();
        return $this->resultPageFactory->create();
    }

    /**
     * Destroy session at page attention
     */
    private function _cleanDataAtAttentionPage()
    {
        $this->sessionManager->unsSelectedReasons();
        $this->sessionManager->unsSelectedQuestionnaireAnswers();
        $this->sessionManager->unsCancelProfileSuccess();
    }
}
