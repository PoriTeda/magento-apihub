<?php
namespace Riki\Subscription\Helper\Adminhtml;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $subscriptionCourseResource;

    protected $frequencyFactory;

    protected $courseIdsToNames = [];

    protected $_session;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResource,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ) {
        $this->subscriptionCourseResource = $courseResource;
        $this->_session = $sessionQuote;
        $this->frequencyFactory = $frequencyFactory;

        parent::__construct($context);
    }

    /**
     * @param $courseId
     * @return mixed
     */
    public function getCourseNameById($courseId)
    {
        if (!isset($this->courseIdsToNames[$courseId])) {
            $courseName = '';
            try {
                $courseData = $this->subscriptionCourseResource->getCourseInfoById($courseId, ['course_name']);
                if ($courseData) {
                    $courseName = $courseData['course_name'];
                }
            } catch (\Exception $e) {
                $courseName = '';
            }

            $this->courseIdsToNames[$courseId] = $courseName;
        }

        return $this->courseIdsToNames[$courseId];
    }

    /**
     * @param $courseId
     * @return mixed
     */
    public function getCourseTypeById($courseId)
    {
        try {
            $courseData = $this->subscriptionCourseResource->getCourseInfoById($courseId, ['subscription_type']);
            if ($courseData) {
                $type = $courseData['subscription_type'];
            }
        } catch (\Exception $e) {
            $type = '';
        }

        return $type;
    }

    /**
     * @return mixed|null
     */
    public function getCourseNameByCurrentQuote()
    {
        $quote = $this->_session->getQuote();

        if ($courseId = $quote->getRikiCourseId()) {
            return $this->getCourseNameById($courseId);
        }

        return null;
    }

    public function getCourseTypeByCurrentQuote()
    {
        $quote = $this->_session->getQuote();

        if ($courseId = $quote->getRikiCourseId()) {
            return $this->getCourseTypeById($courseId);
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getFrequencyNameByCurrentQuote()
    {
        $quote = $this->_session->getQuote();
        if ($frequencyId = $quote->getRikiFrequencyId()) {
            /** @var \Riki\Subscription\Model\Frequency\Frequency $frequency */
            $frequency = $this->frequencyFactory->create();
            $frequency->load($frequencyId);

            return $frequency->getFrequencyInterval() . ' ' . $frequency->getFrequencyUnit();
        }
        return null;
    }
}
