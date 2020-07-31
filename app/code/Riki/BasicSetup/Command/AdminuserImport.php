<?php
/**
 * Basic Setup Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Riki\BasicSetup\Helper\Data as DataHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use Magento\User\Model\UserFactory;
use Magento\Framework\App\ResourceConnection;
/**
 * Class AdminuserImport
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class AdminuserImport extends Command
{
    /**
     * define Filename
     */
    const USER_ACCOUNT ='user_account';

    const USER_PASSWORD_HISTORY = 'user_password_history';
    /**
     * @var DataHelper
     */
    protected $dataHelper;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var CollectionFactory
     */
    protected $roleRepository;
    /**
     * @var UserFactory
     */
    protected $userFactory;
    /**
     * @var ResourceConnection
     */
    protected $setup;
    /**
     * AdminuserImport constructor.
     * @param DataHelper $data
     */
    public function __construct(
        DataHelper $data,
        DirectoryList $directoryList,
        CollectionFactory $collectionFactory,
        UserFactory $userFactory,
        ResourceConnection $setup
    )
    {
        $this->dataHelper = $data;
        $this->directoryList = $directoryList;
        $this->roleRepository = $collectionFactory;
        $this->userFactory = $userFactory;
        $this->setup = $setup;
        parent::__construct();
    }
    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::USER_ACCOUNT,
                InputArgument::REQUIRED,
                'Admin user csv to import'
            ),
            new InputArgument(
                self::USER_PASSWORD_HISTORY,
                InputArgument::OPTIONAL
            ),
        ];
        $this->setName('riki:admin_user:import')
            ->setDescription('A CLI Admin user importing')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileUserAccount = $input->getArgument(self::USER_ACCOUNT);
        $fileUserPasswordHistory = $input->getArgument(self::USER_PASSWORD_HISTORY);
        $roles = $this->getAdminUserRoleIds();
        $passwordHistoryData = array();
        if($this->dataHelper->checkFileExist($fileUserPasswordHistory))
        {
            $passwordHistoryData = $this->dataHelper->getCsvContent($fileUserPasswordHistory,true);
        }
        /*
         * Check password history
         */
        if(!$fileUserPasswordHistory)
        {
            $output->writeln("-----------------------------------------------");
            $output->writeln("Warning: Admin account has not password history");
            $output->writeln("-----------------------------------------------");
        }
        /**
         * Check file exist
         */
        if(!$this->dataHelper->checkFileExist($fileUserAccount))
        {
            $fullPath = $this->directoryList->getPath(DirectoryList::ROOT).'/'.$fileUserAccount;
            $output->writeln("File: $fullPath does not exist");
            exit;
        }
        /**
         * importing
         */
        $datas = $this->dataHelper->getCsvContent($fileUserAccount,true);
        array_shift($datas);
        if($datas)
        {
            foreach($datas as $data)
            {
                $previousPasswords = '';
                if($passwordHistoryData)
                {
                    $previousPasswords = $this->getPasswordHistoryData($passwordHistoryData,$data[0]);
                }
                $this->createAdminUser($data,$output,$roles,$previousPasswords);
            }
        }
        $output->writeln("Total ".count($datas). " records were imported");
        $output->writeln("-----------------------------------------------");

        exit;
    }

    /**
     * @param $data
     * @param OutputInterface $output
     * @param $roles
     * @param $previousPasswords
     */
    public function createAdminUser($data,OutputInterface $output,$roles,$previousPasswords)
    {
        //create new user
        $output->writeln("Processing admin user: ".$data[2]);
        $newEmail = strtolower($data[2].'@dm.jp');
        $firstName = $data[4];
        $lastName = $data[4];
        $created = $data[12];
        $isActive = $data[7] ? 0 : 1;
        $failures = $data[6];
        $logDate = $data[8];
        $password = $data[3];
        $userAdmin = $this->userFactory->create()->loadByUsername($data[2]);
        if(!$userAdmin)
        {
            $output->writeln("Admin user does not exist. Create a new admin user: ".$data[2]);
            $userAdmin = $this->userFactory->create();
        }
        try{
            $output->writeln("Admin user already exists. Update admin user: ".$data[2]);
            $userAdmin->setUserName($data[2]);
            $userAdmin->setFirstName($firstName);
            $userAdmin->setLastName($lastName);
            $userAdmin->setEmail($newEmail);
            $userAdmin->setIsActive($isActive);
            $userAdmin->setFailuresNum($failures);
            $userAdmin->setLogdate($logDate);
            $userAdmin->setCreated($created);
            $userAdmin->setPassword($password);
            if(array_key_exists($data[15], $roles))
            {
                $userAdmin->setRoleId($roles[$data[15]]);
            }
            $userAdmin->setInterfaceLocale('ja_JP');
            $userAdmin->setPreviousPassword($previousPasswords);
            $userAdmin->save();
            $this->updateHashPassword($newEmail,$password);
        }catch(\Exception $e)
        {
            $output->writeln($e->getMessage());
        }
        $output->writeln("-----------------------------------------");
    }
    /**
     * @return array
     */
    public function getAdminUserRoleIds()
    {
        $roleCollection = $this->roleRepository->create();
        $newRoleData = array();
        foreach($roleCollection as $role)
        {
            $roleName = strtoupper($role->getRoleName());
            $newRoleData[trim($roleName)] = $role->getId();
        }
        return $newRoleData;
    }

    /**
     * @param $email
     * @param $password
     */
    public function updateHashPassword($email,$password)
    {
        $table = $this->setup->getConnection()->getTableName('admin_user');
        if($email && $password)
        {
            $sql = "UPDATE $table set `password` ='". $password."' where `email` = '".$email."'";
            $this->setup->getConnection()->query($sql);
        }
    }
    /**
     * @param $passwordDatas
     * @param $key
     * @return string
     */
    public function getPasswordHistoryData($passwordDatas,$key)
    {
        $res = array();
        foreach($passwordDatas as $_data)
        {
            if(trim($key) == trim($_data[0]))
            {
                $res[] = $_data[1];
            }
        }
        return implode("___",$res);
    }
}