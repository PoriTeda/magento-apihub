<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Rma\Controller\Adminhtml\Rma;

use Magento\Customer\Controller\RegistryConstants;

class RmaCustomer extends \Magento\Rma\Controller\Adminhtml\Rma\RmaCustomer
{
    /**
     * Generate RMA grid for ajax request from customer page
     *
     * @return void
     */
    public function execute()
    {
        $customerId = intval($this->getRequest()->getParam('id'));
        if ($customerId) {
            $this->_coreRegistry->register(RegistryConstants::CURRENT_CUSTOMER_ID,$customerId);
            $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock(
                    'Magento\Rma\Block\Adminhtml\Customer\Edit\Tab\Rma'
                )->setCustomerId(
                    $customerId
                )->toHtml()
            );
        }
    }
}
