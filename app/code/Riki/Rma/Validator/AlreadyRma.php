<?php
namespace Riki\Rma\Validator;

class AlreadyRma extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{

    const WARNING = 'warning';

    /**
     * {@inherit}
     *
     * @var mixed[]
     */
    protected $_messageTemplates = [
        self::WARNING => 'Please be aware this order already have a return',
    ];

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * AlreadyRma constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
    ){
        $this->searchHelper = $searchHelper;
        $this->rmaRepository = $rmaRepository;

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

        $result = $this->searchHelper
            ->getByEntityId($value->getId(), 'neq')
            ->getByOrderId($value->getData('order_id'))
            ->getByFullPartial(\Riki\Rma\Api\Data\Rma\TypeInterface::PARTIAL)
            ->getCount()
            ->execute($this->rmaRepository);
        if ($result) {
            $this->_error(self::WARNING);
            return false;
        }

        return true;
    }

}