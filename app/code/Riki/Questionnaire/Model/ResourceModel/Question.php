<?php
namespace Riki\Questionnaire\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory;

class Question extends AbstractDb
{
    /**
     * @var CollectionFactory
     */
    protected $collectionQuestionFactory;

    /**
     * Question constructor.
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory $collectionQuestionFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
    
        $this->collectionQuestionFactory = $collectionQuestionFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_enquete_question', 'question_id');
    }
    /**
     * @param array $data
     * @return int|void
     */
    public function insertArrayQuestion($data = []) {
        if (!$data) {
            return;
        }
        $columns = array_keys($data[0]);
        return $this->getConnection()->insertArray(
            $this->getMainTable(),
            $columns,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     */
    public function updateQuestion($data = [])
    {
        $errors = [];

        foreach ($data as $questionId => $postData) {
            try {
                $where = ['question_id = ?' => (int)$questionId];

                $this->getConnection()->update($this->getMainTable(), $postData, $where);
            } catch (\Exception $e) {
                $errors[] = __('Question ID %1', $questionId) . ': ' . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * @param $questionNo
     * @return string
     */
    public function findQuestionbyNo($questionNo){
        $sql = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), ['question_id'])
            ->where('legacy_enquete_question_no = ?', $questionNo);
        $questionId = $this->getConnection()->fetchOne($sql);
        return $questionId;
    }

    /**
     * get array of question id to no
     *
     * @return array
     */
    public function getAllIdsToNo(){
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['question_id', 'legacy_enquete_question_no']
        );

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Delete Question By Enquete Question No
     *
     * @param $arrNo
     */
    public function deleteQuestionByNoIdArr($arrNo)
    {
        $condition = ['legacy_enquete_question_no in (?)' => $arrNo];

        $this->getConnection()->delete($this->getTable('riki_enquete_question'), $condition);
    }

    /**
     * @param $configEnqueteIds
     * @param bool $groupEnquete
     * @return array
     */
    public function getConfigedQuestions($configEnqueteIds, $groupEnquete = true)
    {
        $questionData = [];
        $questionCollection = $this->collectionQuestionFactory->create();
        $questionCollection->addFieldToFilter('riki_enquete.enquete_id', ['in' => $configEnqueteIds]);
        $questionCollection->join(
            'riki_enquete',
            'riki_enquete.enquete_id = main_table.enquete_id',
            []
        );
        $questionCollection->setOrder('sort_order', 'ASC');
        $questionCollection->addFieldToFilter(
            'riki_enquete.is_enabled',
            \Riki\Questionnaire\Model\Questionnaire::STATUS_ENABLED
        );
        if ($groupEnquete) {
            if ($questionCollection->getItems()) {
                foreach ($questionCollection->getItems() as $questionItem) {
                    $questionData[$questionItem->getData('enquete_id')][] = $questionItem;
                }
            }
            return $questionData;
        } else {
            return $questionCollection->getItems();
        }
    }
}
