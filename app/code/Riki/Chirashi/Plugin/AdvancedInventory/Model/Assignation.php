<?php
namespace Riki\Chirashi\Plugin\AdvancedInventory\Model;

use \Riki\AdvancedInventory\Model\Assignation as AssignationModel;

class Assignation
{
    /** @var AssignationModel  */
    protected $assignation;

    /**
     * Assignation constructor.
     * @param AssignationModel $assignation
     */
    public function __construct(
        AssignationModel $assignation
    ) {
        $this->assignation = $assignation;
    }

    /**
     * Only assign chirashi product if they is available in same warehouse with others
     *
     * @param AssignationModel $subject
     * @param \Closure $proceed
     * @param $assignTo
     * @param $orderedItemsArray
     * @param $allowMultipleAssignation
     * @return mixed
     */
    public function aroundAssignationProcess(
        AssignationModel $subject,
        \Closure $proceed,
        $assignTo,
        $orderedItemsArray,
        $allowMultipleAssignation
    ) {

        $chirashiIds = [];
        $newOrderedItemsArray = [];
        $itemIds = [];

        foreach ($orderedItemsArray['items'] as $itemData) {
            if (!isset($itemData['chirashi']) || !$itemData['chirashi']) {
                $itemIds[] = $itemData['item_id'];

                $newOrderedItemsArray[] = $itemData;
            } else {
                $chirashiIds[$itemData['item_id']] = $itemData;
            }
        }

        if (!empty($newOrderedItemsArray)) {
            $orderedItemsArray['items'] = $newOrderedItemsArray;
        } else {
            return $assignTo;
        }

        $assignTo = $proceed($assignTo, $orderedItemsArray, $allowMultipleAssignation);

        if (isset($assignTo['items']) && $assignTo['status'] && !empty($chirashiIds)) {
            $places = $this->getAssignedPlaces($orderedItemsArray['places'], $assignTo, $itemIds);

            foreach ($chirashiIds as $chirashiId => $chirashiData) {
                foreach ($places as $place) {
                    $placeId = $place->getId();

                    $available = $this->assignation->getAvailableStockInfo(
                        $chirashiData,
                        $placeId,
                        $chirashiData['qty_to_assign']
                    );

                    if ($available['status'] >= AssignationModel::STOCK_STATUS_AVAILABLE_PARTIAL) {
                        $assignTo['items'][$chirashiId] = $this->assignation->prepareAssignationDataForItem(
                            $chirashiData
                        );

                        $assignTo['items'][$chirashiId]['pos'][$placeId]['qty_assigned'] =
                            $chirashiData['qty_to_assign'];

                        break;
                    }
                }
            }
        }

        return $assignTo;
    }

    /**
     * @param array $places
     * @param array $assignTo
     * @param array $mainItemIds
     * @return array
     */
    private function getAssignedPlaces(array $places, array $assignTo, array $mainItemIds)
    {
        $assignedWhIds = [];

        // if order was assigned to many WH => not assign chirashi
        foreach ($assignTo['items'] as $orderItemId => $assignedItemData) {
            if (in_array($orderItemId, $mainItemIds)) {
                foreach ($assignedItemData['pos'] as $whId => $assignedData) {
                    $assignedWhIds[] = $whId;
                }
            }
        }

        $result = [];

        foreach ($places as $place) {
            if (in_array($place->getId(), $assignedWhIds)) {
                $result[] = $place;
            }
        }

        return $result;
    }
}
