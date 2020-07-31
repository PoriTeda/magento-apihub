<?php

namespace Riki\SubscriptionCourse\Cron;

use \Magento\Framework\App\Filesystem\DirectoryList;
use \Riki\SubscriptionCourse\Model\ImportHandler\SubscriptionCourse;

class ImportSubscriptionCourse
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvParser;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseRepository
     */
    protected $subscriptionCourseRepository;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subscriptionCourseModelFactory;

    protected $frequencyCollectionFactory;
    /**
     * @var \Riki\SubscriptionCourse\Logger\LoggerSubscriptionCourse
     */
    protected $logger;

    protected $rowId;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    protected $courseColumns = [
        'course_name',
        'course_code',
        'duration_unit',
        'duration_interval',
        'must_select_sku',
        'minimum_order_qty',
        'minimum_order_times',
        'sales_count',
        'application_count',
        'application_limit',
        'applied_payment_method_code',
        'membership_type_restriction',
        'description',
        'is_enable',
        'allow_skip_next_delivery',
        'launch_date',
        'close_date',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'penalty_fee',
        'allow_change_next_delivery_date',
        'allow_change_payment_method',
        'allow_change_address',
        'allow_change_product',
        'allow_change_qty',
        'sales_value_count',
        'visibility',
        'subscription_type',
        'hanpukai_type',
        'hanpukai_maximum_order_times',
        'hanpukai_delivery_date_allowed',
        'hanpukai_delivery_date_from',
        'hanpukai_delivery_date_to',
        'hanpukai_first_delivery_date',
        'navigation_path',
        'design',
        'additional_category_description',
        'point_for_trial',
        'point_for_trial_wbs',
        'point_for_trial_account_code',
        'nth_delivery_simulation',
        'is_delay_payment',
        'maximum_order_qty',
        'captured_amount_calculation_option',
        'is_shopping_point_deduction',
        'payment_delay_time',
        'subscription_course_category',
        'subscription_course_frequency',
        'subscription_course_membership',
        'subscription_course_merge_profile',
        'subscription_course_payment',
        'subscription_course_website',
        'multiple_machine',
    ];

    /**
     * ImportSubscriptionCourse constructor.
     *
     * @param  \Magento\Framework\Filesystem $filesystem
     * @param  \Magento\Framework\File\Csv $csvParser
     * @param  \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $SubscriptionCourseRepository
     * @param  \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param  \Riki\SubscriptionCourse\Logger\LoggerSubscriptionCourse $logger
     * @param  \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Csv $csvParser,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $SubscriptionCourseRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Logger\LoggerSubscriptionCourse $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Riki\SubscriptionFrequency\Model\ResourceModel\Frequency\CollectionFactory $frequencyCollectionFactory
    ) {
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->csvParser = $csvParser;
        $this->subscriptionCourseRepository = $SubscriptionCourseRepository;
        $this->subscriptionCourseModelFactory = $courseFactory;
        $this->logger = $logger;
        $this->timeZone = $timeZone;
        $this->frequencyCollectionFactory = $frequencyCollectionFactory;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info("Import Process Starts");
        $importData = $this->getImportData();
        if ($importData) {
            $this->processImport($importData);
            $this->renameImportFile();
        } else {
            $this->logger->info("There is no course to import");
        }
    }

    /**
     * Retrieve CSV file data as array
     *
     * @return array
     * @throws \Exception
     */
    public function getImportData()
    {
        $filePath = $this->varDirectory->getAbsolutePath(SubscriptionCourse::FOLDER_NAME) .
            DIRECTORY_SEPARATOR . SubscriptionCourse::FILE_NAME;
        try {
            $data = $this->csvParser->getData($filePath);
            return $this->convertToAssociativeArray($data);
        } catch (\Exception $exception) {
            $this->logger->addError($exception->getMessage());
        }
    }

    /**
     * Return Csv Data as associative array with key is header
     *
     * @param  $data
     * @return array
     */
    public function convertToAssociativeArray($data)
    {
        $importData = [];

        if ($data) {
            $header = array_shift($data);
            foreach ($data as $row) {
                $importData[] = array_combine($header, $row);
            }
        }

        return $importData;
    }

    /**
     * @param array $data
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processImport(array $data)
    {
        foreach ($data as $rowId => $rowData) {
            $this->rowId = $rowId;
            if (isset($rowData['course_code']) && $rowData['course_code']) {
                /**
                 * @var \Riki\SubscriptionCourse\Model\Course $course
                 */
                $course = $this->subscriptionCourseRepository->getCourseByCode($rowData['course_code']);
                if ($course) { //update course
                    $type = $course->getSubscriptionType();
                    if ($type != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES &&
                        $rowData['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES
                    ) {
                        $this->logger->info(
                            "Can not process, course_code [" . $rowData['course_code'] . "] in row " .
                            $rowId . " cannot be updated to multiple machine course"
                        );
                    }
                    if ($this->isChangingDelayPaymentConfig($course, $rowData['is_delay_payment'])) {
                        $rowData['is_delay_payment'] = $course->getData('is_delay_payment');
                    }
                    $this->updateCourse($course, $rowData);
                } else { //create course
                    $this->createCourse($rowData);
                }
            } else {
                $this->logger->info(
                    "Can not process, course_code [" .
                    $rowData['course_code'] . "] in row " . $rowId . " is empty or not found"
                );
            }
        }
        $this->logger->info("Import Process Done!");
    }

    /**
     * Do some custom logic
     *
     * @param  \Riki\SubscriptionCourse\Model\Course $model
     * @return bool
     */
    public function validate(\Riki\SubscriptionCourse\Model\Course $model)
    {
        $valid = true;

        $errors = $model->validate();

        if ($model->isDelayPayment() && $model->isHanpukai()) {
            $errors[] = __('Hanpukai course can not be a Delay Payment');
        }

        if ($model->isDelayPayment() && !$this->isValidFrequency($model)) {
            $errors[] = __('Delay Payment course only accepts [3,4,6] months values');
        }

        if ($errors) {
            $valid = false;
            foreach ($errors as $error) {
                $this->logger->logError('Row[' . $this->rowId . '] ' . $error);
            }
        }

        return $valid;
    }

    /**
     * Validate frequency in case of delay payment course
     * @param $model
     * @return bool
     */
    protected function isValidFrequency($model)
    {
        $acceptFrequencyIds = [];

        $frequencyCollection = $this->frequencyCollectionFactory->create();

        $frequencyItems = $frequencyCollection
            ->addFieldToFilter('frequency_unit', 'month')
            ->addFieldToFilter(
                'frequency_interval',
                \Riki\SubscriptionCourse\Model\DelayedPayment\ConfigProvider::ALLOWED_FREQUENCY_INTERVALS
            )->getItems();

        foreach ($frequencyItems as $item) {
            $acceptFrequencyIds[] = $item->getFrequencyId();
        }

        return !array_diff($model->getFrequencyIds(), $acceptFrequencyIds);
    }

    /**
     * Check if this will change delay payment config
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param $delayPaymentData
     * @return bool
     */
    public function isChangingDelayPaymentConfig(\Riki\SubscriptionCourse\Model\Course $course, $delayPaymentData)
    {
        $change = false;

        if ($course->getData('is_delay_payment') != $delayPaymentData) {
            $change = true;
            $this->logger->info(
                __(
                    'Can not update is_delay_payment configuration of course #%1',
                    $course->getCode()
                )
            );
        }

        return $change;
    }

    /**
     * Update Course
     *
     * @param $course
     * @param $courseData
     */
    public function updateCourse($course, $courseData)
    {
        foreach ($this->courseColumns as $column) {
            if (isset($courseData[$column])) {
                $this->courseSetData($course, $column, $courseData);
            }
        }
        try {
            if ($this->validate($course)) {
                $this->subscriptionCourseRepository->save($course);
                $this->logger->logSuccess(
                    "Row[" . $this->rowId . "] Subscription course [" .
                    $courseData['course_code'] . "] has been updated successfully"
                );
            } else {
                $this->logger->logError(
                    "Can not update Subscription course - validate error"
                );
            }
        } catch (\Magento\Framework\Exception\CouldNotSaveException $exception) {
            $this->logger->logError(
                "Can not update Subscription course [" .
                $courseData['course_code'] . "] - " . $exception->getMessage()
            );
        }
    }

    /**
     * Create Course
     *
     * @param $courseData
     */
    public function createCourse($courseData)
    {
        $course = $this->subscriptionCourseModelFactory->create();
        foreach ($this->courseColumns as $column) {
            if (isset($courseData[$column])) {
                $this->courseSetData($course, $column, $courseData);
            }
        }
        try {
            if ($this->validate($course)) {
                $this->subscriptionCourseRepository->save($course);
                $this->logger->logSuccess(
                    "Row[" . $this->rowId . "] Subscription course [" .
                    $courseData['course_code'] . "] has been created successfully"
                );
            } else {
                $this->logger->logError(
                    "Can not create Subscription course - validate error"
                );
            }
        } catch (\Magento\Framework\Exception\CouldNotSaveException $exception) {
            $this->logger->logError(
                "Can not create Subscription course [" .
                $courseData['course_code'] . "] - " . $exception->getMessage()
            );
        }
    }

    /**
     * Rename import file after importing
     */
    public function renameImportFile()
    {
        $nowTimezone = $this->timeZone->date()->format('YmdHis');
        $filePath = SubscriptionCourse::FOLDER_NAME . DIRECTORY_SEPARATOR . SubscriptionCourse::FILE_NAME;
        $newFileName = $nowTimezone . '.csv';
        $newFilePath = SubscriptionCourse::FOLDER_NAME . DIRECTORY_SEPARATOR . $newFileName;
        try {
            $this->varDirectory->renameFile($filePath, $newFilePath);
            $this->logger->info("File has been renamed to " . $newFileName);
        } catch (\Exception $exception) {
            $this->logger->error("Can not rename file - " . $exception->getMessage());
        }
    }

    /**
     * @param $course
     * @param $column
     * @param $courseData
     */
    public function courseSetData($course, $column, $courseData)
    {
        switch ($column) {
            case 'subscription_course_category':
                $this->setSubscriptionCourseCategory($course, $courseData[$column]);
                break;
            case 'subscription_course_frequency':
                $this->setSubscriptionCourseFrequency($course, $courseData[$column]);
                break;
            case 'subscription_course_membership':
                $this->setSubscriptionCourseMembership($course, $courseData[$column]);
                break;
            case 'subscription_course_merge_profile':
                $this->setSubscriptionCourseMergeProfile($course, $courseData[$column]);
                break;
            case 'subscription_course_payment':
                $this->setSubscriptionCoursePayment($course, $courseData[$column]);
                break;
            case 'subscription_course_website':
                $this->setSubscriptionCourseWebsite($course, $courseData[$column]);
                break;
            case 'multiple_machine':
                $this->setSubscriptionCourseMultipleMachines($course, $courseData[$column]);
                break;
            default:
                $course->setData($column, $courseData[$column]);
                break;
        }
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCourseCategory($course, $data)
    {
        $categoryData = json_decode($data, true);
        foreach ($categoryData as $categoryType => $ids) {
            if ($categoryType == 'main_categories') {
                $course->setCategoryIds($ids);
            }
            if ($categoryType == 'additional_categories') {
                $course->setAdditionalCategoryIds($ids);
            }
            if ($categoryType == 'profile_page_categories') {
                $course->setProfileCategoryIds($ids);
            }
        }
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCourseFrequency($course, $data)
    {
        $frequencyIds = json_decode($data, true);
        $course->setFrequencyIds($frequencyIds);
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCourseMembership($course, $data)
    {
        $membershipIds = json_decode($data, true);
        $course->setMembershipIds($membershipIds);
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCourseMergeProfile($course, $data)
    {
        $profileIds = json_decode($data, true);
        $course->setMergeProfileTo($profileIds);
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCoursePayment($course, $data)
    {
        $paymentIds = json_decode($data, true);
        $course->setPaymentIds($paymentIds);
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCourseWebsite($course, $data)
    {
        $websiteIds = json_decode($data, true);
        $course->setWebsiteIds($websiteIds);
    }

    /**
     * @param $course
     * @param $data
     */
    public function setSubscriptionCourseMultipleMachines($course, $data)
    {
        $typeIds = json_decode($data, true);
        $course->setMultiMachine($typeIds);
    }
}
