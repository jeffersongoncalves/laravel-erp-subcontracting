<?php

namespace JeffersonGoncalves\Erp\Subcontracting\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ModelResolver
{
    /** @var array<string, string> */
    protected static array $cache = [];

    /** @return class-string<Model> */
    public static function subcontractingBom(): string
    {
        return static::resolve('subcontracting_bom');
    }

    /** @return class-string<Model> */
    public static function subcontractingBomItem(): string
    {
        return static::resolve('subcontracting_bom_item');
    }

    /** @return class-string<Model> */
    public static function subcontractingOrder(): string
    {
        return static::resolve('subcontracting_order');
    }

    /** @return class-string<Model> */
    public static function subcontractingOrderItem(): string
    {
        return static::resolve('subcontracting_order_item');
    }

    /** @return class-string<Model> */
    public static function subcontractingOrderSuppliedItem(): string
    {
        return static::resolve('subcontracting_order_supplied_item');
    }

    /** @return class-string<Model> */
    public static function subcontractingReceipt(): string
    {
        return static::resolve('subcontracting_receipt');
    }

    /** @return class-string<Model> */
    public static function subcontractingReceiptItem(): string
    {
        return static::resolve('subcontracting_receipt_item');
    }

    /** @return class-string<Model> */
    public static function subcontractingReceiptSuppliedItem(): string
    {
        return static::resolve('subcontracting_receipt_supplied_item');
    }

    /**
     * @return class-string<Model>
     *
     * @throws InvalidArgumentException
     */
    protected static function resolve(string $key): string
    {
        if (isset(static::$cache[$key])) {
            return static::$cache[$key];
        }

        /** @var class-string<Model>|null $model */
        $model = config("erp-subcontracting.models.{$key}");

        if (! $model || ! class_exists($model)) {
            throw new InvalidArgumentException(
                "Model class for [{$key}] does not exist: {$model}"
            );
        }

        return static::$cache[$key] = $model;
    }

    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
