<?php
/**
 * StatusOption Class
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model\Source
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class StatusOption
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model\Source
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class JobStatusOption implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => __('Enable'), 'value' => 1];
        $options[] = ['label' => __('Disabled'), 'value' => 0];
        return $options;
    }
}
