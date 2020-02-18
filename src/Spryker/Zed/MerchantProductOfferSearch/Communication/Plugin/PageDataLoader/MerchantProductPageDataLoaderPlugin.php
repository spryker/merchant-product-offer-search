<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\PageDataLoader;

use Generated\Shared\Transfer\ProductPageLoadTransfer;
use Generated\Shared\Transfer\ProductPayloadTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductPageSearchExtension\Dependency\Plugin\ProductPageDataLoaderPluginInterface;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchRepositoryInterface getRepository()
 * @method \Spryker\Zed\MerchantProductOfferSearch\Business\MerchantProductOfferSearchFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchConfig getConfig()
 */
class MerchantProductPageDataLoaderPlugin extends AbstractPlugin implements ProductPageDataLoaderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Expands ProductPageLoadTransfer object with merchant data.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductPageLoadTransfer $productPageLoadTransfer
     *
     * @return \Generated\Shared\Transfer\ProductPageLoadTransfer
     */
    public function expandProductPageDataTransfer(ProductPageLoadTransfer $productPageLoadTransfer)
    {
        $productAbstractIds = $productPageLoadTransfer->getProductAbstractIds();

        $productAbstractMerchantData = $this->getFacade()
            ->getProductAbstractMerchantDataByProductAbstractIds($productAbstractIds);

        return $this->setMerchantDataToPayloadTransfers($productPageLoadTransfer, $productAbstractMerchantData);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductPageLoadTransfer $productPageLoadTransfer
     * @param \Generated\Shared\Transfer\ProductAbstractMerchantTransfer[] $productAbstractMerchantData
     *
     * @return \Generated\Shared\Transfer\ProductPageLoadTransfer
     */
    protected function setMerchantDataToPayloadTransfers(
        ProductPageLoadTransfer $productPageLoadTransfer,
        array $productAbstractMerchantData
    ): ProductPageLoadTransfer {
        $updatedPayLoadTransfers = [];

        foreach ($productPageLoadTransfer->getPayloadTransfers() as $payloadTransfer) {
            $updatedPayLoadTransfers[$payloadTransfer->getIdProductAbstract()] = $this->setMerchantDataToPayloadTransfer($payloadTransfer, $productAbstractMerchantData);
        }

        return $productPageLoadTransfer->setPayloadTransfers($updatedPayLoadTransfers);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductPayloadTransfer $payloadTransfer
     * @param \Generated\Shared\Transfer\ProductAbstractMerchantTransfer[] $productAbstractMerchantData
     *
     * @return \Generated\Shared\Transfer\ProductPayloadTransfer
     */
    protected function setMerchantDataToPayloadTransfer(
        ProductPayloadTransfer $payloadTransfer,
        array $productAbstractMerchantData
    ): ProductPayloadTransfer {
        foreach ($productAbstractMerchantData as $productAbstractMerchantTransfer) {
            if ($payloadTransfer->getIdProductAbstract() !== $productAbstractMerchantTransfer->getIdProductAbstract()) {
                continue;
            }

            $payloadTransfer->setMerchantNames($productAbstractMerchantTransfer->getMerchantNames())
                ->setMerchantReferences($productAbstractMerchantTransfer->getMerchantReferences());
        }

        return $payloadTransfer;
    }
}
