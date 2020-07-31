<?php
namespace Riki\Rma\Validator;

use Riki\Rma\Api\ConfigInterface;

class ExceedShipmentFeeRma extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const WARNING = 'warning';

    /**
     * {@inherit}
     *
     * @var mixed[]
     */
    protected $_messageTemplates = [
        self::WARNING => 'Please check if the additional fee 0 needs to apply.',
    ];

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * ExceedShipmentFeeRma constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
    ) {
        $this->dataHelper = $dataHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->amountHelper = $amountHelper;
        $this->searchHelper = $searchHelper;
        $this->reasonRepository = $reasonRepository;

        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        /** @var \Magento\Rma\Model\Rma $value */
        if (!$value instanceof \Magento\Rma\Model\Rma) {
            $this->_error(self::WARNING);
            return false;
        }

        if ($value->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL) {
            return true;
        }

        $order = $this->dataHelper->getRmaOrder($value);
        if (!$order instanceof \Riki\Sales\Model\Order) {
            return true;
        }

        if (floatval($order->getShippingAmount())) {
            return true;
        }

        $reason = $this->searchHelper
            ->getById($value->getData('reason_id'))
            ->getOne()
            ->execute($this->reasonRepository);
        if (!$reason instanceof \Riki\Rma\Model\Reason) {
            return true;
        }

        if ($reason->getData('due_to') != \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER) {
            return true;
        }

        $remainingLimit = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->rma()
            ->returnAmount()
            ->remainingAmountLimit();
        $remainingLimit = floatval($remainingLimit?:0);
        $remainingAmount = floatval($this->amountHelper->getReturnableItemsTotalAmount($value));
        if ($remainingAmount >= $remainingLimit) {
            return true;
        }

        $this->_error(self::WARNING);

        return false;
    }

}