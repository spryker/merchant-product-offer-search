<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\ProductPageSearch;

use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\PageMapTransfer;
use Spryker\Zed\ProductPageSearchExtension\Dependency\PageMapBuilderInterface;
use Spryker\Zed\ProductPageSearchExtension\Dependency\Plugin\ProductAbstractMapExpanderPluginInterface;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchRepositoryInterface getRepository()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig getConfig()
 */
class MerchantNamesProductAbstractMapExpanderPlugin implements ProductAbstractMapExpanderPluginInterface
{
    /**
     * @var string
     */
    protected const KEY_MERCHANT_NAMES = 'merchant_names';

    /**
     * @var string
     */
    protected const KEY_MERCHANT_NAME = 'merchant_name';

    /**
     * {@inheritDoc}
     * - Adds merchant names to product abstract search data.
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
    public function expandProductMap(
        PageMapTransfer $pageMapTransfer,
        PageMapBuilderInterface $pageMapBuilder,
        array $productData,
        LocaleTransfer $localeTransfer
    ): PageMapTransfer {
        if (!is_array($productData[static::KEY_MERCHANT_NAMES])) {
            return $pageMapTransfer;
        }

        if (empty($productData[static::KEY_MERCHANT_NAMES][$pageMapTransfer->getStore()])) {
            return $pageMapTransfer;
        }

        foreach ($productData[static::KEY_MERCHANT_NAMES][$pageMapTransfer->getStore()] as $merchantName) {
            if ($this->hasMerchantNameFacet($pageMapTransfer, $merchantName)) {
                continue;
            }

            $pageMapBuilder
                ->addStringFacet($pageMapTransfer, static::KEY_MERCHANT_NAME, $merchantName)
                ->addFullTextBoosted($pageMapTransfer, $merchantName)
                ->addSuggestionTerms($pageMapTransfer, $merchantName)
                ->addCompletionTerms($pageMapTransfer, $merchantName);
        }

        return $pageMapTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\PageMapTransfer $pageMapTransfer
     * @param string $value
     *
     * @return bool
     */
    protected function hasMerchantNameFacet(PageMapTransfer $pageMapTransfer, string $value): bool
    {
        foreach ($pageMapTransfer->getStringFacet() as $facetMapTransfer) {
            if ($facetMapTransfer->getName() === static::KEY_MERCHANT_NAME && in_array($value, $facetMapTransfer->getValue())) {
                return true;
            }
        }

        return false;
    }
}
