<?php
namespace Riki\ThirdPartyImportExport\Cron\Order\Import;

class ShippingImporter extends \Riki\Framework\Helper\Importer\Csv
{
    /**
     * OrderImporter constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping $shippingResource
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping $shippingResource
    ) {
        $this->db = $shippingResource;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->columns = [
            'shipping_no', 'order_no', 'shop_code',
            'customer_code', 'address_no', 'address_last_name',
            'address_first_name', 'address_last_name_kana',
            'address_first_name_kana', 'postal_code',
            'prefecture_code', 'address1', 'address2',
            'address3', 'address4', 'phone_number',
            'delivery_remark', 'acquired_point', 'delivery_slip_no',
            'shipping_charge', 'shipping_charge_tax_type',
            'shipping_charge_tax_rate', 'shipping_charge_tax',
            'shipping_charge_free_flg', 'delivery_type_no',
            'delivery_type_name', 'delivery_appointed_date',
            'delivery_appointed_time_start',
            'delivery_appointed_time_end',
            'arrival_date', 'arrival_time_start', 'arrival_time_end',
            'delivery_date', 'sitadori_kbn', 'sitadori_commodity_type',
            'fixed_sales_status', 'shipping_status',
            'shipping_direct_date', 'shipping_date', 'shipping_list_flg',
            'original_shipping_no', 'giftaway_shipping_flg',
            'return_item_date', 'return_item_loss_money',
            'return_item_type', 'sap_ren_flg', 'stock_ren_flg',
            'ship_ren_flg', 'bill_ren_flg', 'order_modify_flg',
            'orm_rowid', 'created_user', 'created_datetime',
            'updated_user', 'updated_datetime', 'warehouse_code',
            'delivery_company_code'
        ];
        $notEmptyValidator =  new \Magento\Framework\Validator\NotEmpty(['type' => \Magento\Framework\Validator\NotEmpty::STRING]);
        $this->validators = [
            [
                'validator' => $notEmptyValidator,
                'columns' => [
                    'shipping_no', 'order_no', 'shop_code',
                    'address_last_name', 'address_first_name',
                    'address_last_name_kana', 'address_first_name_kana',
                    'postal_code', 'prefecture_code', 'address1',
                    'address2', 'shipping_charge', 'shipping_charge_free_flg',
                    'shipping_charge_tax_type', 'delivery_type_no',
                    'fixed_sales_status', 'shipping_status', 'shipping_list_flg',
                    'giftaway_shipping_flg', 'orm_rowid',
                    'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime'
                ]
            ]
        ];
        $this->filters = [
            [
                'filter' => 'nullExpr',
                'columns' => [
                    'customer_code','address_no','address3','address4',
                    'phone_number','delivery_remark','acquired_point',
                    'delivery_slip_no','shipping_charge_tax_rate',
                    'shipping_charge_tax','delivery_type_name',
                    'delivery_appointed_date','delivery_appointed_time_start',
                    'delivery_appointed_time_end','arrival_date',
                    'arrival_time_start','arrival_time_end','delivery_date',
                    'sitadori_kbn','sitadori_commodity_type',
                    'shipping_direct_date','shipping_date','original_shipping_no',
                    'return_item_date','return_item_loss_money','return_item_type',
                    'sap_ren_flg','stock_ren_flg','ship_ren_flg','bill_ren_flg',
                    'order_modify_flg','warehouse_code','delivery_company_code'
                ]
            ]
        ];
    }
}