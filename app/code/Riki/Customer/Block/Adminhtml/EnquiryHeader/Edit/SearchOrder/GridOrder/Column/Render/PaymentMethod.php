<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder\GridOrder\Column\Render;
use Magento\Backend\Block\Context;

/**
 * Column renderer for gift registry item grid qty column
 * @codeCoverageIgnore
 */
class PaymentMethod extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render gift registry item qty as input html element
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;
        parent::__construct($context, []);
    }

    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        return $row->getPayment()->getMethodInstance()->getTitle();
    }
}
