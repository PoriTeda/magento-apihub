<?php

namespace Riki\SubscriptionCourse\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface;

interface CourseRepositoryInterface
{
    /**
     * @param $id
     *
     * @return SubscriptionCourseInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * @param $id
     * @return mixed
     */
    public function getCourseByCode($id);

    /**
     * @param SubscriptionCourseInterface $course
     * @return mixed
     */
    public function save(SubscriptionCourseInterface $course);
}
