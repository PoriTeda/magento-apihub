<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;

/**
 * Class Point
 * @package Riki\Rma\Plugin\Rma\Model\Rma
 *
 * @deprecated
 */
class Point
{
    /**
     * @var \Riki\Rma\Helper\Point
     */
    protected $pointHelper;

    /**
     * @var \Riki\Rma\Model\Repository\RewardRepository
     */
    protected $rewardRepository;

    /**
     * Point constructor.
     *
     * @param \Riki\Rma\Model\Repository\RewardRepository $rewardRepository
     * @param \Riki\Rma\Helper\Point $pointHelper
     */
    public function __construct(
        \Riki\Rma\Model\Repository\RewardRepository $rewardRepository,
        \Riki\Rma\Helper\Point $pointHelper
    ) {
        $this->rewardRepository = $rewardRepository;
        $this->pointHelper = $pointHelper;
    }

    /**
     * Extra logic for point
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return mixed[]
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeBeforeSave(\Magento\Rma\Model\Rma $subject)
    {
        if (!$subject->dataHasChangedFor('return_status')) {
            return [];
        }

        $this->cancelPoint($subject);
        $this->revertCancelPoint($subject);
        $this->returnPoint($subject);

        return [];
    }

    /**
     * Cancel point
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelPoint(\Magento\Rma\Model\Rma $rma)
    {
        if ($rma->getData('substitution_order')) {
            return;
        }

        if ($rma->getData('return_status') != ReturnStatusInterface::APPROVED_BY_CC) {
            return;
        }

        $rmaConnection = $rma->getResource()->getConnection();
        $rewardConnection = $this->rewardRepository->createFromArray()->getResource()->getConnection();
        $needTransaction = (spl_object_hash($rmaConnection) != spl_object_hash($rewardConnection));
        if ($needTransaction) {
            $rma->getResource()
                ->addCommitCallback([$this->rewardRepository->beginTransaction(), 'commit']);
        }

        if (!$this->pointHelper->cancelPointOnConsumerDb($rma)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cancel point via consumer db failed. Please try again.'));
        }
    }

    /**
     * Revert cancel point
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function revertCancelPoint(\Magento\Rma\Model\Rma $rma)
    {
        if ($rma->getData('substitution_order')) {
            return;
        }

        if ($rma->getData('return_status') != ReturnStatusInterface::CS_FEEDBACK_REJECTED) {
            return;
        }

        $rmaConnection = $rma->getResource()->getConnection();
        $rewardConnection = $this->rewardRepository->createFromArray()->getResource()->getConnection();
        $needTransaction = (spl_object_hash($rmaConnection) != spl_object_hash($rewardConnection));
        if ($needTransaction) {
            $rma->getResource()
                ->addCommitCallback([$this->rewardRepository->beginTransaction(), 'commit']);
        }

        if (!$this->pointHelper->revertCancelPointOnConsumerDb($rma)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Revert cancel point via consumer db failed. Please try again.'));
        }
    }

    /**
     * Return point
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function returnPoint(\Magento\Rma\Model\Rma $rma)
    {
        if ($rma->getData('substitution_order')) {
            return;
        }

        if ($rma->getData('return_status') != ReturnStatusInterface::COMPLETED) {
            return;
        }

        $rmaConnection = $rma->getResource()->getConnection();
        $rewardConnection = $this->rewardRepository->createFromArray()->getResource()->getConnection();
        $needTransaction = (spl_object_hash($rmaConnection) != spl_object_hash($rewardConnection));
        if ($needTransaction) {
            $rma->getResource()
                ->addCommitCallback([$this->rewardRepository->beginTransaction(), 'commit']);
        }

        if (!$this->pointHelper->returnPointOnConsumerDb($rma)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Return point via consumer db failed. Please try again.'));
        }
    }
}