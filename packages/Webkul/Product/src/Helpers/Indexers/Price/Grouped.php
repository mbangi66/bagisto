<?php

namespace Webkul\Product\Helpers\Indexers\Price;

class Grouped extends AbstractType
{
    /**
     * Returns product specific pricing for customer group
     *
     * @return array
     */
    public function getIndices()
    {
        if (! $this->product->grouped_products()->count()) {
            return parent::getIndices();
        }

        $minRegularPrice = null;
        $minPrice = null;

        foreach ($this->product->grouped_products as $groupedProduct) {
            if (! $groupedProduct->associated_product) {
                continue;
            }

            $productIndexer = $groupedProduct->associated_product->getTypeInstance()
                ->getPriceIndexer()
                ->setChannel($this->channel)
                ->setCustomerGroup($this->customerGroup)
                ->setProduct($groupedProduct->associated_product);

            $productPrice = $productIndexer->getMinimalPrice();
            $productRegularPrice = $groupedProduct->associated_product->price;

            if (
                $minPrice === null
                || $productPrice < $minPrice
            ) {
                $minPrice = $productPrice;
            }

            if (
                $minRegularPrice === null
                || $productRegularPrice < $minRegularPrice
            ) {
                $minRegularPrice = $productRegularPrice;
            }
        }

        return [
            'min_price'         => $minPrice ?? 0,
            'regular_min_price' => $minRegularPrice ?? 0,
            'max_price'         => $minPrice ?? 0,
            'regular_max_price' => $minRegularPrice ?? 0,
            'product_id'        => $this->product->id,
            'channel_id'        => $this->channel->id,
            'customer_group_id' => $this->customerGroup->id,
        ];
    }

    /**
     * Get product minimal price.
     *
     * @return float
     */
    public function getMinimalPrice($qty = null)
    {
        $minPrices = [];

        foreach ($this->product->grouped_products as $groupOptionProduct) {
            $variant = $groupOptionProduct->associated_product;

            $variantIndexer = $variant->getTypeInstance()
                ->getPriceIndexer()
                ->setChannel($this->channel)
                ->setCustomerGroup($this->customerGroup)
                ->setProduct($variant);

            $minPrices[] = $variantIndexer->getMinimalPrice();
        }

        return empty($minPrices) ? 0 : min($minPrices);
    }

    /**
     * Get product regular minimal price.
     *
     * @return float
     */
    public function getRegularMinimalPrice()
    {
        $minPrices = [];

        foreach ($this->product->grouped_products as $groupOptionProduct) {
            $minPrices[] = $groupOptionProduct->associated_product->price;
        }

        return empty($minPrices) ? 0 : min($minPrices);
    }

    /**
     * Get product maximum price.
     *
     * @return float
     */
    public function getMaximumPrice()
    {
        $maxPrices = [];

        foreach ($this->product->grouped_products as $groupOptionProduct) {
            $variant = $groupOptionProduct->associated_product;

            $variantIndexer = $variant->getTypeInstance()
                ->getPriceIndexer()
                ->setChannel($this->channel)
                ->setCustomerGroup($this->customerGroup)
                ->setProduct($variant);

            $maxPrices[] = $variantIndexer->getMinimalPrice();
        }

        return empty($maxPrices) ? 0 : max($maxPrices);
    }

    /**
     * Get product regular maximum price.
     *
     * @return float
     */
    public function getRegularMaximumPrice()
    {
        $maxPrices = [];

        foreach ($this->product->grouped_products as $groupOptionProduct) {
            $maxPrices[] = $groupOptionProduct->associated_product->price;
        }

        return empty($maxPrices) ? 0 : max($maxPrices);
    }
}
