<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Adminhtml\Order\Create;

/**
 * Adminhtml sales order create block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Customer extends \Magento\Sales\Block\Adminhtml\Order\Create\Customer
{
    /**
     * Get buttons html
     *
     * @return string
     */
    public function getButtonsSearchConsumerDBHtml()
    {
        if ($this->_authorization->isAllowed('Magento_Customer::manage')) {
            $addButtonData = [
                'label' => __('Search Customer ConsumerDB'),
                'class' => 'primary searchconsumerdb',
            ];
            return $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData($addButtonData)
                ->toHtml();
        }
        return '';
    }

    /**
     * Get Auto Search CustomerId
     *
     * @return mixed
     */
    public function getAutoSearchCustomerId()
    {
        $customerId = $this->_request->getParam('customerid');
        if($customerId){
            return $customerId;
        }
        return 0;
    }
}
