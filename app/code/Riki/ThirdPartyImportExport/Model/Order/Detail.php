<?php
namespace Riki\ThirdPartyImportExport\Model\Order;


class Detail extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * Detail constructor.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_priceCurrency = $priceCurrency;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->_init('Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail');
    }

    /**
     * Format price with currency
     *
     * @param $price
     * @return float
     */
    public function formatPrice($price)
    {
        return $this->_priceCurrency->format($price);
    }

    /**
     * Is ignore when calc and display
     *
     * @return bool
     */
    public function isIgnore()
    {
        if ($this->getData('sku_code') == 'HANPUKAIDISCOUNT') {
            return true;
        }

        return false;
    }
}
