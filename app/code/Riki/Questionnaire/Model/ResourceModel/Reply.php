<?php
namespace Riki\Questionnaire\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Reply extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_enquete_answer_reply', 'reply_id');
    }

    /**
     *
     */
    public function getListByAnswerOrder($orderId){
        if($orderId instanceof \Magento\Sales\Model\Order){
            $orderId = $orderId->getId();
        }

        $select = $this->getConnection()->select()
            ->from($this->getTable('riki_enquete_answer'), ['answer_id'])
            ->where('entity_id = ?', $orderId);
        $answerIds = $this->getConnection()->fetchAll($select);

        $select = $this->getConnection()->select()
            ->from(['ac'    =>  $this->getMainTable()])
            ->joinLeft(
                ['qc'   =>  $this->getTable('riki_enquete_question_choice')],
                'ac.choice_id=qc.choice_id',
                ['label', 'parent_choice_id']
            )
            ->where('ac.answer_id IN(?)', $answerIds);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $choiceId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByChoice($choiceId){

        $select = $this->getConnection()->select()
            ->from(['ac'    =>  $this->getMainTable()])

            ->where('ac.choice_id = ' .$choiceId);

        return $this->getConnection()->fetchOne($select);
    }
}