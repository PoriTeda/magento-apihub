<?php
namespace Riki\ThirdPartyImportExport\Cron\Order\Import;

class OrderImporter extends \Riki\Framework\Helper\Importer\Csv
{
    /**
     * OrderImporter constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Order $orderResource
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Order $orderResource
    ) {
        $this->db = $orderResource;
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
            'order_no', 'shop_code', 'order_datetime',
            'customer_code', 'guest_flg', 'last_name',
            'first_name', 'last_name_kana', 'first_name_kana',
            'email', 'send_email_no', 'postal_code',
            'prefecture_code', 'address1', 'address2',
            'address3', 'address4', 'phone_number',
            'free_shipping_flag', 'reason_code',
            'owabijou_type', 'store_code', 'advance_later_flg',
            'payment_method_no', 'payment_method_type',
            'payment_method_name', 'payment_commission',
            'payment_commission_tax_rate',
            'payment_commission_tax',
            'payment_commission_tax_type',
            'used_point', 'bonus_point_amount',
            'point_sheet_number', 'attach_point_mark',
            'payment_date', 'payment_limit_date',
            'payment_status', 'payment_money',
            'purchasing_customer_type', 'customer_group_code',
            'data_transport_status', 'order_status',
            'order_type', 'office_order_flg', 'client_group',
            'caution', 'message', 'payment_order_id',
            'credit_date', 'credit_status', 'cvs_code',
            'payment_recepit_no', 'payment_recepit_url',
            'digital_cash_type', 'transfer_form_flg',
            'form_issue_count', 'creditpayment_erasing_flg',
            'creditpayment_count', 'plan_no', 'plan_type',
            'order_count', 'mastar_sku_code', 'discount_rate',
            'giftaway_exchange_flg', 'warning_message',
            'orm_rowid', 'created_user', 'created_datetime',
            'updated_user', 'updated_datetime',
            'credit_agency_type', 'acq_id'
        ];
        $notEmptyValidator =  new \Magento\Framework\Validator\NotEmpty(['type' => \Magento\Framework\Validator\NotEmpty::STRING]);
        $this->validators = [
            [
                'validator' => $notEmptyValidator,
                'columns' => [
                    'order_no', 'shop_code', 'order_datetime',
                    'customer_code', 'guest_flg', 'last_name',
                    'first_name', 'last_name_kana', 'first_name_kana',
                    'email', 'send_email_no', 'postal_code',
                    'prefecture_code', 'address1', 'address2',
                    'phone_number', 'free_shipping_flag',
                    'advance_later_flg', 'payment_method_no',
                    'payment_method_type', 'payment_commission',
                    'payment_commission_tax_type', 'payment_status',
                    'data_transport_status', 'order_status', 'order_type',
                    'client_group', 'transfer_form_flg', 'form_issue_count',
                    'creditpayment_erasing_flg', 'giftaway_exchange_flg',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime'
                ]
            ]
        ];
        $this->filters = [
            [
                'filter' => 'nullExpr',
                'columns' => [
                    'address3','address4','reason_code',
                    'owabijou_type','store_code','payment_method_name',
                    'payment_commission_tax_rate','payment_commission_tax',
                    'used_point','bonus_point_amount','point_sheet_number',
                    'attach_point_mark','payment_date','payment_limit_date',
                    'payment_money','purchasing_customer_type','customer_group_code',
                    'office_order_flg','caution','message','payment_order_id',
                    'credit_date','credit_status','cvs_code','payment_recepit_no',
                    'payment_recepit_url','digital_cash_type','creditpayment_count',
                    'plan_no','plan_type','order_count','mastar_sku_code',
                    'discount_rate','warning_message','credit_agency_type','acq_id'
                ]
            ]
        ];
    }
}