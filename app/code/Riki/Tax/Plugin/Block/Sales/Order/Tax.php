<?php
/**
 * *
 *  Tax
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package  Riki\Tax
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Tax\Plugin\Block\Sales\Order;
/**
 * *
 *  Tax
 *
 *  @category RIKI
 *  @package  Riki\Tax\Plugin\Block\Sales\Order
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Tax
{
    /**
     * Tax configuration model
     *
     * @var \Magento\Tax\Model\Config
     */
    protected $config;

    /**
     * Tax construct
     *
     * @param \Magento\Tax\Model\Config $taxConfig $taxConfig
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig
    ) {
        $this->config = $taxConfig;
    }

    /**
     * Re calculate Grand Total (Excl.Tax) of Order View in BE
     * To makes sense with Riki tax and Grand Total (Incl.Tax)
     *
     * @param \Magento\Tax\Block\Sales\Order\Tax $subject Tax
     *
     * @return \Magento\Tax\Block\Sales\Order\Tax
     */
    public function afterInitTotals(\Magento\Tax\Block\Sales\Order\Tax $subject)
    {
        $parent = $subject->getParentBlock();
        /*$source = $subject->getSource();
        $taxRiki = $subject->getOrder()->getTaxRikiTotal();

        $grandototal = $parent->getTotal('grand_total');
        if (!$grandototal || !(double)$source->getGrandTotal()) {
            return $subject;
        }

        $grandtotal = $source->getGrandTotal();
        $baseGrandtotal = $source->getBaseGrandTotal();
        $grandtotalExcl = $grandtotal - $taxRiki;
        $baseGrandtotalExcl = $baseGrandtotal - $taxRiki;

        if ($this->config->displaySalesTaxWithGrandTotal($subject->getStore())) {

            $totalExcl = new \Magento\Framework\DataObject(
                [
                    'code' => 'grand_total',
                    'strong' => true,
                    'value' => $grandtotalExcl,
                    'base_value' => $baseGrandtotalExcl,
                    'label' => __('Grand Total (Excl.Tax)'),
                ]
            );
            $parent->addTotal($totalExcl, 'grand_total');
        }*/
        $parent->removeTotal('grand_total');

        $subTotal = $parent->getTotal('subtotal');
        if ($subTotal !== false) {
            $subTotal->setData('label', __('Items total (Tax included)'));
            $parent->addTotal($subTotal, 'subtotal');
        }

        return $subject;
    }
}