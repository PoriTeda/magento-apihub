<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

class ListAction extends \Riki\Subscription\Controller\Disengage\Profile\AbstractDisengagement
{
    /**
     * Default customer account page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_getLoginUrl());
            return $resultRedirect;
        }
        $this->validateProfileIdFromSession();
        $this->_cleanDataAtProfileListPage();
        return $this->resultPageFactory->create();
    }

    /**
     * Destroy session at page profile list
     */
    private function _cleanDataAtProfileListPage()
    {
        $this->sessionManager->unsAttentionNote();
        $this->sessionManager->unsSelectedReasons();
        $this->sessionManager->unsSelectedQuestionnaireAnswers();
        $this->sessionManager->unsCancelProfileSuccess();
    }

    /**
     * @return string
     */
    private function _getLoginUrl()
    {
        $returnUrl = $this->urlEncoder->encode(
            $this->urlBuilder->getUrl(DisengagementUrl::URL_DISENGAGEMENT_LIST)
        );
        return $this->urlBuilder->getUrl(
            DisengagementUrl::URL_CUSTOMER_LOGIN,
            [\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $returnUrl]
        );
    }
}
