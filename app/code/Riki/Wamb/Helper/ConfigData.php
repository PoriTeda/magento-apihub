<?php
namespace Riki\Wamb\Helper;

use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class ConfigData extends \Magento\Framework\App\Helper\AbstractHelper
{
    const WAMB_ENABLE_CRON = 'consumer_db_api_url/customer_wamb_setting/WAMB_enable_cron';

    const WAMB_CRON_SETTING = 'consumer_db_api_url/customer_wamb_setting/WAMB_cron_setting';

    const WAMB_COURSE_NAME = 'consumer_db_api_url/customer_wamb_setting/WAMB_course_name';

    const WAMB_COURSE_CODE = 'consumer_db_api_url/customer_wamb_setting/WAMB_course_code';

    /**
     * Get wamb cron enable
     *
     * @return bool
     */
    public function getWambCronEnable()
    {
        return (bool)$this->scopeConfig->getValue(static::WAMB_ENABLE_CRON);
    }

    /**
     * Get wamb cron setting
     *
     * @return string
     */
    public function getWambCronSetting()
    {
        return (string)$this->scopeConfig->getValue(static::WAMB_CRON_SETTING);
    }

    /**
     * Get wamb course name
     *
     * @return string
     */
    public function getWambCourseName()
    {
        return (string)$this->scopeConfig->getValue(static::WAMB_COURSE_NAME);
    }

    /**
     * Get wamb course code
     *
     * @return string
     */
    public function getWambCourseCode()
    {
        return (string)$this->scopeConfig->getValue(static::WAMB_COURSE_CODE);
    }

    /**
     * Get allowed wamb order status
     *
     * @return array
     */
    public function getWambAllowedOrderStatus()
    {
        return [
            OrderStatus::STATUS_ORDER_IN_PROCESSING,
            OrderStatus::STATUS_ORDER_NOT_SHIPPED,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED,
            OrderStatus::STATUS_ORDER_SHIPPED_ALL,
            OrderStatus::STATUS_ORDER_CAPTURE_FAILED,
            OrderStatus::STATUS_ORDER_COMPLETE,
        ];
    }
}