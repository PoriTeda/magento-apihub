<?php

namespace Riki\ShippingCarrier\Block\Adminhtml\View;


class Total extends \Magento\Sales\Block\Adminhtml\Totals
{
    /**
     * Retrieve shipment model instance
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        return $this->_coreRegistry->registry('current_shipment');
    }

    /**
     * Retrieve invoice order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getShipment()->getOrder();
    }

    /**
     * Retrieve source
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getSource()
    {
        return $this->getShipment();
    }

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $this->_totals = [];
        $this->_totals['total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'total',
                'strong' => true,
                'value' => $this->getSource()->getAmountTotal(),
                'base_value' => $this->getSource()->getBaseAmountTotal(),
                'label' => __('Items total (tax incl.)')
            ]
        );

        if((float)$this->getSource()->getShoppingPointAmount()){
            $this->_totals['shopping_point'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'shopping_point',
                    'value' => $this->getSource()->getShoppingPointAmount() * -1,
                    'base_value' => $this->getSource()->getBaseShoppingPointAmount() * -1,
                    'label' => __('Shopping points')
                ]
            );
        }

        if((float)$this->getSource()->getDiscountAmount()){
            $this->_totals['discount_amount'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'discount_amount',
                    'value' => $this->getSource()->getDiscountAmount() * -1,
                    'base_value' => $this->getSource()->getBaseDiscountAmount() * -1,
                    'label' => __('Discount Amount')
                ]
            );
        }

        //commisison amount of shipment
        $totalCommissionAmount = 0;
        $itemShipments = $this->getSource()->getItems();
        foreach($itemShipments as $itemShipment){
            $totalCommissionAmount += $itemShipment->getCommissionAmount();
        }

        $this->_totals['commission_amount'] = new \Magento\Framework\DataObject(
            [
                'code' => 'commission_amount',
                'value' => $totalCommissionAmount,
                'base_value' => $totalCommissionAmount,
                'label' => __('Commission Amount')
            ]
        );

        if((float)$this->getSource()->getGwPrice()){
            $this->_totals['gw_price'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'gw_price',
                    'value' => $this->getSource()->getGwPrice() + $this->getSource()->getGwTaxAmount(),
                    'base_value' => $this->getSource()->getGwBasePrice() + $this->getSource()->getGwBaseTaxAmount(),
                    'label' => __('Gift Wrapping Fee')
                ]
            );
        }

        $this->_totals['delivery_fee'] = new \Magento\Framework\DataObject(
            [
                'code' => 'delivery_fee',
                'value' => (float)$this->getSource()->getShipmentFee(),
                'base_value' => (float)$this->getSource()->getBaseShipmentFee(),
                'label' => __('Shipment fee (tax incl.)')
            ]
        );

        if((float)$this->getSource()->getPaymentFee()){
            $this->_totals['surcharge_fee'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'surcharge_fee',
                    'value' => $this->getSource()->getPaymentFee(),
                    'base_value' => $this->getSource()->getBasePaymentFee(),
                    'label' => __('Surcharge Fee')
                ]
            );
        }

        $this->_totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'strong' => true,
                'value' => $this->getFinalTotal(),
                'base_value' => $this->getBaseFinalTotal(),
                'label' => __('Grand Total'),
                'area' => 'footer',
            ]
        );
        return $this;
    }

    /**
     * @return mixed
     */
    protected function getFinalTotal(){
        return $this->getSource()->getAmountTotal()
            + $this->getSource()->getShipmentFee()
            + $this->getSource()->getPaymentFee()
            + $this->getSource()->getGwPrice()
            + $this->getSource()->getGwTaxAmount()
            - $this->getSource()->getShoppingPointAmount()
            - $this->getSource()->getDiscountAmount();
    }

    /**
     * @return mixed
     */
    protected function getBaseFinalTotal(){
        return $this->getSource()->getBaseAmountTotal()
        + $this->getSource()->getBaseShipmentFee()
        + $this->getSource()->getBasePaymentFee()
        + $this->getSource()->getGwBasePrice()
        + $this->getSource()->getGwBaseTaxAmount()
        - $this->getSource()->getBaseShoppingPointAmount()
        - $this->getSource()->getBaseDiscountAmount();
    }

}
