<?php

namespace FondOfSpryker\Zed\JellyfishSalesOrderB2C\Communication\Plugin;

use ArrayObject;
use FondOfSpryker\Zed\JellyfishSalesOrderExtension\Dependency\Plugin\JellyfishOrderExpanderPostMapPluginInterface;
use Generated\Shared\Transfer\JellyfishOrderDiscountTransfer;
use Generated\Shared\Transfer\JellyfishOrderTransfer;
use Orm\Zed\Sales\Persistence\SpySalesDiscount;
use Orm\Zed\Sales\Persistence\SpySalesOrder;

class JellyfishOrderDiscountExpanderPostMapPlugin implements JellyfishOrderExpanderPostMapPluginInterface
{
    /**
     * Specification:
     *  - Expand JellyfishOrderTransfer object after mapping.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\JellyfishOrderTransfer $jellyfishOrderTransfer
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrder
     *
     * @return \Generated\Shared\Transfer\JellyfishOrderTransfer
     */
    public function expand(
        JellyfishOrderTransfer $jellyfishOrderTransfer,
        SpySalesOrder $salesOrder
    ): JellyfishOrderTransfer {
        $jellyfishOrderDiscounts = new ArrayObject();

        foreach ($salesOrder->getDiscounts() as $salesDiscount) {
            foreach ($jellyfishOrderTransfer->getDiscounts() as $jellyDiscount) {
                $jellyfishOrderDiscount = $this->validateAndExtendDiscount($salesDiscount, $jellyDiscount);

                $jellyfishOrderDiscounts->append($jellyfishOrderDiscount);
            }
        }

        return $jellyfishOrderTransfer->setDiscounts($jellyfishOrderDiscounts);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesDiscount $salesDiscount
     * @param \Generated\Shared\Transfer\JellyfishOrderDiscountTransfer $jellyfishDiscount
     *
     * @return \Generated\Shared\Transfer\JellyfishOrderDiscountTransfer
     */
    protected function validateAndExtendDiscount(
        SpySalesDiscount $salesDiscount,
        JellyfishOrderDiscountTransfer $jellyfishDiscount
    ): JellyfishOrderDiscountTransfer {
        if (empty($jellyfishDiscount->getName()) || $jellyfishDiscount->getIdSalesOrderItem() === null) {
            foreach ($salesDiscount->getDiscountCodes() as $salesDiscountCode) {
                if (
                    $salesDiscountCode->getCode() === $jellyfishDiscount->getCode()
                    && $jellyfishDiscount->getSumAmount() === $salesDiscount->getAmount()
                    && $jellyfishDiscount->getQuantity() === $this->getQuantity($salesDiscount)
                ) {
                    $jellyfishDiscount->setName($this->getDiscountName($jellyfishDiscount, $salesDiscount));
                    $jellyfishDiscount->setIdSalesOrderItem($this->getIdSalesOrder($jellyfishDiscount, $salesDiscount));
                }
            }
        }

        return $jellyfishDiscount;
    }

    /**
     * @param \Generated\Shared\Transfer\JellyfishOrderDiscountTransfer $jellyfishDiscount
     * @param \Orm\Zed\Sales\Persistence\SpySalesDiscount $salesDiscount
     *
     * @return string
     */
    protected function getDiscountName(
        JellyfishOrderDiscountTransfer $jellyfishDiscount,
        SpySalesDiscount $salesDiscount
    ): string {
        if ($jellyfishDiscount->getName() !== null && $jellyfishDiscount->getName() !== '') {
            return $jellyfishDiscount->getName();
        }

        if ($salesDiscount->getDisplayName() !== null) {
            return $salesDiscount->getDisplayName();
        }

        return '';
    }

    /**
     * @param \Generated\Shared\Transfer\JellyfishOrderDiscountTransfer $jellyfishDiscount
     * @param \Orm\Zed\Sales\Persistence\SpySalesDiscount $salesDiscount
     *
     * @return int
     */
    protected function getIdSalesOrder(
        JellyfishOrderDiscountTransfer $jellyfishDiscount,
        SpySalesDiscount $salesDiscount
    ): int {
        if ($jellyfishDiscount->getIdSalesOrderItem() !== null) {
            return $jellyfishDiscount->getIdSalesOrderItem();
        }

        return 0;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesDiscount $salesDiscount
     *
     * @return int
     */
    protected function getQuantity(SpySalesDiscount $salesDiscount): int
    {
        $salesOrderItem = $salesDiscount->getOrderItem();

        return $salesOrderItem === null ? 1 : $salesOrderItem->getQuantity();
    }
}
