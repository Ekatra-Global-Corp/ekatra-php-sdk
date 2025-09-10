<?php

namespace Ekatra\Product\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

/**
 * Ekatra Facade
 * 
 * Provides easy access to Ekatra SDK functionality
 */
class Ekatra extends Facade
{
    /**
     * Get the registered name of the component
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ekatra';
    }

    /**
     * Create a new product instance
     */
    public static function product(): EkatraProduct
    {
        return new EkatraProduct();
    }

    /**
     * Create a new variant instance
     */
    public static function variant(): EkatraVariant
    {
        return new EkatraVariant();
    }

    /**
     * Create product from customer data
     */
    public static function productFromData(array $data): EkatraProduct
    {
        return EkatraProduct::fromCustomerData($data);
    }

    /**
     * Create variant from customer data
     */
    public static function variantFromData(array $data): EkatraVariant
    {
        return EkatraVariant::fromCustomerData($data);
    }
}
