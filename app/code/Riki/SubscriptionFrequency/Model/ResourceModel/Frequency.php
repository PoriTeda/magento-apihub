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
namespace Riki\SubscriptionFrequency\Model\ResourceModel;

/**
 * Class Frequency
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Frequency extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Frequency constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->functionCache = $functionCache;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()  // @codingStandardsIgnoreLine
    {
        $this->_init('subscription_frequency', 'frequency_id');
    }

    /**
     * Check Duplicate Frequency
     *
     * @param string $unit Unit
     * @param mixed  $int  Int
     *
     * @return array
     */
    public function checkDuplicateFrequency($unit, $int)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('frequency_unit=:unit')
            ->where('frequency_interval=:interval');

        $result = $this->getConnection()->fetchRow(
            $select,
            [
                'unit' => $unit,
                'interval' => $int
            ]
        );

        if (!$result) {
            return [];
        }

        return $result;
    }

    /**
     * Get id by data
     *
     * @param string $unit     Unit
     * @param string $interval Interval
     *
     * @return string
     */
    public function getIdByData($unit, $interval)
    {
        $cacheKey = [$unit, $interval];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_frequency'),
            ['frequency_id']
        )->where('frequency_unit = ?', $unit)->where('frequency_interval = ?', $interval);

        $result = $this->getConnection()->fetchOne($select);
        $this->functionCache->store($result, $cacheKey);

        return $result;
    }
}