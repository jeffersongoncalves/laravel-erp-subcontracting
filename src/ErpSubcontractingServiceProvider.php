<?php

namespace JeffersonGoncalves\Erp\Subcontracting;

use JeffersonGoncalves\Erp\Subcontracting\Services\SubcontractingOrderService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ErpSubcontractingServiceProvider extends PackageServiceProvider
{
    public static string $name = 'erp-subcontracting';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_erp_subcontracting_boms_table',
                'create_erp_subcontracting_bom_items_table',
                'create_erp_subcontracting_orders_table',
                'create_erp_subcontracting_order_items_table',
                'create_erp_subcontracting_order_supplied_items_table',
                'create_erp_subcontracting_receipts_table',
                'create_erp_subcontracting_receipt_items_table',
                'create_erp_subcontracting_receipt_supplied_items_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SubcontractingOrderService::class);
    }
}
