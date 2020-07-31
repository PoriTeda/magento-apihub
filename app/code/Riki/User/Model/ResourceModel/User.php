<?php
namespace Riki\User\Model\ResourceModel;

use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DB\Select;
use Magento\User\Model\User as ModelUser;

class User extends \Magento\User\Model\ResourceModel\User
{
    /**
     * Get old passwords
     *
     * @param ModelUser $user        user object
     * @param int       $retainLimit retain limit
     *
     * @return array
     */
    public function getOldPasswords($user, $retainLimit = 10)
    {
        $userId = (int)$user->getId();
        $table = $this->getTable('admin_passwords');

        // purge expired passwords, except those which should be retained
        $retainPasswordIds = $this->getConnection()->fetchCol(
            $this->getConnection()
                ->select()
                ->from($table, 'password_id')
                ->where('user_id = :user_id')
                ->order('expires ' . Select::SQL_DESC)
                ->order('password_id ' . Select::SQL_DESC)
                ->limit($retainLimit),
            [':user_id' => $userId]
        );
        $t = (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
        $t = strtotime($t);

        $where = ['user_id = ?' => $userId, 'expires <= ?' => $t];
        if ($retainPasswordIds) {
            $where['password_id NOT IN (?)'] = $retainPasswordIds;
        }
        $this->getConnection()->delete($table, $where);

        // get all remaining passwords
        return $this->getConnection()->fetchCol(
            $this->getConnection()
                ->select()
                ->from($table, 'password_hash')
                ->where('user_id = :user_id')
                ->order('expires ' . Select::SQL_DESC)
                ->order('password_id ' . Select::SQL_DESC)
                ->limit($retainLimit),
            [':user_id' => $userId]
        );
    }
}