<?php
namespace Riki\Questionnaire\Model\ResourceModel\Answers;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Riki\Questionnaire\Model\ResourceModel\Answers
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'answer_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Questionnaire\Model\Answers',
            'Riki\Questionnaire\Model\ResourceModel\Answers'
        );
    }
}
