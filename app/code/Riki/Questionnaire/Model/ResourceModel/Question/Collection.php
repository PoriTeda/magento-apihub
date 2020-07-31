<?php
namespace Riki\Questionnaire\Model\ResourceModel\Question;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Riki\Questionnaire\Model\ResourceModel\Question
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'question_id';

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory
     */
    protected $_questionChoiceCollectionFactory;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory $collectionFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory $collectionFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ){
        $this->_questionChoiceCollectionFactory = $collectionFactory;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource);
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Questionnaire\Model\Question',
            'Riki\Questionnaire\Model\ResourceModel\Question'
        );
    }

    /**
     * @return $this
     */
    public function getQuestions()
    {
        return $this;
    }

    /**
     * Add choice to result
     *
     * @return $this
     */
    public function addChoicesToResult()
    {
        $questionIds = [];

        foreach ($this as $question) {
            $questionIds[] = $question->getId();
        }

        if (!empty($questionIds)) {
            /** @var \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory $choices */
            $choices = $this->_questionChoiceCollectionFactory->create();
            $choices->addQuestionToFilter(
                $questionIds
            )->setOrder(
                'sort_order',
                self::SORT_ORDER_ASC
            )->setOrder(
                'label',
                self::SORT_ORDER_ASC
            );
            
            foreach ($choices as $choice) {
                $questionId = $choice->getQuestionId();
                $questionItem = $this->getItemById($questionId);
                if ($questionItem) {
                    $questionItem->addChoice($choice);
                    $choice->setQuestion($questionItem);
                }
            }
        }

        return $this;

    }

    /**
     * Add Questionnaire To Filter
     *
     * @param $questionnaire
     * @return $this
     */
    public function addQuestionnaireToFilter($questionnaire)
    {
        if (empty($questionnaire)) {
            $this->addFieldToFilter('enquete_id', '');
        } elseif (is_array($questionnaire)) {
            $this->addFieldToFilter('enquete_id', ['in' => $questionnaire]);
        } elseif ($questionnaire instanceof \Riki\Questionnaire\Model\Questionnaire ) {
            $this->addFieldToFilter('enquete_id', $questionnaire->getId());
        } else {
            $this->addFieldToFilter('enquete_id', $questionnaire);
        }
        return $this;
    }

    /**
     * Add is_required filter to select
     *
     * @param bool $required
     * @return $this
     */
    public function addRequiredFilter($required = true)
    {
        $this->addFieldToFilter('main_table.is_required', (string)$required);
        return $this;
    }

    /**
     * Add filtering by question ids
     *
     * @param string|array $questionIds
     * @return $this
     */
    public function addIdsToFilter($questionIds)
    {
        $this->addFieldToFilter('main_table.question_id', $questionIds);
        return $this;
    }

    /**
     * Call of protected method reset
     *
     * @return $this
     */
    public function reset()
    {
        return $this->_reset();
    }

}