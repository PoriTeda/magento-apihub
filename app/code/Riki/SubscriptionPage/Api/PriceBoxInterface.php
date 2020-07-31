<?php
namespace Riki\SubscriptionPage\Api;

interface PriceBoxInterface
{
    /**
     * Get price box of list product base on courseId & frequencyId
     *
     * @api
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param int $iProfileId
     * @param string[] $productCatIds
     * @param int[] $productQtyIds
     * @param int $selectedMachineId
     * @return mixed
     */
    public function getList($courseId, $frequencyId, $iProfileId,$productCatIds, $productQtyIds, $selectedMachineId = 0);

    /**
     * GetPriceItem
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param int $iProfileId
     * @param string[] $productCatIds
     * @param int[] $productQtyIds
     * @return mixed
     */
    public function getPriceItem($courseId, $frequencyId, $iProfileId,$productCatIds, $productQtyIds);

    /**
     * Get price box of list product base on courseId & frequencyId
     *
     * @api
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param string[] $machineIds
     * @return mixed
     */
    public function getListMachines($courseId, $frequencyId, $machineIds);

    /**
     * Get price box when change qty hanpukai
     *
     * @api
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param string[] $productCatIds
     * @param int $qtyChangeAll
     * @return mixed
     */
    public function changeHanpukaiQty($courseId, $frequencyId, $productCatIds, $qtyChangeAll);


    /**
     * validateAdditionalCat
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param string[] $selectedMain
     * @return mixed
     */
    public function validateAdditionalCat($courseId ,$frequencyId, $selectedMain);
}
