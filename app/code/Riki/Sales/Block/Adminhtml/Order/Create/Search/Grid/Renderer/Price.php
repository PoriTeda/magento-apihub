<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer;

/**
 * Adminhtml sales create order product search grid price column renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Price extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Price
{

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_productRepository = $productRepository;
        $this->_localeCurrency = $localeCurrency;
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;

    }

    /**
     * Render minimal price for downloadable products
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getTypeId() == 'downloadable') {
            $row->setPrice($row->getPrice());
        }

        $_product = $this->_productRepository->getById($row->getData('entity_id'));

        $prices = $_product->getTierPrices();

        if ($prices) {
            if ($_product->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $info = $this->_getBundleTierPriceInfo($prices);
            } else {
                $info = $this->_getTierPriceInfo($prices);
            }

            $tierPrice = '<br />'.__('Tier Pricing').'<br />'.implode('<br />', $info);

            return $this->renderNew($row).$tierPrice;
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