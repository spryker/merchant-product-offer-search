<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\Publisher\Product;

use Spryker\Shared\MerchantProductOfferSearch\MerchantProductOfferSearchConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Merchant\Dependency\MerchantEvents;
use Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchBusinessFactory getBusinessFactory()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Communication\MerchantProductOfferSearchCommunicationFactory getFactory()
 * @method \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig getConfig()
 */
class MerchantSearchPublisherPlugin extends AbstractPlugin implements PublisherPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * - Skips merchant events whose modified columns contain none of {@link \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig::getProductAbstractSearchRelevantMerchantColumns()}.
     * - Does not skip merchant events without modified columns.
     * - Splits the IDs of the active product offers of each merchant into ranges of {@link \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig::getMerchantProductOfferRangeSize()} product offers.
     * - Triggers one `MerchantProductOfferSearch.merchant_product_offer_range.publish` event per range.
     * - For `MerchantProductOfferSearch.merchant_product_offer_range.publish` events: publishes the concrete product search pages of the active product offers of the merchant in the range of each event.
     * - For `MerchantProductOfferSearch.merchant_product_offer_range.publish` events: triggers one `Product.product_abstract_search.publish` event per abstract product of these concrete products.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventEntityTransfers, $eventName): void
    {
        $this->getBusinessFactory()->createMerchantSearchPublisher()->publish($eventEntityTransfers, $eventName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            MerchantEvents::MERCHANT_PUBLISH,
            MerchantEvents::ENTITY_SPY_MERCHANT_UPDATE,
            MerchantProductOfferSearchConfig::MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH,
        ];
    }
}
