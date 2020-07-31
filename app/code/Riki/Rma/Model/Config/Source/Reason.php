<?php

namespace Riki\Rma\Model\Config\Source;

class Reason extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * Reason constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
    ) {
        $this->reasonRepository = $reasonRepository;
        $this->searchHelper = $searchHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $result = [];
        $reasons = $this->searchHelper
            ->getByDeleted(0)
            ->getAll()
            ->execute($this->reasonRepository);
        /** @var \Riki\Rma\Model\Reason $reason */
        foreach ($reasons as $reason) {
            $result[$reason->getId()] = $reason->getCode() . ' - ' . $reason->getDescription();
        }

        return $result;
    }
}
