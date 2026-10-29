<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Business\Event;

use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Shared\MerchantProductOfferSearch\MerchantProductOfferSearchConfig as SharedMerchantProductOfferSearchConfig;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToEventBehaviorFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToEventFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig;
use Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchRepositoryInterface;

class ProductEventTrigger implements ProductEventTriggerInterface
{
    public function __construct(
        protected MerchantProductOfferSearchToEventBehaviorFacadeInterface $eventBehaviorFacade,
        protected MerchantProductOfferSearchRepositoryInterface $repository,
        protected MerchantProductOfferSearchToEventFacadeInterface $eventFacade,
        protected MerchantProductOfferSearchConfig $merchantProductOfferSearchConfig
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return void
     */
    public function triggerMerchantProducts(array $eventEntityTransfers): void
    {
        $merchantIds = $this->eventBehaviorFacade->getEventTransferIds(
            $this->filterProductAbstractSearchRelevantMerchantEvents($eventEntityTransfers),
        );
        if (!$merchantIds) {
            return;
        }

        $rangeSize = max(1, $this->merchantProductOfferSearchConfig->getMerchantProductOfferRangeSize());
        $readBatchSize = max(1, intdiv($this->merchantProductOfferSearchConfig->getMerchantProductOfferRangeReadBatchSize(), $rangeSize)) * $rangeSize;

        foreach ($this->repository->getMerchantReferencesByMerchantIds($merchantIds) as $merchantReference) {
            $this->triggerByMerchantReference($merchantReference, $rangeSize, $readBatchSize);
        }
    }

    protected function triggerByMerchantReference(string $merchantReference, int $rangeSize, int $readBatchSize): void
    {
        foreach ($this->repository->iterateProductOfferIdsByMerchantReference($merchantReference, $readBatchSize) as $productOfferIds) {
            $this->eventFacade->triggerBulk(
                SharedMerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH,
                $this->createRangeEventEntityTransfers($merchantReference, $productOfferIds, $rangeSize),
            );
        }
    }

    /**
     * @param list<int> $productOfferIds
     *
     * @return list<\Generated\Shared\Transfer\EventEntityTransfer>
     */
    protected function createRangeEventEntityTransfers(string $merchantReference, array $productOfferIds, int $rangeSize): array
    {
        $rangeEventEntityTransfers = [];

        foreach (array_chunk($productOfferIds, max(1, $rangeSize)) as $rangeProductOfferIds) {
            $rangeEventEntityTransfers[] = (new EventEntityTransfer())->setAdditionalValues([
                SharedMerchantProductOfferSearchConfig::RANGE_MERCHANT_REFERENCE => $merchantReference,
                SharedMerchantProductOfferSearchConfig::RANGE_FROM_ID_PRODUCT_OFFER => reset($rangeProductOfferIds),
                SharedMerchantProductOfferSearchConfig::RANGE_TO_ID_PRODUCT_OFFER => end($rangeProductOfferIds),
            ]);
        }

        return $rangeEventEntityTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return list<\Generated\Shared\Transfer\EventEntityTransfer>
     */
    protected function filterProductAbstractSearchRelevantMerchantEvents(array $eventEntityTransfers): array
    {
        $relevantMerchantColumns = $this->merchantProductOfferSearchConfig->getProductAbstractSearchRelevantMerchantColumns();

        // An event without modified columns stands for a change of the whole merchant, for example a republish.
        return array_values(array_filter(
            $eventEntityTransfers,
            fn (EventEntityTransfer $eventEntityTransfer): bool => !$eventEntityTransfer->getModifiedColumns()
                || array_intersect($eventEntityTransfer->getModifiedColumns(), $relevantMerchantColumns) !== [],
        ));
    }
}
