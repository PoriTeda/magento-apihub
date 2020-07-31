<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchCustomer\GridCustomer\Column\Render;
use Magento\Backend\Block\Context;

/**
 * Column renderer for gift registry item grid qty column
 * @codeCoverageIgnore
 */
class LastnameKana extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * Render gift registry item qty as input html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @param void
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    )
    {
        $this->_customerFactory = $customerFactory;
        parent::__construct($context, []);
    }

    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $customerId = $row->getData('entity_id');

        $lastnamekana = $this->_customerFactory->create()->load($customerId)->getData('lastnamekana');

        return $lastnamekana;
    }
}
