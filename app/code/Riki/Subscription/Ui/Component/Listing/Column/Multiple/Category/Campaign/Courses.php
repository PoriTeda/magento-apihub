<?php
namespace Riki\Subscription\Ui\Component\Listing\Column\Multiple\Category\Campaign;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Riki\SubscriptionCourse\Api\CourseRepositoryInterface;

/**
 * Class Courses
 * @package Riki\Subscription\Ui\Component\Listing\Column
 */
class Courses extends Column
{
    /**
     * Class container
     *
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var CourseRepositoryInterface
     */
    protected $courseRepository;
    /**
     * @var \Riki\Subscription\Model\Multiple\Category\CampaignFactory
     */
    protected $campaignFactory;

    /**
     * Courses constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param CourseRepositoryInterface $courseRepository
     * @param \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->courseRepository = $courseRepository;
        $this->campaignFactory = $campaignFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['campaign_id'])) {
                continue;
            }
            $campaignModel = $this->campaignFactory->create()->load($item['campaign_id']);
            $courses = $campaignModel->getCourseIds();
            if ($courses) {
                $coursesCode = [];
                foreach ($courses as $courseId) {
                    $coursesCode[] = $this->courseRepository->get($courseId)->getData('course_code');
                }
                $item['course_ids'] = implode(', ', $coursesCode);
            }
        }
        return $dataSource;
    }
}
