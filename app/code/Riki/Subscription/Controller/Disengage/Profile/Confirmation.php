<?php
namespace Riki\Subscription\Controller\Disengage\Profile;

use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

/**
 * Class Confirmation
 * @package Riki\Subscription\Controller\Disengage\Profile
 */
class Confirmation extends \Riki\Subscription\Controller\Disengage\Profile\AbstractDisengagement
{
    /**
     * Disengagement profile confirmation page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath(DisengagementUrl::URL_CUSTOMER_LOGIN);
        }
        if (!$this->sessionManager->getCancelProfileSuccess()) {
            return $resultRedirect->setPath(DisengagementUrl::URL_DISENGAGEMENT_LIST);
        }
        $this->coreRegistry->register('disengaged_profile_id', $this->sessionManager->getProfileDisengagement());
        $this->cleanDisengagedProfile();
        return $this->resultPageFactory->create();
    }
}
