<?php

namespace Riki\SubscriptionCourse\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface;

class CourseRepository implements \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var array
     */
    protected $loaded = [];

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $resourceModel;

    /**
     * @var ResourceModel\Course\CollectionFactory
     */
    protected $courseCollectionFactory;

    /**
     * CourseRepository constructor.
     * @param CourseFactory $courseFactory
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $resourceModel,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $courseCollection

    ) {
        $this->courseFactory = $courseFactory;
        $this->resourceModel = $resourceModel;
        $this->courseCollectionFactory = $courseCollection;
    }

    /**
     * @param $id
     * @return \Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface
     * @throws NoSuchEntityException
     */
    public function get($id)
    {

        if (!isset($this->loaded[$id])) {
            /** @var \Riki\SubscriptionCourse\Model\Course $course */
            $course = $this->courseFactory->create()->load($id);

            if (!$course || !$course->getId()) {
                throw new NoSuchEntityException(__('Requested entity doesn\'t exist'));
            }

            $this->loaded[$id] = $course;
        }

        return $this->loaded[$id];
    }

    /**
     * @param $courseCode
     * @return null|SubscriptionCourseInterface
     * @throws NoSuchEntityException
     */
    public function getCourseByCode($courseCode)
    {
        if (!$courseCode) {
            return null;
        }

        $courseCollection = $this->courseCollectionFactory->create();
        $subscriptionCourse = $courseCollection
            ->addFieldToFilter('course_code', $courseCode)
            ->setPageSize(1)
            ->getFirstItem();
        $course = $subscriptionCourse->getId() ? $this->get($subscriptionCourse->getId()) : null;

        return $course;
    }

    /**
     * @param SubscriptionCourseInterface $course
     * @return mixed|void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(SubscriptionCourseInterface $course)
    {
        try {
            $this->resourceModel->save($course);
        } catch (\Magento\Framework\Exception\CouldNotSaveException $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__($exception->getMessage()));
        }
    }
}
