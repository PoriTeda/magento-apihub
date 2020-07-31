<?php
namespace Riki\Questionnaire\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Choice extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_enquete_question_choice', 'choice_id');
    }

    /**
     * Delete choice by question id
     *
     * @param $questionId
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteChoice($questionId)
    {
        $statement = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete_question_choice')
        )->where(
            'question_id = ?',
            $questionId
        );

        $rowSet = $this->getConnection()->fetchAll($statement);

        foreach ($rowSet as $choice) {
            $this->deleteChoices($choice['choice_id']);
        }

        $this->getConnection()->delete($this->getMainTable(), ['question_id = ?' => $questionId]);

        return $this;
    }

    /**
     * Delete choice by choice id
     *
     * @param $choiceId
     */
    public function deleteChoices($choiceId)
    {
        $condition = ['choice_id = ?' => $choiceId];

        $this->getConnection()->delete($this->getTable('riki_enquete_question_choice'), $condition);

    }
    
    /**
     * Delete choice by parent choice id
     *
     * @param $choiceId
     */
    public function deleteChildChoices($choiceId)
    {
        $condition = ['parent_choice_id = ?' => $choiceId];

        $this->getConnection()->delete($this->getTable('riki_enquete_question_choice'), $condition);

    }

    /**
     * @param $choiceNo
     * @return string
     */
    public function findChoiceIdByChoiceNo($choiceNo){
        $sql = $this->getConnection()
            ->select()
            ->from($this->getMainTable(),array('choice_id'))
            ->where('legacy_enquete_choices_no = ?',$choiceNo);
        $choiceId = $this->getConnection()->fetchOne($sql);
        return $choiceId;
    }
    /**
     * @param array $data
     * @return int|void
     */
    public function insertArrayChoice($data = []) {
        if(!$data)
            return;
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
    public function updateChoices($data = [])
    {
        $errors = [];

        foreach ($data as $choiceId => $postData) {
            try {
                $where = ['choice_id = ?' => (int)$choiceId];

                $this->getConnection()->update($this->getMainTable(), $postData, $where);
            } catch (\Exception $e) {
                $errors[] = __('Choice ID %1', $choiceId) . ': ' . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * get array of choice id to no
     *
     * @return array
     */
    public function getAllIdsToNo(){
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['choice_id', 'legacy_enquete_choices_no']
        );

        return $this->getConnection()->fetchPairs($select);
    }

}