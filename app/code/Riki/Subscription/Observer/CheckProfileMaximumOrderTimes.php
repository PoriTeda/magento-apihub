<?php
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Config\Source\Profile\Status;
use Riki\SubscriptionCourse\Model\Course\Type;

class CheckProfileMaximumOrderTimes implements ObserverInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $logger;

    /**
     * CheckProfileMaximumOrderTimes constructor.
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\Subscription\Logger\LoggerOrder $logger
     */
    public function __construct(
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\Subscription\Logger\LoggerOrder $logger
    ) {
        $this->courseRepository = $courseRepository;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getEvent()->getSubscriptionProfile();

        if ($profile->dataHasChangedFor('order_times')) {
            $course = $this->loadCourse($profile->getCourseId());

            if ($course->getSubscriptionType() == Type::TYPE_HANPUKAI) {
                $maximumOrderTimes = (int)$course->getHanpukaiMaximumOrderTimes();
                if ($maximumOrderTimes
                    && $maximumOrderTimes <= (int)$profile->getData('order_times')
                ) {
                    $profile->setStatus(Status::COMPLETED);
                    $this->logger->info(
                        __(
                            'The profile #%1 has been completed by maximum order times of course is %2',
                            $profile->getId(),
                            $maximumOrderTimes
                        )
                    );
                }
            }
        }
    }

    /**
     * @param $courseId
     * @return \Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface
     * @throws LocalizedException
     */
    private function loadCourse($courseId)
    {
        try {
            $course = $this->courseRepository->get($courseId);
        } catch (\Exception $e) {
            throw new LocalizedException(__('The profile have invalid course ID #1', $courseId));
        }

        return $course;
    }
}