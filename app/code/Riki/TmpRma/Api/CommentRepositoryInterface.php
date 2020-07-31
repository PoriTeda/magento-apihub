<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Api;

/**
 * Interface CommentRepositoryInterface
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Api
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
interface CommentRepositoryInterface
{
    /**
     * Lists comments that match specific search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria searchCriteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria);

    /**
     * Loads a specified comment.
     *
     * @param int $id id
     *
     * @return \Riki\TmpRma\Api\Data\CommentInterface
     */
    public function get($id);

    /**
     * Deletes a specified comment.
     *
     * @param \Riki\TmpRma\Api\Data\CommentInterface $entity entity
     *
     * @return bool
     */
    public function delete(\Riki\TmpRma\Api\Data\CommentInterface $entity);

    /**
     * Performs persist operations for a specified comment.
     *
     * @param \Riki\TmpRma\Api\Data\CommentInterface $entity entity
     *
     * @return \Riki\TmpRma\Api\Data\CommentInterface
     */
    public function save(\Riki\TmpRma\Api\Data\CommentInterface $entity);
}
