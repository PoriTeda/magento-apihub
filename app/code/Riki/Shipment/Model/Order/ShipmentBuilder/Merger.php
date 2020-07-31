<?php
// @codingStandardsIgnoreFile
/**
 * Shipment Merger
 *
 * PHP version 7
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\Order\ShipmentBuilder;
use Magento\Framework\MessageQueue\MergerInterface;

/**
 * Class Merger
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Merger implements MergerInterface
{
    /**
     * @param array $messages
     * @return array
     */
    public function merge(array $messages)
    {
        return $messages;
    }
}