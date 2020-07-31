<?php
namespace Riki\Subscription\Command;

use Riki\Subscription\Model\Profile\Profile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriptionAfterImport extends Command
{
    const FILE_NAME = 'file_name';

    const TYPE_PROFILE = 'type_profile';

    const MAIN_PROFILE = 'main_profile';

    const VERSION_PROFILE = 'version_profile';

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_modelSubscriptionProfile;

    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $modelPaymentFee;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subscriptionCourse;
    /**
     * @var SubscriptionBeforeImport
     */
    protected $subscriptionBeforeImport;
    /**
     * @var \Riki\Subscription\Model\Version\Version
     */
    protected $subscriptionVersion;

    /**
     * SubscriptionAfterImport constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\SubscriptionCourse\Model\Course $subscriptionCourse
     * @param \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $modelPaymentFee
     * @param SubscriptionBeforeImport $subscriptionBeforeImport
     * @param \Riki\Subscription\Model\Version\Version $subscriptionVersion
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile,
        \Magento\Framework\File\Csv $reader,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourse,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $modelPaymentFee,
        \Riki\Subscription\Command\SubscriptionBeforeImport $subscriptionBeforeImport,
        \Riki\Subscription\Model\Version\Version $subscriptionVersion,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_modelSubscriptionProfile = $modelSubscriptionProfile;
        $this->_readerCSV = $reader;
        $this->_time = $timezoneInterface;
        $this->_customerFactory = $customerFactory;
        $this->modelPaymentFee = $modelPaymentFee;
        $this->subscriptionCourse = $subscriptionCourse;
        $this->subscriptionBeforeImport = $subscriptionBeforeImport;
        $this->subscriptionVersion = $subscriptionVersion;
        $this->resourceConnection = $resourceConnection;

        parent::__construct();
    }

    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::REQUIRED,
                'Name of file to import'
            ),
            new InputArgument(
                self::TYPE_PROFILE,
                InputArgument::REQUIRED,
                'Check type profile'
            )
        ];
        $this->setName('riki:subscription:after-import')
            ->setDescription('A cli Import Subscription Profile')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * CompareDataFaster
     *
     * @param $dataImport
     * @param $row
     * @return array
     */
    public function compareData($dataImport, $dataAfterImport)
    {
        $arrDataCompare = array();

        if (!empty($dataAfterImport) && !empty($dataImport) && isset($dataAfterImport['profile_id'])) {

            foreach ($dataImport as $key => $val) {
                $valueField = $dataAfterImport[$key];
                if ($valueField != trim($val)) {
                    $arrDataCompare[$key] = array(
                        'File csv' => trim($val),
                        'Field model' => $valueField
                    );
                }
            }
        }
        return $arrDataCompare;
    }

    /**
     * prepareDataAfterImport
     *
     * @param $typeProfile
     * @param $dataOldProfilesWithKey
     * @return array
     */
    public function prepareDataAfterImport($typeProfile, $dataOldProfilesWithKey)
    {

        $connection = $this->resourceConnection->getConnection('sales');

        $oldProfileIds = [];

        foreach ($dataOldProfilesWithKey as $iOldProfileId => $dataOldProfiles) {
            foreach ($dataOldProfiles as $iProfileId => $dataProfile) {
                $oldProfileIds[] = $iProfileId;
            }
        }

        $oldProfileIds = array_unique($oldProfileIds);

        $aProfileIdAfterImport = [];

        if ($typeProfile == self::MAIN_PROFILE) {
            $select = $connection->select()
                ->from([$connection->getTableName('subscription_profile_version')])
                ->where("rollback_id IN (?)", $oldProfileIds);
            $dataProfileVersion = $connection->fetchAll($select);


            foreach ($dataProfileVersion as $dataProfile) {
                $aProfileIdAfterImport[] = $dataProfile['rollback_id'];
            }
        } else {
            $select = $connection->select()
                ->from([$connection->getTableName('subscription_profile_version')])
                ->where("moved_to IN (?)", $oldProfileIds);
            $dataProfileVersion = $connection->fetchAll($select);

            foreach ($dataProfileVersion as $dataProfile) {
                $aProfileIdAfterImport[] = $dataProfile['moved_to'];
            }
        }
        unset($oldProfileIds);

        $dataAfterImport = [];
        foreach ($dataOldProfilesWithKey as $iOldProfileId => $dataOldProfiles) {
            foreach ($dataOldProfiles as $iProfileId => $dataProfile) {
                if (in_array($iProfileId, $aProfileIdAfterImport)) {
                    $dataAfterImport[$iOldProfileId] = $dataProfile;
                }
            }
        }

        return $dataAfterImport;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flagTime = microtime(true);

        $fileName = $input->getArgument(self::FILE_NAME);

        $typeProfile = $input->getArgument(self::TYPE_PROFILE);

        if (!$typeProfile) {
            $typeProfile = self::MAIN_PROFILE;
        }

        if ($fileName != "") {
            try {

                list($dataResult, $dataForValidated) = $this->subscriptionBeforeImport->prepareData($fileName);
                $dataOldProfilesWithKey = $dataForValidated['profile_id_data'];

                $dataAfterImport = $this->prepareDataAfterImport($typeProfile, $dataOldProfilesWithKey);

                $row = 2;

                foreach ($dataResult as $data) {
                    // convert Data
                    $dataImport = $this->subscriptionBeforeImport->convertDataImport($data);

                    //validate data
                    $dataBeforeImport = $this->subscriptionBeforeImport->validateData($dataImport, $dataForValidated, $typeProfile, $row, \Riki\Subscription\Command\SubscriptionBeforeImport::IS_AFTER_IMPORT);
                    $dataImport = $dataBeforeImport['dataImport'];

                    $errors = $dataBeforeImport['error'];
                    if (count($errors) > 0) {
                        $output->writeln("====================================================================================");
                        $output->writeln("[Row $row] Validate error!\n");
                        $output->writeln(implode("\n", $errors) . "\n");
                    } else {
                        $iOldProfileId = $dataImport['old_profile_id'];
                        if (isset($dataAfterImport[$iOldProfileId])) {
                            $dataCompare = $this->compareData($dataImport, $dataAfterImport[$dataImport['old_profile_id']]);
                            $output->writeln("[Row $row]====================================================================================");
                            if (is_array($dataCompare) && count($dataCompare) > 0) {
                                print_r($dataCompare) . "\n";
                            }
                        } else {
                            $output->writeln("[Row $row] is not imported!!");
                        }
                    }
                    $row++;
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                exit();
            }
        }

        $timeElapsedSecs = microtime(true) - $flagTime;
        echo "Script run time :" . ($timeElapsedSecs) . "\n";
    }


}