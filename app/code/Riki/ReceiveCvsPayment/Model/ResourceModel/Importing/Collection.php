<?php
namespace Riki\ReceiveCvsPayment\Model\ResourceModel\Importing;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'upload_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\ReceiveCvsPayment\Model\Importing', 'Riki\ReceiveCvsPayment\Model\ResourceModel\Importing');
        $this->_map['fields']['upload_id'] = 'main_table.upload_id';
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }
}