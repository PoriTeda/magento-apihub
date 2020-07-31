<?php
namespace Riki\ThirdPartyImportExport\Cron\Order\Import;

class OrderDetailImporter extends \Riki\Framework\Helper\Importer\Csv
{
    /**
     * OrderDetailImporter constructor.
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail $orderDetailResource
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Detail $orderDetailResource
    ) {
        $this->db = $orderDetailResource;
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
            'order_no', 'shop_code', 'sku_code',
            'commodity_code', 'commodity_name',
            'standard_detail1_name', 'standard_detail2_name',
            'purchasing_amount', 'attach_amount', 'unit_price',
            'retail_price', 'retail_tax', 'commodity_tax_rate',
            'commodity_tax', 'commodity_tax_type',
            'commodity_kbn', 'campaign_code', 'campaign_name',
            'campaign_discount_rate',
            'shipping_free_campaign_flg', 'applied_point_rate',
            'used_point_limit_rate', 'applied_point_amount',
            'commodity_length', 'commodity_width',
            'commodity_high', 'commodity_weight',
            'sale_organization_code', 'sap_commodity_code',
            'sap_unit_price', 'distribution_detail_flg',
            'commission_rate', 'orm_rowid',
            'created_user', 'created_datetime',
            'updated_user', 'updated_datetime'
        ];
        $notEmptyValidator =  new \Magento\Framework\Validator\NotEmpty(['type' => \Magento\Framework\Validator\NotEmpty::STRING]);
        $this->validators = [
            [
                'validator' => $notEmptyValidator,
                'columns' => [
                    'order_no', 'shop_code', 'sku_code',
                    'commodity_code', 'commodity_name',
                    'purchasing_amount', 'attach_amount',
                    'unit_price', 'retail_price', 'retail_tax',
                    'commodity_tax_type', 'commodity_kbn',
                    'applied_point_rate', 'orm_rowid',
                    'created_user', 'created_datetime', 'updated_user',
                    'updated_datetime'
                ]
            ]
        ];
        $this->filters = [
            [
                'filter' => 'nullExpr',
                'columns' => [
                    'standard_detail1_name','standard_detail2_name','commodity_tax_rate',
                    'commodity_tax','campaign_code','campaign_name','campaign_discount_rate',
                    'shipping_free_campaign_flg','used_point_limit_rate',
                    'applied_point_amount','commodity_length','commodity_width',
                    'commodity_high','commodity_weight','sale_organization_code',
                    'sap_commodity_code','sap_unit_price','distribution_detail_flg',
                    'commission_rate'
                ]
            ]
        ];
    }
}