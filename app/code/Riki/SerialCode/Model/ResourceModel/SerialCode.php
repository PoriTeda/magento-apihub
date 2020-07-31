<?php

namespace Riki\SerialCode\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SerialCode extends AbstractDb
{
    const CODE_LENGTH = 12;

    protected $_idFieldName = 'id';
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $random;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Math\Random $random
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Math\Random $random,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dateTime = $dateTime;
        $this->random = $random;
    }

    /**
     * Initialize connection and define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_serial_code', 'id');
    }

    /**
     * @param $number
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function buildRandomString($number)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable(), ['serial_code']);
        $codeExisted = $connection->fetchAll($select);
        if ($codeExisted) {
            $codeExisted = array_map(function($value) {return $value['serial_code'];}, $codeExisted);
        }
        $randResult = [];
        $numberGenerated = 0;
        while ($numberGenerated < $number) {
            //\Magento\Framework\Math\Random::CHARS_DIGITS if generate number
            $random = $this->random->getRandomString(self::CODE_LENGTH);
            if (!in_array($random, $codeExisted) && !in_array($random, $randResult)) {
                $randResult[] = $random;
                $numberGenerated++;
            }
        }
        return $randResult;
    }

    /**
     * @return bool|int
     */
    public function generateSerialCode(\Riki\SerialCode\Model\SerialCode $object)
    {
        $number = (int) $object->getData('number_of_generate');
        if (!$number) {
            return false;
        }
        $data = [];
        foreach (['activation_date', 'expiration_date'] as $field) {
            $value = !$object->getData($field) ? null : $object->getData($field);
            $object->setData($field, $this->dateTime->formatDate($value));
        }
        $activationDate = $object->getData('activation_date');
        $expirationDate = $object->getData('expiration_date');
        $pointPeriod = (int) $object->getData('point_expiration_period');
        $randNumber = $this->buildRandomString((int) $number);
        $campaignId = $object->getData('campaign_id');
        $campaignLimit = $object->getData('campaign_limit');
        $serialCode = [
            'issued_point' => $object->getData('issued_point'),
            'wbs' => $object->getData('wbs'),
            'account_code' => $object->getData('account_code'),
            'activation_date' => $activationDate,
            'expiration_date' => $expirationDate,
            'campaign_id' => $campaignId,
            'campaign_limit' => $campaignLimit ? $campaignLimit : null,
            'point_expiration_period' => $pointPeriod ? $pointPeriod : null
        ];
        foreach ($randNumber as $rand) {
            $serialCode['serial_code'] = $rand;
            $data[] = $serialCode;
        }
        $columns = array_keys($data[0]);
        return $this->getConnection()->insertArray(
            $this->getMainTable(),
            $columns,
            $data
        );
    }

    /**
     * Count serial code used with the same $campaignId
     *
     * @param string $campaignId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function campaignUsed($campaignId)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from($this->getMainTable(), ['total' => new \Zend_Db_Expr('COUNT("id")')]);
        $sqlSelect->where('campaign_id = ?', $campaignId);
        $sqlSelect->where('status = ?', \Riki\SerialCode\Model\Source\Status::STATUS_USED);
        return (int) $connection->fetchOne($sqlSelect);
    }

    /**
     * @param string $serialCode
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadBySerialCode($serialCode)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("serial_code = ?", $serialCode);
        $sql = $this->getConnection()
            ->select()
            ->from($table, ['id'])
            ->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }

}