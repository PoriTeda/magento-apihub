<?php
namespace Riki\Rma\Validator;

class ReplacementOrderRma extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const WARNING = 'warning';

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * ReplacementOrderRma constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->searchHelper = $searchHelper;
        $this->orderRepository = $orderRepository;

        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());

        $this->_messageTemplates = [
            self::WARNING => __('There is a replacement order %1 related to this order', '%value%')
        ];
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

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->searchHelper
            ->getByOriginalOrderId($value->getOrderIncrementId())
            ->getOne()
            ->execute($this->orderRepository);
        if (!$order) {
            return true;
        }

        $this->_error(self::WARNING, $order->getIncrementId());

        return false;
    }

}