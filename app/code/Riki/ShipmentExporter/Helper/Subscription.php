<?php
/**
 * Subscription Helper
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShipmentExporter\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Riki\Subscription\Model\Profile\WebApi\ProfileRepository;
use Riki\SubscriptionCourse\Model\CourseFactory;
use Magento\Framework\App\ResourceConnection;
/**
 * Class Subscription Helper
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Subscription extends AbstractHelper
{
    CONST CVS_PAYMENT_CODE = 3;
    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $scopeConfig;
    /**
     * @var ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var CourseFactory
     */
    protected $courseFactory;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * Subscription constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ProfileRepository $profileRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ProfileRepository $profileRepository,
        CourseFactory $courseFactory,
        ResourceConnection $resourceConnection
    )
    {
        $this->scopeConfig = $context;
        $this->profileRepository = $profileRepository;
        $this->courseFactory = $courseFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * @param $profileId
     * @return \Riki\Subscription\Api\Data\ProfileInterface|\Riki\Subscription\Model\Profile\Profile
     * @throws \Exception
     */
    public function getSubscriptionProfile($profileId) {
        try {
            return $this->profileRepository->load($profileId);
        } catch(\Exception $e)
        {
            $this->_logger->info($e->getMessage());
        }


    }

    /**
     * @param $courseId
     * @return $this
     */
    public function getCourse($courseId) {
        return $this->courseFactory->create()->load($courseId);
    }

    /**
     * @param $courseId
     * @return string
     */
    public function getDeliveryChoice($courseId)
    {
        if($courseId)
        {
            $connection = $this->resourceConnection->getConnection('sales');
            $tableName = $connection->getTableName('subscription_course_payment');
            $sql = "select * from $tableName where `course_id` = $courseId AND `payment_id`= ".self::CVS_PAYMENT_CODE;
            $res = $connection->fetchOne($sql);
            if($res)
            {
                return '9001';
            }
            else
            {
                return '';
            }
        }
        else
        {
            return '';
        }
    }

    /**
     * @param $regionCode
     * @return string
     */
    public function getRegionNameByCode($regionCode)
    {
        if($regionCode)
        {
            $connection = $this->resourceConnection->getConnection('default');
            $tableRE = $connection->getTableName('directory_country_region');
            $tableReN = $connection->getTableName('directory_country_region_name');
            $sql = "select `rem`.name from $tableRE as `re` INNER JOIN $tableReN 
                as `rem` ON `re`.region_id = `rem`.region_id where `locale`='ja_JP' AND re.code = '$regionCode' ";
            return $connection->fetchOne($sql);
        }else
        {
            return '';
        }

    }
}