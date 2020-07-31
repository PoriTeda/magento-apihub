<?php
namespace Riki\Subscription\Model\Frequency;

use Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface;
use Magento\Framework\DataObject\IdentityInterface;
/**
 * Subscription Course data model
 *
 * @method \Riki\SubscriptionCourse\Model\ResourceModel\Course _getResource()
 * @method \Riki\SubscriptionCourse\Model\ResourceModel\Course getResource()
 */
class Frequency extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{
    const TABLE = 'subscription_frequency';

    const CACHE_TAG = 'riki_subscription_frequency';
    const WEEK = 1;
    const MONTH = 2;

    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Frequency\ResourceModel\Frequency');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public static function getArrIdToCode() {
        return array(
            self::WEEK => 'week',
            self::MONTH => 'month'
        );
    }

    public static function getArrCodeToId() {
        return array(
            'week' => self::WEEK,
            'month' => self::MONTH,
        );
    }


}