<?php
namespace Riki\Customer\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Category post mysql resource
 */
class CategoryEnquiry extends AbstractModel
{

    const CATEGORY_ID = 'entity_id'; // We define the id fieldname

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'enquiry'; // parent value is 'core_abstract'

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'category'; // parent value is 'object'

    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = self::CATEGORY_ID; // parent value is 'id'

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Customer\Model\ResourceModel\CategoryEnquiry');
    }
}