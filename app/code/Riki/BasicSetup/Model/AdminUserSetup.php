<?php
/**
 * Riki Basic Setup
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Model;
use Riki\BasicSetup\Helper\Data as DataHelper;
/**
 * Class Admin User Setup
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class AdminUserSetup
{
    /**
     * @var \Riki\BasicSetup\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userFactory;
    /**
     * @var \Magento\User\Model\RoleFactory
     */
    protected $roleFactory;
    /**
     * @var \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory
     */
    protected $roleCollection;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var
     */
    protected $userCollection;
    /**
     * @var \Magento\Authorization\Model\RulesFactory
     */
    protected $ruleFactory;
    /**
     * @var \Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory
     */
    protected $ruleCollectionFactory;
    /**
     * AdminUserSetup constructor.
     * @param DataHelper $dataHelper
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory
     * @param \Magento\Authorization\Model\RoleFactory $roleFactory
     * @param \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory $collectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct
    (
        \Riki\BasicSetup\Helper\Data $dataHelper,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        \Magento\Authorization\Model\RoleFactory $roleFactory,
        \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Authorization\Model\RulesFactory $ruleFactory,
        \Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory $ruleCollectionFactory
    )
    {
        $this->dataHelper = $dataHelper;
        $this->userFactory = $userFactory;
        $this->roleFactory = $roleFactory;
        $this->roleCollection = $collectionFactory;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->userCollection = $userCollectionFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * @param $version
     * @param $connection
     */
    public function setupData($version,$connection)
    {
        //$this->setupAdminRole($version);
        //$this->setupAdminUser($version,$connection);
        $this->assignRuleForRole($version);
    }

    /**
     * @param $version
     * @param $connection
     */
    public function setupAdminUser($version,$connection)
    {
        $fileData = $version.'/'.DataHelper::FILE_ADMIN_USER;
        $userData = $this->dataHelper->getCsvContent($fileData);
        $passwordDatas = $this->getPasswordData($version);
        $userRoles = $this->getAdminUserRoleIds($version);
        $updatePasswordData = array();
        foreach($userData as $data)
        {
            if(array_key_exists($data[15], $userRoles))
            {
                $userRoleId = $userRoles[$data[15]];
            }
            else
            {
                $userRoleId = 1;
            }
            $newEmail = strtolower($data[2].'@dm.jp');;
            $updatePasswordData[] = array('email'=>$newEmail, 'password' => $data[3]);
            $previousPassword = $this->getPasswordHistoryData($passwordDatas,$data[0]);
            $this->createAdminUser($data,$previousPassword, $userRoleId);

        }
        //update password
        $this->updateOrignalPassword($connection,$updatePasswordData);
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

    /**
     * @param $data
     * @param $previousPassword
     * @param $userRoleId
     */
    public function createAdminUser($data,$previousPassword,$userRoleId)
    {
        //create new user
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
            $userAdmin = $this->userFactory->create();
        }
        try{
            $userAdmin->setUserName($data[2]);
            $userAdmin->setFirstName($firstName);
            $userAdmin->setLastName($lastName);
            $userAdmin->setEmail($newEmail);
            $userAdmin->setIsActive($isActive);
            $userAdmin->setFailuresNum($failures);
            $userAdmin->setLogdate($logDate);
            $userAdmin->setCreated($created);
            $userAdmin->setPassword($password);
            $userAdmin->setRoleId($userRoleId);
            $userAdmin->setInterfaceLocale('ja_JP');
            $userAdmin->setPreviousPassword($previousPassword);
            $userAdmin->save();
            //assign role
        }catch(\Exception $e)
        {
            $this->logger->error($e->getMessage());
        }
    }
    /**
     * @param $version
     */
    public function setupAdminRole($version)
    {

        $fileRole = $version.'/'.DataHelper::FILE_ADMIN_ROLE;
        $roleData = $this->dataHelper->getCsvContent($fileRole);
        $existRoles  = $this->getExistRoles($roleData);
        if(count($roleData))
        {
            foreach($roleData as $_data)
            {
                $newRole = $_data[0];
                if($_data[0] && !in_array(strtolower($newRole),$existRoles))
                {
                    //create new Role
                    $roleObject = $this->roleFactory->create();
                    $roleObject->setRoleName($newRole);
                    $roleObject->setRoleType('G'); // G:group , U: user
                    $roleObject->setUserType(2); // 1 : user, 2 : group
                    $roleObject->setParentId(0);
                    $roleObject->setTreeLevel(1);
                    $roleObject->setGwsIsAll(1);
                    $roleObject->setGwsWebsites(
                        $this->getAllWebsiteIds()
                    );
                    $roleObject->setGwsStoreGroups(
                        $this->getAllGroupIds()
                    );
                    try{
                        $roleObject->save();
                    }catch(\Exception $e)
                    {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param $setup
     * @param $data
     */
    public function updateOrignalPassword($setup,$data)
    {
        $table = $setup->getConnection()->getTableName('admin_user');
        foreach($data as $_data)
        {
            if($_data['password'] && $_data['email'])
            {
                $sql = "UPDATE $table set `password` ='". $_data['password']."' where `email` = '".$_data['email']."'";
                $setup->run($sql);
            }
        }
    }

    /**
     * @param $roleData
     * @return array
     */
    public function getExistRoles($roleData)
    {
        $noDelRoles = array();
        $noDelRoles[] = 'administrators';
        foreach($roleData as $_roleRaw)
        {
            $noDelRoles[] = strtolower($_roleRaw[0]);
        }

        $collection = $this->roleCollection->create();
        $collection->addFieldToFilter('role_type', \Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE);

        $roleNames = array();
        foreach($collection as $_role)
        {
            $roleName = strtolower($_role->getRoleName());
            if(in_array($roleName, $noDelRoles))
            {
                $roleNames[] = strtolower($roleName);
            }
        }
        return $roleNames;
    }

    /**
     * @return string
     */
    public function getAllWebsiteIds()
    {
        $webIds = array();
        $websites = $this->storeManager->getWebsites();
        foreach($websites as $_web)
        {
            $webIds[] = $_web->getId();
        }
        if($webIds)
        {
            return implode(',',$webIds);
        }
        else
        {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getAllGroupIds()
    {
        $groups = $this->storeManager->getGroups();
        $groupIds = array();
        foreach($groups as $_group)
        {
            $groupIds[] = $_group->getId();
        }
        if($groupIds)
        {
            return implode(',',$groupIds);
        }
        else
        {
            return '';
        }
    }

    /**
     * @return array
     */
    public function getAllAdminUsers()
    {
        $userEmails = array();
        $users = $this->userCollection->create();
        foreach($users as $user)
        {
            $userEmails[] = $user->getEmail();
        }
        return $userEmails;
    }

    /**
     * @param $version
     * @return array
     */
    public function getPasswordData($version)
    {
        $filePass = $version.'/'.DataHelper::FILE_ADMIN_PASS;
        $passData = $this->dataHelper->getCsvContent($filePass);
        return $passData;
    }

    /**
     * @param $version
     * @return array
     */
    public function getAdminUserRoleIds($version)
    {
        $fileRole = $version.'/'.DataHelper::FILE_ADMIN_ROLE;
        $dataRole = $this->dataHelper->getCsvContent($fileRole);

        $roleCollection = $this->roleCollection->create();
        $roleCollection->addFieldToFilter('role_type', \Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE);

        $newRoleData = array();
        foreach($roleCollection as $role)
        {
            $roleName = strtolower($role->getRoleName());
            foreach($dataRole as $data)
            {
                if($roleName == strtolower($data[0]))
                {
                    $newRoleData[trim($data[0])] = $role->getId();
                }
            }
        }
        return $newRoleData;
    }

    public function assignRuleForRole($version)
    {

        $ruleDataFileName = $version.'/'.DataHelper::FILE_ADMIN_ROLE_RULES;
        $ruleDatas = $this->dataHelper->getCsvContent($ruleDataFileName);
        $ruleDatas = $this->processRuleData($ruleDatas);
        //update data
        $countRuleData = count($ruleDatas);
        if($countRuleData)
        {
            //remove all role
            for($i=1;$i< $countRuleData; $i++){
                $countRuleDataI = count($ruleDatas[$i]);
                for($j=1; $j<$countRuleDataI; $j++)
                {
                    if ($ruleDatas[$i][$j][0]) {
                        $this->setUpFullRoleAdmin($ruleDatas[$i][$j]);
                    }
                }
            }


            $allPermission = [];
            for($i=1;$i< $countRuleData; $i++)
            {
                //set full permission
                $countRuleDataI = count($ruleDatas[$i]);
                for($j=1; $j<$countRuleDataI; $j++)
                {
                    if ($ruleDatas[$i][$j][0]=='Magento_Backend::all' &&  $ruleDatas[$i][$j][3]=='allow' ) {
                        $allPermission[] = $ruleDatas[$i][$j];
                    }
                    $this->updateRules($ruleDatas[$i][$j]);
                }
            }

            if (is_array($allPermission)&& count ($allPermission)>0){
                foreach ($allPermission as $item){
                    $this->setUpFullRoleAdmin($item);
                }
            }
        }
    }
    /**
     * set key for data element
     *
     * @param $data
     * @return mixed
     */
    public function processRuleData($data)
    {
        $roleIds = array();
        $roleCollection = $this->roleCollection->create();
        $roleCollection->addFieldToFilter('user_type',2);
        $roleCollection->addFieldToFilter('role_type','G');
        //get Data Roles
        if($roleCollection->getSize())
        {
            foreach($roleCollection as $_role)
            {
                $roleIds[$_role->getRoleName()] = $_role->getRoleId();
            }
        }
        $firstRow = $data[0];
        $countData = count($data);
        if($countData)
        {
            for($i=0;$i<$countData;$i++)
            {
                $countDataI = count($data[$i]);
                for($j=0;$j<$countDataI;$j++)
                {
                    if($i>0 && $j>0)
                    {
                        $roleIdKey = $firstRow[$j];
                        $roleIdKeyValue = $roleIds[$roleIdKey];
                        $perm = 'deny';
                        if($data[$i][$j])
                        {
                            $perm = "allow";
                        }
                        $data[$i][$j] = array($data[$i][0],$roleIdKey,$roleIdKeyValue,$perm);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @param $data
     */
    public function updateRules($data)
    {
        $resourceId = $data[0];
        $roleId = $data[2];
        $permission = $data[3];

        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('resource_id',$data[0]);
        $ruleCollection->addFieldToFilter('role_id',$roleId);
        if($ruleCollection->getSize())
        {
            $ruleObject = $ruleCollection->getFirstItem();
        }
        else
        {
            $ruleObject = $this->ruleFactory->create();
        }
        try{
            $ruleObject->setRoleId($roleId);
            $ruleObject->setResourceId($resourceId);
            $ruleObject->setPermission($permission);
            $ruleObject->save();
        }catch(\Exception $e)
        {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @param $data
     */
    public function setUpFullRoleAdmin($data)
    {
        $resourceId = $data[0];
        $roleId     = $data[2];
        $permission = $data[3];

        $connection = $this->ruleCollectionFactory->create()->getConnection();
        $table = $connection->getTableName('authorization_rule');
        $sql = "Delete FROM $table  Where role_id = $roleId  AND resource_id <>'Magento_Backend::all'  ";
        $connection->query($sql);
    }


}//end class