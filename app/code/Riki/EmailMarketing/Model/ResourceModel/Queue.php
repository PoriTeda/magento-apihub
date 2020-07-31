<?php
/**
 * *
 *  Email Marketing
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package Riki\Email Marketing
 *  @author Nestle.co.jp <support@nestle.co.jp>
 *  @license https://opensource.org/licenses/MIT MIT License
 *  @link https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Newsletter\Model\Queue as ModelQueue;

/**
 * Class Queue
 *
 *  @category RIKI
 *  @package Riki\Email Marketing
 *  @author Nestle.co.jp <support@nestle.co.jp>
 *  @license https://opensource.org/licenses/MIT MIT License
 *  @link https://github.com/rikibusiness/riki-ecommerce
 */
class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $subscriberCollection,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_email_queue', 'queue_id');
    }
}