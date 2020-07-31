<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

/**
 * Class Questionnaire
 * @package Riki\Subscription\Controller\Disengage\Profile
 */
class Questionnaire extends \Riki\Subscription\Controller\Disengage\Profile\AbstractDisengagement
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
        $this->_cleanDataAtQuestionnairePage();
        return $this->resultPageFactory->create();
    }

    /**
     * Destroy session at page questionnaire
     */
    private function _cleanDataAtQuestionnairePage()
    {
        $this->sessionManager->unsCancelProfileSuccess();
    }
}
