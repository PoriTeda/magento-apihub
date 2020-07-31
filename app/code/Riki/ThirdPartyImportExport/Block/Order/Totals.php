<?php
namespace Riki\ThirdPartyImportExport\Block\Order;

class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * Associated array of totals
     * array(
     *  $totalCode => $totalObject
     * )
     *
     * @var array
     */
    protected $_totals;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\Order|null
     */
    protected $_order = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize self totals and children blocks totals before html building
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->_initTotals();

        return parent::_beforeToHtml();
    }

    /**
     * Get order object
     *
     * @return \Riki\ThirdPartyImportExport\Model\Order
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            if ($this->hasData('order')) {
                $this->_order = $this->_getData('order');
            } elseif ($this->_coreRegistry->registry('current_order')) {
                $this->_order = $this->_coreRegistry->registry('current_order');
            } elseif ($this->getParentBlock()->getOrder()) {
                $this->_order = $this->getParentBlock()->getOrder();
            }
        }
        return $this->_order;
    }

    /**
     * @param \Riki\ThirdPartyImportExport\Model\Order $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Get totals source object
     *
     * @return \Riki\ThirdPartyImportExport\Model\Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $source = $this->getOrder();

        $this->_totals = [];

        $this->_totals['total_amount_product'] = new \Magento\Framework\DataObject(
            [
                'code' => 'total_amount_product',
                'field' => 'total_amount_product',
                'value' => $source->formatPrice($source->getTotalAmountProduct()),
                'label' => __('Total Amount Product'),
            ]
        );

        $this->_totals['wrapping_fee'] = new \Magento\Framework\DataObject(
            [
                'code' => 'wrapping_fee',
                'field' => 'wrapping_fee',
                'value' => $source->formatPrice($source->getWrappingFee()),
                'label' => __('Wrapping Fee'),
            ]
        );

        $this->_totals['shipping_fee'] = new \Magento\Framework\DataObject(
            [
                'code' => 'shipping_fee',
                'field' => 'shipping_fee',
                'value' => $source->formatPrice($source->getShippingCharge()),
                'label' => __('Shipping Fee'),
            ]
        );

        $this->_totals['payment_fee'] = new \Magento\Framework\DataObject(
            [
                'code' => 'payment_fee',
                'field' => 'payment_fee',
                'value' => $source->formatPrice($source->getPaymentCommission()),
                'label' => __('Payment Fee'),
            ]
        );

        $this->_totals['used_point'] = new \Magento\Framework\DataObject(
            [
                'code' => 'point_used',
                'field' => 'point_used',
                'value' => $source->getUsedPoint(),
                'label' => __('Point Used'),
            ]
        );


        $this->_totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'value' => $source->formatPrice($source->getGrandTotal()),
                'label' => __('Grand Total'),
            ]
        );

        $this->_totals['acquired_point'] = new \Magento\Framework\DataObject(
            [
                'code' => 'acquired_point',
                'field' => 'acquired_point',
                'value' => $source->getTotalAquiredPoint(),
                'label' => __('Earned Point'),
            ]
        );

        return $this;
    }

    /**
     * Add new total to totals array after specific total or before last total by default
     *
     * @param   \Magento\Framework\DataObject $total
     * @param   null|string $after
     * @return  $this
     */
    public function addTotal(\Magento\Framework\DataObject $total, $after = null)
    {
        if ($after !== null && $after != 'last' && $after != 'first') {
            $totals = [];
            $added = false;
            foreach ($this->_totals as $code => $item) {
                $totals[$code] = $item;
                if ($code == $after) {
                    $added = true;
                    $totals[$total->getCode()] = $total;
                }
            }
            if (!$added) {
                $last = array_pop($totals);
                $totals[$total->getCode()] = $total;
                $totals[$last->getCode()] = $last;
            }
            $this->_totals = $totals;
        } elseif ($after == 'last') {
            $this->_totals[$total->getCode()] = $total;
        } elseif ($after == 'first') {
            $totals = [$total->getCode() => $total];
            $this->_totals = array_merge($totals, $this->_totals);
        } else {
            $last = array_pop($this->_totals);
            $this->_totals[$total->getCode()] = $total;
            $this->_totals[$last->getCode()] = $last;
        }
        return $this;
    }

    /**
     * Add new total to totals array before specific total or after first total by default
     *
     * @param   \Magento\Framework\DataObject $total
     * @param   null|string $before
     * @return  $this
     */
    public function addTotalBefore(\Magento\Framework\DataObject $total, $before = null)
    {
        if ($before !== null) {
            if (!is_array($before)) {
                $before = [$before];
            }
            foreach ($before as $beforeTotals) {
                if (isset($this->_totals[$beforeTotals])) {
                    $totals = [];
                    foreach ($this->_totals as $code => $item) {
                        if ($code == $beforeTotals) {
                            $totals[$total->getCode()] = $total;
                        }
                        $totals[$code] = $item;
                    }
                    $this->_totals = $totals;
                    return $this;
                }
            }
        }
        $totals = [];
        $first = array_shift($this->_totals);
        $totals[$first->getCode()] = $first;
        $totals[$total->getCode()] = $total;
        foreach ($this->_totals as $code => $item) {
            $totals[$code] = $item;
        }
        $this->_totals = $totals;
        return $this;
    }

    /**
     * Get Total object by code
     *
     * @param string $code
     * @return mixed
     */
    public function getTotal($code)
    {
        if (isset($this->_totals[$code])) {
            return $this->_totals[$code];
        }
        return false;
    }

    /**
     * Delete total by specific
     *
     * @param   string $code
     * @return  $this
     */
    public function removeTotal($code)
    {
        unset($this->_totals[$code]);
        return $this;
    }


    /**
     * get totals array for visualization
     *
     * @param array|null $area
     * @return array
     */
    public function getTotals($area = null)
    {
        $totals = [];
        if ($area === null) {
            $totals = $this->_totals;
        } else {
            $area = (string)$area;
            foreach ($this->_totals as $total) {
                $totalArea = (string)$total->getArea();
                if ($totalArea == $area) {
                    $totals[] = $total;
                }
            }
        }
        return $totals;
    }
}
