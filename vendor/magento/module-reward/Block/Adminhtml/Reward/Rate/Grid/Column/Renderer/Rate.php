<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward rate grid renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @codeCoverageIgnore
 */
namespace Magento\Reward\Block\Adminhtml\Reward\Rate\Grid\Column\Renderer;

class Rate extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Reward\Model\Reward\Rate
     */
    protected $_rate;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\Reward\Rate $rate
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\Reward\Rate $rate,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_rate = $rate;
        parent::__construct($context, $data);
    }

    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $websiteId = $row->getWebsiteId();
        return $this->_rate->getRateText(
            $row->getDirection(),
            $row->getPoints(),
            $row->getCurrencyAmount(),
            0 == $websiteId ? null : $this->_storeManager->getWebsite($websiteId)->getBaseCurrencyCode()
        );
    }
}
