<?php

namespace Riki\SubscriptionCutOffEmail\Cron;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

class SendCutOffEmail
{
    protected $_helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate
     */
    protected $emailCutOffDate;

    /**
     * @var \Riki\SubscriptionCutOffEmail\Model\EmailCutOffDateRepository
     */
    protected $emailCutOffDateRepository;

    /**
     * @var \Riki\SubscriptionCutOffEmail\Api\Data\EmailCutOffDateInterface
     */
    protected $emailCutOffDateInterface;

    protected $resourceConnection;
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $directoryWrite;
    protected $listProfileSendMail = [];

    /**
     * @var \Riki\SubscriptionCutOffEmail\Logger\SendCutOffEmailLogger
     */
    protected $sendCutOffEmailLogger;

    /**
     * SendCutOffEmail constructor.
     *
     * @param \Riki\SubscriptionCutOffEmail\Helper\Data $helper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate $emailCutOffDate
     * @param \Riki\SubscriptionCutOffEmail\Model\EmailCutOffDateRepository $emailCutOffDateRepository
     * @param \Riki\SubscriptionCutOffEmail\Api\Data\EmailCutOffDateInterface $emailCutOffDateInterface
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $stdTimezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param Filesystem $filesystem
     * @param \Riki\SubscriptionCutOffEmail\Logger\SendCutOffEmailLogger $sendCutOffEmailLogger
     */
    public function __construct(
        \Riki\SubscriptionCutOffEmail\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate $emailCutOffDate,
        \Riki\SubscriptionCutOffEmail\Model\EmailCutOffDateRepository $emailCutOffDateRepository,
        \Riki\SubscriptionCutOffEmail\Api\Data\EmailCutOffDateInterface $emailCutOffDateInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $stdTimezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        Filesystem $filesystem,
        \Riki\SubscriptionCutOffEmail\Logger\SendCutOffEmailLogger $sendCutOffEmailLogger
    ) {
        $this->_helper = $helper;
        $this->resourceConnection = $resourceConnection;
        $this->emailCutOffDate = $emailCutOffDate;
        $this->emailCutOffDateRepository = $emailCutOffDateRepository;
        $this->emailCutOffDateInterface = $emailCutOffDateInterface;
        $this->stdTimezone = $stdTimezone;
        $this->dateTime = $dateTime;
        $this->directoryWrite = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->sendCutOffEmailLogger = $sendCutOffEmailLogger;
    }

    /**
     * Generate list profile id can not send mail
     */
    public function generateListProfileSendMail()
    {
        $currentDate = $this->stdTimezone->date()->format('Y-m-d');
        $connection = $this->resourceConnection->getConnection();
        $this->listProfileSendMail = $connection->fetchCol(
            $connection
                ->select()
                ->from('riki_send_mail_cut_off_date', 'profile_id')
                ->where("DATE(cut_off_date) = '$currentDate' ")
        );
    }

    /**
     * Get list check send mail cut off date
     *
     * @param $profileId
     * @return bool
     */
    public function checkSendEmailCutOffDate($profileId)
    {
        $isSendMail = true;
        if ($this->listProfileSendMail && in_array($profileId, $this->listProfileSendMail)) {
            $isSendMail = false;
        }
        return $isSendMail;
    }

    /**
     * @param $profile
     * @param $email
     * @throws \Exception
     */
    public function logEmailCutoffDate($profile, $email)
    {
        try {
            $cutOffDate = $this->stdTimezone->date()->format('Y-m-d H:i:s');
            $logSendEmail = $this->emailCutOffDateInterface->setId(null);
            $logSendEmail->setEmail($email);
            $logSendEmail->setProfileId($profile->getId());
            $logSendEmail->setCutOffDate($cutOffDate);
            $this->emailCutOffDateRepository->save($logSendEmail);
        } catch (LocalizedException $e) {
            $this->sendCutOffEmailLogger->info(sprintf(
                "Profile ID [%s] cannot save due to : [%s].",
                $profile->getId(),
                $e->getMessage()
            ));
        } catch (\Exception $e){
            $this->sendCutOffEmailLogger->critical($e);
            return;
        }
    }

    public function execute()
    {
        $lockFile = $this->directoryWrite->getRelativePath('lock/' . $this->getLockFileName());
        if (!$this->directoryWrite->isExist($lockFile)) {
            $this->directoryWrite->create($lockFile);
           try {
               if (
                   $this->_helper->getConfig(\Riki\SubscriptionCutOffEmail\Helper\Data::CONFIG_CUT_OFF_EMAIL_ENABLE) == 1
               ) {

                       $profileCollectionSendEmail = $this->_helper->getSubscriptionProfileCollection();

                       //get email cut off date not send
                       $this->generateListProfileSendMail();

                       foreach ($profileCollectionSendEmail as $profile) {
                           //check send mail cut off date
                           if ($this->checkSendEmailCutOffDate($profile->getId())) {
                               $customer = $this->_helper->getCustomerById($profile->getCustomerId());

                               // Send Email
                               $daysDiffBetweenOrderDateAndNextDeliveryDate = $this->_helper->getDiffDay($profile->getNextOrderDate(), $profile->getNextDeliveryDate());
                               $datesNeeded = $this->_helper->calculateXYDate($profile->getId());
                               $varEmailTemplate['customer_name'] = $customer->getLastname() . ' ' . $customer->getFirstname();
                               $varEmailTemplate['subscription_profile_id'] = $profile->getId();
                               $varEmailTemplate['subscription_course_name'] = $profile->getCourseName();
                               $varEmailTemplate['days_available_edit_profile'] = $daysDiffBetweenOrderDateAndNextDeliveryDate;
                               $varEmailTemplate['xdate'] = $datesNeeded['xdate'];
                               $varEmailTemplate['ydate'] = $datesNeeded['ydate'];
                               $varEmailTemplate['cutxdate'] = $datesNeeded['xdateconfig'];
                               $varEmailTemplate['emailReceiver'] = $customer->getEmail();

                               if ($this->_helper->sendCutOffEmail($varEmailTemplate)) {
                                   $this->logEmailCutoffDate($profile, $customer->getEmail());
                               }
                           }
                       }
                   }

           } catch (\Exception $e){
               $this->sendCutOffEmailLogger->critical($e);
               return;
           }finally {
               $this->directoryWrite->delete($lockFile);
           }
        } else{
            $this->sendCutOffEmailLogger->info(
                'Please wait, system have a same process is running and haven\'t finish yet.'
            );
            throw new LocalizedException(
                __('Please wait, system have a same process is running and haven\'t finish yet.')
            );
        }

    }
    /**
     * Each type of cutoff  email has a particular name.
     *
     * @return string
     */
    protected function getLockFileName()
    {
        $part = explode('\\', get_class($this));
        return strtolower(end($part)) .'.lock';
    }
}
