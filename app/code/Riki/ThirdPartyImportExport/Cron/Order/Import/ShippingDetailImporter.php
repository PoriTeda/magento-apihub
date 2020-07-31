<?php
namespace Riki\ThirdPartyImportExport\Cron\Order\Import;

class ShippingDetailImporter extends \Riki\Framework\Helper\Importer\Csv
{
    /**
     * ShippingDetailImporter constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Detail $shippingDetailResource
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Detail $shippingDetailResource
    ) {
        $this->db = $shippingDetailResource;
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
            'shipping_no', 'shipping_detail_no', 'shop_code',
            'sku_code', 'unit_price', 'discount_price',
            'discount_amount', 'retail_price', 'retail_tax',
            'shipping_charge_target_flg', 'purchasing_amount',
            'gift_code', 'gift_name', 'gift_price', 'gift_tax_rate',
            'gift_tax', 'gift_tax_type', 'message_card_price',
            'message_card_tax_type', 'message_card_tax_rate',
            'message_card_tax', 'message_card_code',
            'message_card_name', 'message_card_addresses',
            'message_card_text', 'message_card_sender', 'attach_type',
            'distribution_detail_flg', 'sitadori_money',
            'orm_rowid', 'created_user', 'created_datetime',
            'updated_user', 'updated_datetime'
        ];
        $notEmptyValidator =  new \Magento\Framework\Validator\NotEmpty(['type' => \Magento\Framework\Validator\NotEmpty::STRING]);
        $this->validators = [
            [
                'validator' => $notEmptyValidator,
                'columns' => [
                    'shipping_no', 'shipping_detail_no', 'shop_code',
                    'sku_code', 'shipping_charge_target_flg',
                    'purchasing_amount', 'gift_price', 'gift_tax_type',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime'
                ]
            ]
        ];
        $this->filters = [
            [
                'filter' => 'nullExpr',
                'columns' => [
                    'unit_price','discount_price','discount_amount',
                    'retail_price','retail_tax','gift_code','gift_name',
                    'gift_tax_rate','gift_tax','message_card_price',
                    'message_card_tax_type','message_card_tax_rate',
                    'message_card_tax','message_card_code','message_card_name',
                    'message_card_addresses','message_card_text',
                    'message_card_sender','attach_type','distribution_detail_flg',
                    'sitadori_money'
                ]
            ]
        ];
    }
}