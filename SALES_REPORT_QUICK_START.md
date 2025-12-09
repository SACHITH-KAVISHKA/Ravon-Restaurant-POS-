# 🚀 Sales Report - Quick Start Guide

## Installation (Run Once)

```powershell
# 1. Run migration
php artisan migrate

# 2. Install Excel package
composer require phpoffice/phpspreadsheet

# 3. Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Start server (if not running)
php artisan serve
```

## Access

**URL:** `http://localhost:8000/sales-report`

**Required Role:** Admin

---

## Quick Reference

### Routes Added
```
GET  /sales-report                      - Main report page
GET  /sales-report/sale-details/{order} - AJAX order details
GET  /sales-report/receipt/{order}      - Print receipt
GET  /sales-report/export               - Export to Excel
DELETE /sales-report/order/{order}      - Soft delete order
```

### Files Created
```
✅ app/Http/Controllers/SalesReportController.php
✅ resources/views/sales-report/index.blade.php
✅ resources/views/sales-report/receipt.blade.php
✅ database/migrations/2025_12_09_000001_add_is_deleted_to_orders_table.php
✅ SALES_REPORT_IMPLEMENTATION_GUIDE.md (this file)
```

### Files Modified
```
✅ routes/web.php - Added sales-report routes
✅ app/Models/Order.php - Added is_deleted field
✅ resources/views/layouts/navigation.blade.php - Added Reports menu
```

---

## Features

✅ Date range filtering (defaults to today)
✅ Order type filtering
✅ Real-time totals dashboard
✅ Paginated table (100 per page)
✅ Payment breakdown (Cash/Card/Mixed)
✅ View order details modal
✅ Print receipt
✅ Export to Excel
✅ Soft delete orders
✅ Responsive design

---

## Common Tasks

### Allow Cashiers to Access Reports
Edit `routes/web.php` line ~90:
```php
// Change from:
Route::middleware(['role:admin'])

// To:
Route::middleware(['role:admin|cashier'])
```

### Change Pagination Limit
Edit `SalesReportController.php` line ~47:
```php
->paginate(100)  // Change 100 to desired number
```

### Add More Order Types
Edit `index.blade.php` line ~76-80:
```html
<option value="your_type">Your Type</option>
```

---

## Testing Checklist

- [ ] Can access /sales-report as admin
- [ ] Date filters work
- [ ] Order type filter works
- [ ] Totals calculate correctly
- [ ] View details modal opens
- [ ] Print receipt opens in new tab
- [ ] Excel export downloads
- [ ] Delete order works with confirmation
- [ ] Mobile responsive

---

## Troubleshooting

**Route not found?**
```powershell
php artisan route:clear
```

**Excel export fails?**
```powershell
composer require phpoffice/phpspreadsheet
```

**Access denied?**
- Verify you're logged in as admin
- Check: `Auth::user()->getRoleNames()`

**Modal not opening?**
- Check browser console for errors
- Verify jQuery is loaded

---

## Support

See full documentation: `SALES_REPORT_IMPLEMENTATION_GUIDE.md`

Created: December 9, 2025
Version: 1.0.0
