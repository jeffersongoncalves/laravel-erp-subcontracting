<div class="filament-hidden">

![Laravel ERP Subcontracting](https://raw.githubusercontent.com/jeffersongoncalves/laravel-erp-subcontracting/main/art/jeffersongoncalves-laravel-erp-subcontracting.png)

</div>

# Laravel ERP Subcontracting

ERP subcontracting — subcontracting BOMs, orders and receipts for the Laravel ERP ecosystem.

This package is the subcontracting module of the Laravel ERP ecosystem. It models the flow of having a subcontractor produce a finished good from raw materials the company supplies, and posts the resulting stock and accounting impact when the finished good is received. It depends on [`jeffersongoncalves/laravel-erp-core`](https://github.com/jeffersongoncalves/laravel-erp-core), [`jeffersongoncalves/laravel-erp-accounting`](https://github.com/jeffersongoncalves/laravel-erp-accounting), [`jeffersongoncalves/laravel-erp-stock`](https://github.com/jeffersongoncalves/laravel-erp-stock), [`jeffersongoncalves/laravel-erp-buying`](https://github.com/jeffersongoncalves/laravel-erp-buying) and [`jeffersongoncalves/laravel-erp-manufacturing`](https://github.com/jeffersongoncalves/laravel-erp-manufacturing).

## Features

- **Subcontracting BOM master** — A finished good, the quantity it is produced in, an optional link to a manufacturing `Bom`, a service item and the raw materials that must be supplied for it
- **Subcontracting Order** — A PO-like document to a supplier (via the dynamic-link `party_type` / `party_id` convention) for finished goods, carrying the supplied raw materials and the supplier warehouse they are sent to, built on the core `IsSubmittable` lifecycle (`Draft → Submitted → Cancelled`) with its own `SubcontractingOrderStatus`
- **Subcontracting Receipt** — Receives the finished goods and posts to the perpetual inventory engine: the finished good comes inbound (valued at the service rate plus the consumed raw material value) while the consumed raw materials go outbound from the supplier warehouse, with the net stock value driving the general ledger
- **`createReceipt` service** — `SubcontractingOrderService::createReceipt()` drafts a receipt for the finished-good quantities still to be received and copies the supplied raw materials over as consumed
- **Customizable Models** — Override any model via config (ModelResolver pattern)
- **Translations** — English and Brazilian Portuguese

## Compatibility

| Package | PHP | Laravel |
|---------|-----|---------|
| `^1.0`  | `^8.2` | `^11.0 \| ^12.0 \| ^13.0` |

## Installation

```bash
composer require jeffersongoncalves/laravel-erp-subcontracting
```

Publish and run the migrations (the upstream package migrations must be published too):

```bash
php artisan vendor:publish --tag="erp-core-migrations"
php artisan vendor:publish --tag="erp-accounting-migrations"
php artisan vendor:publish --tag="erp-stock-migrations"
php artisan vendor:publish --tag="erp-manufacturing-migrations"
php artisan vendor:publish --tag="erp-subcontracting-migrations"
php artisan migrate
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag="erp-subcontracting-config"
```

## The Subcontracting Receipt

The receipt is built on the stock package's `StockLedgerService` and the core `IsSubmittable` lifecycle. On `submit()` it posts two kinds of movement through the inventory engine:

```php
use JeffersonGoncalves\Erp\Subcontracting\Services\SubcontractingOrderService;

$receipt = app(SubcontractingOrderService::class)->createReceipt($subcontractingOrder);

$receipt->counterAccountId = $subcontractingExpenseAccountId;
$receipt->submit();   // posts the stock + GL impact
$receipt->cancel();   // reverses it
```

- **Finished good — inbound** to its destination warehouse, valued at the service `rate` plus a proportional share of the consumed raw material value.
- **Supplied raw material — outbound** from the supplier warehouse it was sent to, valued by the item's valuation method.

The net stock-value change (the service / conversion cost) drives the general ledger via the stock engine: **debit** the finished-good inventory account / **credit** the subcontracting counter account (Stock Received But Not Billed / subcontracting expense). Cancellation reverses both the stock ledger and the GL.

## Database Tables

All tables use the configured prefix shared across the ERP ecosystem (default: `erp_`): `subcontracting_boms`, `subcontracting_bom_items`, `subcontracting_orders`, `subcontracting_order_items`, `subcontracting_order_supplied_items`, `subcontracting_receipts`, `subcontracting_receipt_items`, `subcontracting_receipt_supplied_items`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jefferson Simão Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
