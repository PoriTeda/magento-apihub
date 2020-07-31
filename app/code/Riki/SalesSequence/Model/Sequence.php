<?php
/**
 * SalesSequence
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SalesSequence
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */


namespace Riki\SalesSequence\Model;

use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Framework\DB\Sequence\SequenceInterface;
use Magento\SalesSequence\Model\Meta as Meta;

/**
 * Sequence
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SalesSequence
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */


class Sequence extends  \Magento\SalesSequence\Model\Sequence
{
    /**
     * Default pattern for Sequence
     */
    const DEFAULT_PATTERN  = "%s%'.09d%s";

    /**
     * IncrementId
     *
     * @var string
     */
    private $lastIncrementId; // @codingStandardsIgnoreLine

    /**
     * Meta
     *
     * @var Meta
     */
    private $meta; // @codingStandardsIgnoreLine

    /**
     * AdapterInterface
     *
     * @var false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection; // @codingStandardsIgnoreLine

    /**
     * Pattern
     *
     * @var string
     */
    private $pattern; // @codingStandardsIgnoreLine

    /**
     * Constructor
     *
     * @param Meta        $meta     Meta
     * @param AppResource $resource AppResource
     * @param string      $pattern  string
     */
    public function __construct(
        Meta $meta,
        AppResource $resource,
        $pattern = self::DEFAULT_PATTERN
    ) {
        $this->meta = $meta;
        $this->connection = $resource->getConnection('sales');
        $this->pattern = $pattern;
    }

    /**
     * Retrieve current value
     *
     * @return string
     */
    public function getCurrentValue()
    {
        if (!isset($this->lastIncrementId)) {
            return null;
        }
        //Order ID	11 digits
        //Shipment ID	9 digits
        //Return ID	7 digits
        //Credit Memo ID 	6 digits
        switch(strtolower($this->meta->getEntityType()))
        {
        case 'order':
            $pattern = "%s%'.09d%s";
            break;
        case 'shipment':
            $pattern = "%s%'.08d%s";
            break;
        case 'creditmemo':
            $pattern = "%s%'.05d%s";
            break;
        case 'rma_item':
            $pattern = "%s%'.06d%s";
            break;
        default:
            $pattern = "%s%'.08d%s";
            break;
        }
        return sprintf(
            $pattern,
            $this->meta->getActiveProfile()->getPrefix(),
            $this->calculateCurrentValue(),
            $this->meta->getActiveProfile()->getSuffix()
        );
    }

    /**
     * Retrieve next value
     *
     * @return string
     */
    public function getNextValue()
    {
        $this->meta->setCheckTempTable(true);
        $this->connection->insert($this->meta->getSequenceTable(), []);
        $this->meta->setCheckTempTable(false);
        $this->lastIncrementId = $this->connection->lastInsertId(
            $this->meta->getSequenceTable()
        );
        return $this->getCurrentValue();
    }

    /**
     * Calculate current value depends on start value
     *
     * @return string
     */
    protected function calculateCurrentValue()
    {
        $startValue = $this->meta->getActiveProfile()->getStartValue();
        return ($this->lastIncrementId - $startValue)
        * $this->meta->getActiveProfile()->getStep()
        + $this->meta->getActiveProfile()->getStartValue();
    }
}
