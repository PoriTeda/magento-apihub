<?php
namespace Riki\NpAtobarai\Model\ResourceModel;

use Exception;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Transaction extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var
     */
    protected $connectionName = 'sales';

    /**
     * @var TransactionAttribute
     */
    protected $attribute;

    /**
     * Transaction constructor.
     *
     * @param Context $context
     * @param TransactionAttribute $attribute
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        TransactionAttribute $attribute,
        $connectionName = null
    ) {
        $this->attribute = $attribute;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_np_atobarai_transaction', 'transaction_id');
    }

    /**
     * @param \Riki\NpAtobarai\Model\Transaction $object
     * @param string|array $attribute
     *
     * @return $this
     * @throws Exception
     */
    public function saveAttribute(\Riki\NpAtobarai\Model\Transaction $object, $attribute)
    {
        $this->attribute->saveAttribute($object, $attribute);
        return $this;
    }
}
