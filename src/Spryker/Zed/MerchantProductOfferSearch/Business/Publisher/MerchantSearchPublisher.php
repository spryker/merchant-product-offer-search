<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Business\Publisher;

use Spryker\Shared\MerchantProductOfferSearch\MerchantProductOfferSearchConfig;
use Spryker\Zed\MerchantProductOfferSearch\Business\Event\ProductEventTriggerInterface;
use Spryker\Zed\MerchantProductOfferSearch\Business\Writer\MerchantProductOfferRangeSearchWriterInterface;

class MerchantSearchPublisher implements MerchantSearchPublisherInterface
{
    public function __construct(
        protected ProductEventTriggerInterface $productEventTrigger,
        protected MerchantProductOfferRangeSearchWriterInterface $merchantProductOfferRangeSearchWriter
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return void
     */
    public function publish(array $eventEntityTransfers, string $eventName): void
    {
        if ($eventName === MerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH) {
            $this->merchantProductOfferRangeSearchWriter->writeCollectionByRangeEvents($eventEntityTransfers);

            return;
        }

        $this->productEventTrigger->triggerMerchantProducts($eventEntityTransfers);
    }
}
