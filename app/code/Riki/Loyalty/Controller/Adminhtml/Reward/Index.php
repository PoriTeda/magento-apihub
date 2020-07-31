<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

class Index extends \Riki\Loyalty\Controller\Adminhtml\Reward
{
    /**
     * Shopping point grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $customerCode = $this->_coreRegistry->registry('current_customer_code');
        $statistic = $this->_consumerDb->getPoint($customerCode);
        if (!$statistic['error']) {
            $this->_coreRegistry->register('riki_reward_statistic', $statistic['return']);
        }
        $resultLayout = $this->_resultLayoutFactory->create();
        return $resultLayout;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::viewpoint');
    }
}