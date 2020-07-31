<?php
namespace Riki\Framework\Helper\Importer;

abstract class Csv
{
    /**
     * @var mixed[]
     */
    protected $messages;

    /**
     * @var mixed[]
     */
    protected $columns;

    /** @var  \Magento\Framework\Model\ResourceModel\Db\AbstractDb */
    protected $db;

    /**
     * @var mixed[]
     */
    protected $filters;

    /**
     * @var mixed[]
     */
    protected $validators;

    /**
     * Csv constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->messages = [];
        $this->columns = [];
        $this->validators = [];
        $this->filters = [];
    }

    /**
     * Get db
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Set db
     *
     * @param $db
     *
     * @return $this
     */
    public function setDb($db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * Add message
     *
     * @param $message
     * @param null $group
     *
     * @return $this
     */
    public function addMessage($message, $group = null)
    {

        $this->messages[] = [
            ($group ?: 'default') => $message
        ];

        return $this;
    }

    /**
     * Clear messages
     *
     * @return $this
     */
    public function clearMessages()
    {
        $this->messages = [];
        return $this;
    }

    /**
     * Get messages
     *
     * @param null $group
     *
     * @return array
     */
    public function getMessages($group = null)
    {
        return $group ? array_column($this->messages, $group) : $this->messages;
    }

    /**
     * Validate data
     *
     * @param $data
     *
     * @return bool|mixed[]
     */
    public function isValid($data)
    {
        $valid = $filteredData = $this->filter($data);
        if (!$valid) {
            return false;
        }
        foreach ($this->validators as $validatorData) {
            /** @var \Magento\Framework\Validator\AbstractValidator $validator */
            $validator = $validatorData['validator'];
            $invalidMessage = [
                'message' => null,
                'columns' => []
            ];
            foreach ($validatorData['columns'] as $column) {
                if (isset($filteredData[$column]) && $validator->isValid($filteredData[$column])) {
                    continue;
                }
                if (!$invalidMessage['message']) {
                    $invalidMessage['message'] = implode(', ', $validator->getMessages());
                }
                $invalidMessage['columns'][] = $column;
            }
            if ($invalidMessage['message']) {
                $this->addMessage($invalidMessage, 'error');
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     *
     *
     * @param $data
     *
     * @return mixed
     */
    public function filter($data)
    {
        if (count($this->columns) != count($data)) {
            $this->addMessage('Invalid format: columns count does not match', 'error');
            return false;
        }
        $data = array_combine($this->columns, $data);
        foreach ($this->filters as $filterData) {
            $filter = $filterData['filter'];
            foreach ($filterData['columns'] as $column) {
                if ($filter == 'filterNullExpr') {
                    $data[$column] = $this->filterNullExpr($data[$column]);
                }
            }
        }

        return $data;
    }

    /**
     * @param $value
     *
     * @return \Zend_Db_Expr
     */
    public function filterNullExpr($value)
    {
        if (!strlen($value) || $value == 'NULL') {
            return new \Zend_Db_Expr('NULL');
        }

        return $value;
    }


    /**
     * Import
     *
     * @param $data
     *
     * @return bool
     */
    public function import($data)
    {
        $connection = $this->db->getConnection();
        $connection->insertOnDuplicate($this->db->getMainTable(), $data, $this->columns);

        return true;
    }
}