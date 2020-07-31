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
namespace Riki\SubscriptionFrequency\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class FrequencyUnit
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Model\Source
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class FrequencyUnit implements OptionSourceInterface
{
    /**
     * Frequency
     *
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $frequency;

    /**
     * FrequencyUnit constructor.
     *
     * @param \Riki\SubscriptionFrequency\Model\Frequency $frequency Frequency
     */
    public function __construct(\Riki\SubscriptionFrequency\Model\Frequency $frequency)
    {
        $this->frequency = $frequency;
    }

    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->frequency->getFrequencyUnits();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
