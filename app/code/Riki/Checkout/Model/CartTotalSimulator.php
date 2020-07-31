<?php

namespace Riki\Checkout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class CartTotalSimulator implements \Riki\Checkout\Api\CartTotalSimulatorInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\Checkout\Model\QuoteSimulator
     */
    protected $quoteSimulator;

    /**
     * @var \Riki\Checkout\Api\Data\CartSimulationTotalsInterfaceFactory
     */
    protected $cartSimulationTotalsFactory;

    /**
     * CartTotalSimulator constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\Checkout\Model\QuoteSimulator $quoteSimulator
     * @param \Riki\Checkout\Api\Data\CartSimulationTotalsInterfaceFactory $cartSimulationTotalsFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\Checkout\Model\QuoteSimulator $quoteSimulator,
        \Riki\Checkout\Api\Data\CartSimulationTotalsInterfaceFactory $cartSimulationTotalsFactory
    )
    {
        $this->cartRepository = $cartRepository;
        $this->courseRepository = $courseRepository;
        $this->quoteSimulator = $quoteSimulator;
        $this->cartSimulationTotalsFactory = $cartSimulationTotalsFactory;
    }

    /**
     * @inheritdoc
     */
    public function simulateSubscriptionCart($cartId)
    {
        try {
            $quote = $this->cartRepository->getActive($cartId);
        } catch (NoSuchEntityException $e) {
            $quote = null;
        }

        if (!$quote) {
            return [];
        }

        if ($courseId = $quote->getRikiCourseId()) {
            try {
                $course = $this->courseRepository->get($courseId);
            } catch (NoSuchEntityException $e) {
                $course = null;
            }
        }

        if (!isset($course) || !$course->getNthDeliverySimulation()) {
            return [];
        }

        $result = [];
        for ($i = 2; $i <= $course->getNthDeliverySimulation(); $i++) {
            if ($quote = $this->quoteSimulator->simulateQuoteByNthDelivery($quote->getId(), $i)) {
                $cartSimulationTotals = $this->cartSimulationTotalsFactory->create();
                $cartSimulationTotals->setOrderTimes($i);
                $cartSimulationTotals->setGrandTotal($quote->getGrandTotal());

                $result[] = $cartSimulationTotals;
            } else {
                throw new LocalizedException(__('An error has occurred.'));
            }
        }

        return $result;
    }
}
