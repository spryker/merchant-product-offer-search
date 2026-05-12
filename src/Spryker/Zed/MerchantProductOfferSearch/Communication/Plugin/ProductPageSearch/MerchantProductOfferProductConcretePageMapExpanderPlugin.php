<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\ProductPageSearch;

use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\PageMapTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductPageSearchExtension\Dependency\PageMapBuilderInterface;
use Spryker\Zed\ProductPageSearchExtension\Dependency\Plugin\ProductConcretePageMapExpanderPluginInterface;
use Spryker\Zed\ProductPageSearchExtension\Dependency\Plugin\ProductConcretePageMapExpanderPreLoaderPluginInterface;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig getConfig()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Communication\MerchantProductOfferSearchCommunicationFactory getFactory()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchBusinessFactory getBusinessFactory()()
 */
class MerchantProductOfferProductConcretePageMapExpanderPlugin extends AbstractPlugin implements ProductConcretePageMapExpanderPluginInterface, ProductConcretePageMapExpanderPreLoaderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Extracts SKUs from the provided product concrete transfers.
     * - Batch-loads all merchant product offers for those SKUs across all stores mentioned in transfers.
     * - Warms the internal cache before the per-product expand loop begins.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return void
     */
    public function preload(array $productConcreteTransfers): void
    {
        $this->getBusinessFactory()
            ->createMerchantProductOfferSearchExpander()
            ->preloadProductOffers($productConcreteTransfers);
    }

    /**
     * {@inheritDoc}
     * - Expands the provided `PageMap.fullTextBoosted` transfer property with merchant names and references from related product offers.
     * - Expands the provided `PageMap` transfer object with related merchant references.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\PageMapTransfer $pageMapTransfer
     * @param \Spryker\Zed\ProductPageSearchExtension\Dependency\PageMapBuilderInterface $pageMapBuilder
     * @param array<string, mixed> $productData
     * @param \Generated\Shared\Transfer\LocaleTransfer $localeTransfer
     *
     * @return \Generated\Shared\Transfer\PageMapTransfer
     */
    public function expand(
        PageMapTransfer $pageMapTransfer,
        PageMapBuilderInterface $pageMapBuilder,
        array $productData,
        LocaleTransfer $localeTransfer
    ): PageMapTransfer {
        return $this->getFacade()->expandProductConcretePageMap(
            $pageMapTransfer,
            $pageMapBuilder,
            $productData,
            $localeTransfer,
        );
    }
}
