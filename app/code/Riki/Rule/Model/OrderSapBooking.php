<?php
namespace Riki\Rule\Model;
/**
 * Order SAP booking model
 * save order data: WBS, AC, CT information for SAP booking purpose (3.1)
 *
 * @method \Riki\Rule\Model\ResourceModel\OrderSapBooking _getResource()
 * @method \Riki\Rule\Model\ResourceModel\OrderSapBooking getResource()
 */
class OrderSapBooking extends \Magento\Framework\Model\AbstractModel
{
    const TABLE = 'riki_sales_order_sap_booking';

    /**
     * Promotion rule types
     */
    const RULE_TYPE_SALESRULE = 'salesrule';

    const RULE_TYPE_CATALOGRULE = 'catalogrule';

    /**
     * Sap booking types
     */
    const SAP_TYPE_ACCOUNT_CODE = 'account_code';

    const SAP_TYPE_CONDITION_TYPE = 'sap_condition_type';

    const SAP_TYPE_FREE_GIFT = 'wbs_promo_item_free_gift';

    const SAP_TYPE_SHOPPING_POINT = 'wbs_shopping_point';

    const SAP_TYPE_FREE_DELIVERY = 'wbs_free_delivery';

    const SAP_TYPE_FREE_PAYMENT = 'wbs_free_payment_fee';

    const SAP_TYPE_FREE_MACHINE = 'machine_wbs';

    protected function _construct()
    {
        $this->_init('Riki\Rule\Model\ResourceModel\OrderSapBooking');
    }
}