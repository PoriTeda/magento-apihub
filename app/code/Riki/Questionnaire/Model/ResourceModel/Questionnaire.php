<?php
namespace Riki\Questionnaire\Model\ResourceModel;

/**
 * Class Questionnaire
 * @package Riki\Questionnaire\Model\ResourceModel
 *
 * @method \Riki\Questionnaire\Model\ResourceModel\Questionnaire _getResource()
 * @method \Riki\Questionnaire\Model\ResourceModel\Questionnaire getResource()
 */
class Questionnaire extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_enquete', 'enquete_id');
    }

    /**
     * Get SKU through questionnaire identifiers
     *
     * @param array $questionnaireIds
     *
     * @return array
     */
    public function getQuestionnairesSku(array $questionnaireIds)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete'),
            ['enquete_id', 'linked_product_sku']
        )->where(
            'enquete_id IN (?)',
            $questionnaireIds
        );
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Get questionnaire Ids by sku
     *
     * @param array $listSku
     *
     * @return array
     */
    public function getQuestionnaireIdsBySkus(array $listSku)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete'),
            ['linked_product_sku', 'enquete_id']
        )->where(
            'linked_product_sku IN (?)',
            $listSku
        );

        $result = [];
        $data = $this->getConnection()->fetchAll($select);
        foreach ($data as $row) {
            $result[$row['linked_product_sku']] = $row['enquete_id'];
        }
        return $result;
    }

    /**
     * Get Questionnaire identifier by SKU
     *
     * @param $sku
     *
     * @return string
     */
    public function getQuestionnaireIdBySku($sku)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete'),
            'enquete_id'
        )->where('linked_product_sku = :sku');

        $bind = [':sku' => (string)$sku];

        return $this->getConnection()->fetchOne($select, $bind);
    }

    /**
     * @param array $data
     * @return int|void
     */
    public function insertArrayQuestionnaire($data = []) {
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
    public function updateArrayQuestionnaire($data = [])
    {
        $errors = [];

        foreach ($data as $courseId => $postData) {
            try {
                $where = ['enquete_id = ?' => (int)$courseId];

                $this->getConnection()->update($this->getMainTable(), $postData, $where);
            } catch (\Exception $e) {
                $errors[] = __('Enquete ID %1', $courseId) . ': ' . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * get array of questionnaire id to code
     *
     * @return array
     */
    public function getAllIdsToCodes(){
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['enquete_id', 'code']
        );

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * @param $enquetecode
     * @return string
     */
    public function findQuestionnairebyEnqueteCode($enquetecode){
        $sql = $this->getConnection()
            ->select()
            ->from($this->getMainTable(),array('enquete_id'))
            ->where(
                'code = ?',
                $enquetecode
            );
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }

    /**
     * Delete enquete by array code
     *
     * @param $arrCode
     */
    public function deleteQuestionnaireByCodeArr($arrCode)
    {
        $condition = ['code in (?)' => $arrCode];

        $this->getConnection()->delete($this->getTable('riki_enquete'), $condition);
    }

}