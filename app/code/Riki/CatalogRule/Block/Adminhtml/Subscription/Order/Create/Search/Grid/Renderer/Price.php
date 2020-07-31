<?php
namespace Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Search\Grid\Renderer;

class Price extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Price
{
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogDataHelper;

    /**
     * Price constructor.
     * @param \Magento\Catalog\Helper\Data $catalogDataHelper
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Helper\Data $catalogDataHelper,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        $this->_catalogDataHelper = $catalogDataHelper;
        $this->_productRepository = $productRepository;
        $this->_localeCurrency = $localeCurrency;
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;

        parent::__construct($context, $localeCurrency, $data);
    }

    /**
     * @inheritdoc
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $finalPrice = $row->getFinalPrice();
        $taxPrice = $this->_catalogDataHelper->getTaxPrice($row, $finalPrice);
        $row->setPrice($taxPrice);

        ///support show tier-price
        $_product = $this->_productRepository->getById($row->getData('entity_id'));

        $prices = $_product->getTierPrices();

        if ($prices) {
            if ($_product->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $info = $this->_getBundleTierPriceInfo($prices);
            } else {
                $info = $this->_getTierPriceInfo($prices);
            }

            /*$tierPrice = '<br />'.__('Tier Pricing').'<br />'.implode('<br />', $info);*/

            return $this->renderNew($row);
        }
        else{
            return $this->renderNew($row);
        }
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return float|mixed|string
     * @throws \Zend_Currency_Exception
     */
    protected function renderNew(\Magento\Framework\DataObject $row){
        if ($data = $this->_getValue($row)) {
            $currencyCode = $this->_getCurrencyCode($row);

            if (!$currencyCode) {
                return $data;
            }

            $data = floatval($data) * $this->_getRate($row);
            $data = sprintf("%f", $data);
            $data = $this->_localeCurrency->getCurrency($currencyCode)->toCurrency($data, ['position'   =>  \Zend_Currency::RIGHT]);
            return $data;
        }
        return $this->getColumn()->getDefault();
    }

    /**
     * Get tier price info to display in grid for Bundle product
     *
     * @param array $prices
     * @return string[]
     */
    protected function _getBundleTierPriceInfo($prices)
    {
        $info = [];
        foreach ($prices as $data) {
            $qty = $data['qty'] * 1;
            $info[] = __('%1 with %2 discount each', $qty, $data['value'] * 1 . '%');
        }
        return $info;
    }

    /**
     * Get tier price info to display in grid
     *
     * @param array $prices
     * @return string[]
     */
    protected function _getTierPriceInfo($prices)
    {
        $info = [];
        foreach ($prices as $data) {
            $qty = $data['qty'] * 1;
            $price = $this->convertPrice($data['value']);
            $info[] = __('%1 for %2', $qty, $price);
        }
        return $info;
    }

    /**
     * Convert price
     *
     * @param float $value
     * @param bool $format
     * @return float
     */
    public function convertPrice($value, $format = true)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat(
                $value,
                true,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                $this->_storeManager->getStore()
            )
            : $this->priceCurrency->convert($value, $this->_storeManager->getStore());
    }

}
