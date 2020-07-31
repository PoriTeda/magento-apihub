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

class StatusOption implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => 'Success', 'value' => 'success'];
        $options[] = ['label' => 'Pending', 'value' => 'pending'];
        $options[] = ['label' => 'Running', 'value' => 'running'];
        $options[] = ['label' => 'Missed', 'value' => 'missed'];
        $options[] = ['label' => 'Error', 'value' => 'error'];
        $options[] = ['label' => 'Killed', 'value' => 'killed'];
        return $options;
    }
}
