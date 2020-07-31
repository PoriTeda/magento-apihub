<?php

namespace Riki\CatalogRule\ViewModel\Rule;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class FormScript implements ArgumentInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    private $courseModel;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->courseModel = $courseModel;
        $this->serializer = $serializer;
    }


    public function getCourseFrequencyListJson()
    {
        return $this->serializer->serialize($this->courseModel->getCourseFrequencyList());
    }
}