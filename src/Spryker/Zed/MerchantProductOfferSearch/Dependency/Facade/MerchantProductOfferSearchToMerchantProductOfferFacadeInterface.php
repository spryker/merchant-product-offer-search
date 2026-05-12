<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade;

use Generated\Shared\Transfer\MerchantProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferCollectionTransfer;

interface MerchantProductOfferSearchToMerchantProductOfferFacadeInterface
{
    public function preloadProductOfferCollection(
        MerchantProductOfferCriteriaTransfer $merchantProductOfferCriteriaTransfer
    ): void;

    public function getProductOfferCollection(
        MerchantProductOfferCriteriaTransfer $merchantProductOfferCriteriaTransfer
    ): ProductOfferCollectionTransfer;
}
