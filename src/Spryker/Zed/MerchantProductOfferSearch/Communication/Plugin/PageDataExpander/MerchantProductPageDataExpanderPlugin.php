<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\PageDataExpander;

use Generated\Shared\Transfer\ProductPageSearchTransfer;
use Spryker\Shared\ProductPageSearch\ProductPageSearchConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductPageSearch\Dependency\Plugin\ProductPageDataExpanderInterface;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig getConfig()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchFacadeInterface getFacade()
 */
class MerchantProductPageDataExpanderPlugin extends AbstractPlugin implements ProductPageDataExpanderInterface
{
    /**
     * {@inheritDoc}
     * - Expands the provided ProductAbstractPageSearch transfer object's data by merchant names.
     *
     * @api
     *
     * @param array $productData
     * @param \Generated\Shared\Transfer\ProductPageSearchTransfer $productAbstractPageSearchTransfer
     *
     * @return void
     */
    public function expandProductPageData(array $productData, ProductPageSearchTransfer $productAbstractPageSearchTransfer): void
    {
        $merchantNames = $productAbstractPageSearchTransfer->getMerchantNames();

        foreach ($productData[ProductPageSearchConfig::PRODUCT_ABSTRACT_PAGE_LOAD_DATA]->getMerchantNames() as $store => $names) {
            if (array_key_exists($store, $merchantNames)) {
                $merchantNames[$store] = array_unique(array_merge($merchantNames[$store], $names));

                continue;
            }

            $merchantNames[$store] = $names;
        }

        $productAbstractPageSearchTransfer->setMerchantNames($merchantNames);
        $productAbstractPageSearchTransfer->setMerchantReferences($productData[ProductPageSearchConfig::PRODUCT_ABSTRACT_PAGE_LOAD_DATA]->getMerchantReferences());
    }
}
