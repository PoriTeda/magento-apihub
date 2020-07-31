<?php
namespace Riki\MasterDataHistory\Observer\Subscription;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
use Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory;

/**
 * Class CourceSaveAfter
 * @package Riki\MasterDataHistory\Observer\Subscription
 */
class CourseSaveAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-subcourse';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistorysubscription';
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_subscriptionCource;
    /**
     * @var CollectionFactory
     */
    protected $_subscriptionCourceCollection;
    /**
     * @var string
     */
    protected $_dirLocal;
    /**
     * @var string
     */
    protected $_timeStamp;
    /**
     * @var \Magento\Framework\App\ResourceConnection $_resourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * CourceSaveAfter constructor.
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $collectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\SubscriptionCourse\Model\Course $course,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_subscriptionCourceCollection = $collectionFactory;
        $this->_subscriptionCource = $course;
        $this->_connection = $resourceConnection->getConnection('sales');
        parent::__construct($directoryList, $file, $csv, $timezone, $authSession, $scopeConfig, $request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_initTimestamp();
        $this->_initCreateDirFile();
        if ($this->_dirLocal) {
            $courseIds = $this->_request->getParam('selected');
            if (is_array($courseIds) && $courseIds) {
                // Function export multi report subscription cource
                $this->createMultiCourceCsv($courseIds);
            } else {
                $course = $observer->getCourse();
                $courseId = $course->getId();
                $courseIds[] = $courseId;
                $hanpukaiType = $this->_typeSubscription($courseId);
                switch ($hanpukaiType) {
                    case 'hfixed':
                        $this->createCsvFixed($courseIds);
                        $this->createCsvCourceProduct($courseIds);
                        break;
                    case 'hsequence':
                        $this->createCsvSequence($courseIds);
                        $this->createCsvCourceProduct($courseIds);
                        break;
                    default:
                        $this->createCsvCourseCategory($courseIds);
                }
                $this->createCsvSubscriptionCource($observer);
            }
            $this->createCsvCourceWebsite($courseIds);
            $this->createCsvCourceMemberShip($courseIds);
            $this->createCsvCourceFrequency($courseIds);
            $this->createCsvCourcePayment($courseIds);
        }
    }

    /**
     * @return string
     */
    private function getActionNameCourse()
    {

        if ($this->_request->getParam('course_id') || $this->_request->getParam('selected')) {
            return 'Update';
        } else {
            return 'Add';
        }
    }

    /**
     * @param $observer
     * @return mixed
     */
    public function createCsvSubscriptionCource($observer)
    {

        $courseData = [];
        $course = $observer->getCourse();
        //add more column Data
        $addMoreData = $this->addMoreData();

        $headerCource = $this->_prepareHeader();

        foreach ($headerCource as $attribute) {
            if (!is_array($course->getData($attribute))) {
                $courseData[] = $course->getData($attribute);
            } else {
                $courseData[] = '';
            }
        }

        $header[] = array_merge($headerCource, array_keys($addMoreData));

        $dataExport[] = array_merge($courseData, array_values($addMoreData));

        $prepareData = array_merge($header, $dataExport);

        $nameCsv = $this->_timeStamp . '-subscription-course.csv';

        $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);

        return $course->getId();
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourseCategory($courseId = [])
    {
        $data = [];
        $select = $this->_connection->select();
        $select->from('subscription_course_category', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        $headerCatagory = [
            'course_id',
            'category_id',
        ];
        $addMoreData = $this->addMoreData();
        if ($result) {
            foreach ($result as $category) {
                $dataCatagory = [
                    $category['course_id'],
                    $category['category_id'],
                ];
                $data[] = array_merge($dataCatagory, array_values($addMoreData));
            }
        }
        $header[] = array_merge($headerCatagory, array_keys($addMoreData));
        $prepareData = array_merge($header, $data);
        $nameCsv = $this->_timeStamp . '-subscription-course-category-' . $this->_datetime->date()->format('Y-m-d') . '.csv';
        $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourceWebsite($courseId = [])
    {
        $select = $this->_connection->select();
        $select->from('subscription_course_website', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        if ($result) {
            $addMoreData = $this->addMoreData();
            $headerWebsite = [
                'course_id',
                'website_id',
            ];
            foreach ($result as $website) {
                $dataWebsite = [
                    $website['course_id'],
                    $website['website_id'],
                ];
                $dataMerge[] = array_merge($dataWebsite, array_values($addMoreData));
            }

            $header[] = array_merge($headerWebsite, array_keys($addMoreData));
            $prepareData = array_merge($header, $dataMerge);
            $nameCsv = $this->_timeStamp . '-subscription-course-website.csv';
            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourceProduct($courseId = [])
    {
        $select = $this->_connection->select();
        $select->from('subscription_course_product', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        if ($result) {
            $addMoreData = $this->addMoreData();
            $headerWebsite = [
                'product_id',
                'course_id',
                'priority',
            ];
            foreach ($result as $website) {
                $dataWebsite = [
                    $website['product_id'],
                    $website['course_id'],
                    $website['priority'],
                ];
                $dataMerge[] = array_merge($dataWebsite, array_values($addMoreData));
            }

            $header[] = array_merge($headerWebsite, array_keys($addMoreData));
            $prepareData = array_merge($header, $dataMerge);
            $nameCsv = $this->_timeStamp . '-subscription-course-product.csv';
            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourceMemberShip($courseId = [])
    {
        $select = $this->_connection->select();
        $select->from('subscription_course_membership', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        if ($result) {
            $addMoreData = $this->addMoreData();
            $headerMemberShip = [
                'course_id',
                'membership_id',
            ];
            foreach ($result as $membership) {
                $dataMemberShip = [
                    $membership['course_id'],
                    $membership['membership_id'],
                ];
                $dataMerge[] = array_merge($dataMemberShip, array_values($addMoreData));
            }
            $header[] = array_merge($headerMemberShip, array_keys($addMoreData));
            $prepareData = array_merge($header, $dataMerge);

            $nameCsv = $this->_timeStamp . '-subscription-course-membership.csv';
            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourceFrequency($courseId = [])
    {
        $select = $this->_connection->select();
        $select->from('subscription_course_frequency', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        if ($result) {
            $addMoreData = $this->addMoreData();
            $headerFrequency = [
                'course_id',
                'frequency_id',
            ];
            foreach ($result as $frequency) {
                $dataFrequency = [
                    $frequency['course_id'],
                    $frequency['frequency_id'],
                ];
                $dataMerge[] = array_merge($dataFrequency, array_values($addMoreData));
            }

            $header[] = array_merge($headerFrequency, array_keys($addMoreData));
            $prepareData = array_merge($header, $dataMerge);
            $nameCsv = $this->_timeStamp . '-subscription-course-frequency.csv';
            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourcePayment($courseId = [])
    {
        $select = $this->_connection->select();
        $select->from('subscription_course_payment', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        if ($result) {
            $addMoreData = $this->addMoreData();
            $headerPayment = [
                'course_id',
                'payment_id',
            ];
            foreach ($result as $payment) {
                $dataPayment = [
                    $payment['course_id'],
                    $payment['payment_id'],
                ];
                $dataMerge[] = array_merge($dataPayment, array_values($addMoreData));
            }
            $header[] = array_merge($headerPayment, array_keys($addMoreData));
            $prepareData = array_merge($header, $dataMerge);
            $nameCsv = $this->_timeStamp . '-subscription-course-payment.csv';
            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param array $courseId
     */
    public function createCsvCourceMergeProfile($courseId = [])
    {
        $select = $this->_connection->select();
        $select->from('subscription_course_merge_profile', '*')
            ->where('course_id in (?)', $courseId);
        $result = $this->_connection->fetchAll($select);
        if ($result) {
            $addMoreData = $this->addMoreData();
            $headerMergeProfile = [
                'course_id',
                'merge_profile_to',
            ];
            foreach ($result as $mergeProfile) {
                $dataMergeProfile = [
                    $mergeProfile['course_id'],
                    $mergeProfile['merge_profile_to'],
                ];
                $dataMerge[] = array_merge($dataMergeProfile, array_values($addMoreData));
            }
            $header[] = array_merge($headerMergeProfile, array_keys($addMoreData));
            $prepareData = array_merge($header, $dataMerge);
            $nameCsv = $this->_timeStamp . '-subscription-course-merge_profile.csv';
            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param $courseIds
     */
    public function createMultiCourceCsv($courseIds)
    {
        $arraySubsripionCourse = $arraySubscriptionHFixed = $arraySubscriptionSequence = [];
        $collection = $this->_subscriptionCourceCollection->create();
        $collection->addFieldToFilter('course_id', ['in' => $courseIds]);
        if ($collection->getSize()) {
            //add more column Data
            $addMoreData = $this->addMoreData();
            $headerCource = $this->_prepareHeader();
            foreach ($collection->getItems() as $course) {
                $courseData = [];
                $hanpuKaiType = $course->getData('hanpukai_type');
                if ($hanpuKaiType == 'hsequence') {
                    $arraySubscriptionSequence[] = $course->getId();
                } elseif ($hanpuKaiType == 'hfixed') {
                    $arraySubscriptionHFixed[] = $course->getId();
                } else {
                    $arraySubsripionCourse[] = $course->getId();
                }

                foreach ($headerCource as $attribute) {
                    if (!is_array($course->getData($attribute))) {
                        $courseData[] = $course->getData($attribute);
                    } else {
                        $courseData[] = '';
                    }
                    $dataMerge = array_merge($courseData, array_values($addMoreData));
                }
                $courseDatas[] = $dataMerge;
            }

            $header[] = array_merge($headerCource, array_keys($addMoreData));

            $prepareData = array_merge($header, $courseDatas);

            $nameCsv = $this->_timeStamp . '-subscription-course.csv';

            $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);

            if ($arraySubscriptionSequence) {
                $this->createCsvSequence($courseIds);
            }

            if ($arraySubscriptionHFixed) {
                $this->createCsvFixed($courseIds);
            }

            if ($arraySubsripionCourse) {
                $this->createCsvCourseCategory($arraySubsripionCourse);
            }
        }
    }

    /**
     * @param $courseIds
     */
    public function createCsvFixed($courseIds)
    {
        $dataHanpukaiFixeds = $prepareData = [];
        $select = $this->_connection->select();
        $select->from('hanpukai_fixed', '*')
            ->where('course_id in (?)', $courseIds);
        $result = $this->_connection->fetchAll($select);
        $headerHanpukaiFixed = $this->_prepareHeaderHanpukaiFixed();
        $addMoreData = $this->addMoreData();
        if ($result) {
            foreach ($result as $hanpukaiFixed) {
                $dataHanpukaiFixed = [];
                foreach ($headerHanpukaiFixed as $column) {
                    $dataHanpukaiFixed[] = $hanpukaiFixed[$column];
                }
                $dataHanpukaiFixeds[] = array_merge($dataHanpukaiFixed, array_values($addMoreData));
            }
        }
        $header[] = array_merge($headerHanpukaiFixed, array_keys($addMoreData));
        $prepareData = array_merge($header, $dataHanpukaiFixeds);
        $nameCsv = $this->_timeStamp . '-hanpukai-fixed.csv';
        $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
    }

    /**
     * @param $courseIds
     */
    public function createCsvSequence($courseIds)
    {
        $dataHanpukaiFixeds = $prepareData = [];
        $select = $this->_connection->select();
        $select->from('hanpukai_sequence', '*')
            ->where('course_id in (?)', $courseIds);
        $result = $this->_connection->fetchAll($select);
        $headerHanpukaiSequence = $this->_prepareHeaderHanpukaiSequence();
        $addMoreData = $this->addMoreData();
        if ($result) {
            foreach ($result as $hanpukaiFixed) {
                $dataHanpukaiFixed = [];
                foreach ($headerHanpukaiSequence as $column) {
                    $dataHanpukaiFixed[] = $hanpukaiFixed[$column];
                }
                $dataHanpukaiFixeds[] = array_merge($dataHanpukaiFixed, array_values($addMoreData));
            }
        }
        $header[] = array_merge($headerHanpukaiSequence, array_keys($addMoreData));
        $prepareData = array_merge($header, $dataHanpukaiFixeds);
        $nameCsv = $this->_timeStamp . '-hanpukai-sequence.csv';
        $this->_csv->saveData($this->_dirLocal . DS . $nameCsv, $prepareData);
    }

    private function _prepareHeader()
    {
        $resource = $this->_subscriptionCource->getResource();
        $connection = $resource->getConnection();
        $describle = $connection->describeTable($resource->getMainTable());
        return array_keys($describle);
    }

    private function _prepareHeaderHanpukaiFixed()
    {
        $tableHanpukaiFixed = $this->_connection->describeTable('hanpukai_fixed');
        return array_keys($tableHanpukaiFixed);
    }

    private function _prepareHeaderHanpukaiSequence()
    {
        $tableHanpukaiSequence = $this->_connection->describeTable('hanpukai_sequence');
        return array_keys($tableHanpukaiSequence);
    }

    public function _typeSubscription($courseId)
    {
        $course = $this->_subscriptionCource->load($courseId);
        if ($course->getData('hanpukai_type')) {
            return $course->getData('hanpukai_type');
        }
        return '';
    }

    private function _initTimestamp()
    {
        $this->_timeStamp = $this->_datetime->date()->getTimestamp();
    }

    protected function _initCreateDirFile()
    {
        //if this is action mass action change status
        if ($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)) {
            $dirFile = $configFolder;
        } else {
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $this->_dirLocal = $this->createFileLocal($dirFile);
    }

    private function addMoreData()
    {
        return [
            'user' => $this->getCurrentUserAdmin(),
            'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
            'action' => $this->getActionNameCourse()
        ];
    }
}
