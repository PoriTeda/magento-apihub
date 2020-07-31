<?php
/**
 * SubscriptionFrequency
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\SubscriptionFrequency\Model;

/**
 * Class Frequency
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Frequency extends \Magento\Framework\Model\AbstractModel
{
    const UNIT_WEEK = 'week';
    const UNIT_MONTH = 'month';
    protected $_eventPrefix = 'frequency';
    protected $_eventObject = 'frequency';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine
    {
        $this->_init('Riki\SubscriptionFrequency\Model\ResourceModel\Frequency');
    }

    /**
     * Get frequency units
     *
     * @return array
     */
    public function getFrequencyUnits()
    {
        return [self::UNIT_WEEK => __('Week'), self::UNIT_MONTH => __('Month')];
    }

    /**
     * Get unit frequency
     *
     * @return mixed
     */
    public function getUnitFrequency()
    {
        return $this->getData("frequency_unit");
    }

    /**
     * Get interval frequency
     *
     * @return mixed
     */
    public function getIntervalFrequency()
    {
        return $this->getData("frequency_interval");
    }
}