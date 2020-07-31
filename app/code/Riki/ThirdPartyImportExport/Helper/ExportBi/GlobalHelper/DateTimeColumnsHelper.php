<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

class DateTimeColumnsHelper
{
    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order
     * @return mixed
     */
    public function getOrderDateTimeColumns()
    {
        return [
            'created_at', 'updated_at', 'customer_dob', 'csv_start_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_payment
     * @return mixed
     */
    public function getOrderPaymentDateTimeColumns()
    {
        return [
            'paygent_limit_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table magento_rma
     * @return mixed
     */
    public function getRmaDateTimeColumns()
    {
        return [
            'date_requested', 'returned_date', 'updated_at', 'return_approval_date', 'export_sap_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getShipmentDateTimeColumns()
    {
        return [
            'created_at', 'updated_at', 'shipment_date', 'payment_date', 'export_sap_date', 'nestle_payment_receive_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment_track
     * @return mixed
     */
    public function getShipmentTrackDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_reward_point
     * @return mixed
     */
    public function getRewardPointDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_reward_point
     * @return mixed
     */
    public function getInquiryDateTimeColumns()
    {
        return [
            'enquiry_created_datetime', 'enquiry_updated_datetime'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_enquete
     * @return mixed
     */
    public function getEnqueteDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_enquete_answer
     * @return mixed
     */
    public function getEnqueteAnswerDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table magento_giftwrapping
     * @return mixed
     */
    public function getGiftWrappingDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_shosha_business_code
     * @return mixed
     */
    public function getShoshaCustomerDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_fair_connection, riki_fair_details, riki_fair_management, riki_fair_recommendation
     * @return mixed
     */
    public function getFairAndSeasonalDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }
}
