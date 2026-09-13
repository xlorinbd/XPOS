<?php

namespace Database\Seeders\Tenant;

use Database\Seeders\Tenant\BarcodeSeeder;
use Database\Seeders\Tenant\ExternalServicesSeeder;
use Database\Seeders\Tenant\LanguagesTableSeeder;
use Database\Seeders\Tenant\TranslationsTableSeeder;
use Database\Seeders\Tenant\InvoiceSettingsSeeder;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public static $tenantData = [];

    public function run()
    {
        $this->call(BarcodeSeeder::class);
        $this->call(ExternalServicesSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(TranslationsTableSeeder::class);
        $this->call(InvoiceSettingsSeeder::class);

        if (!DB::table('general_settings')->count()) {
            DB::table('general_settings')->insert([
                [
                    'id' => 1,
                    'site_title' => !empty(self::$tenantData) ? self::$tenantData['site_title'] : 'KGERP',
                    'site_logo' => !empty(self::$tenantData) ? self::$tenantData['site_logo'] : '20250102042651.png',
                    'is_rtl' => 0,
                    'currency' => '1',
                    'package_id' => !empty(self::$tenantData) ? self::$tenantData['package_id'] : 0,
                    'subscription_type' => !empty(self::$tenantData) ? self::$tenantData['subscription_type'] : 'monthly',
                    'staff_access' => 'own',
                    'without_stock' => 'no',
                    'date_format' => 'd/m/Y',
                    'developed_by' => !empty(self::$tenantData) ? self::$tenantData['developed_by'] : 'Xlorin',
                    'invoice_format' => 'standard',
                    'decimal' => 2,
                    'state' => 1,
                    'theme' => 'default.css',
                    'modules' => !empty(self::$tenantData) ? self::$tenantData['modules'] : NULL,
                    'currency_position' => 'prefix',
                    'expiry_date' => !empty(self::$tenantData) ? self::$tenantData['expiry_date'] : '1970-01-01',
                    'expiry_type' => 'days',
                    'expiry_value' => '0',
                    'is_zatca' => NULL,
                    'company_name' => NULL,
                    'vat_registration_number' => NULL,
                    'is_packing_slip' => 0,
                ]
            ]);
        }

        if (!DB::table('users')->count()) {
            DB::table('users')->insert([
                [
                    'id' => 1,
                    'name' => !empty(self::$tenantData) ? self::$tenantData['name'] : 'admin',
                    'email' => !empty(self::$tenantData) ? self::$tenantData['email'] : 'admin@gmail.com',
                    'password' => !empty(self::$tenantData) ? self::$tenantData['password'] : '$2y$10$DWAHTfjcvwCpOCXaJg11MOhsqns03uvlwiSUOQwkHL2YYrtrXPcL6',
                    'remember_token' => '6mN44MyRiQZfCi0QvFFIYAU9LXIUz9CdNIlrRS5Lg8wBoJmxVu8auzTP42ZW',
                    'phone' => !empty(self::$tenantData) ? self::$tenantData['phone'] : '12112',
                    'company_name' => !empty(self::$tenantData) ? self::$tenantData['company_name'] : 'lioncoders',
                    'role_id' => 1,
                    'biller_id' => NULL,
                    'warehouse_id' => NULL,
                    'is_active' => 1,
                    'is_deleted' => 0,
                ]
            ]);
        }

        if (!DB::table('roles')->count()) {
            DB::table('roles')->insert([
                [
                    'id' => 1,
                    'name' => 'Admin',
                    'description' => 'admin can access all data...',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 2,
                    'name' => 'Owner',
                    'description' => 'Staff of shop',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 4,
                    'name' => 'staff',
                    'description' => 'staff has specific acess...',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 5,
                    'name' => 'Customer',
                    'description' => NULL,
                    'is_active' => 1,
                    'guard_name' => 'web',
                ]
            ]);
        }

        ///permissions table data insert start///
        $existing_permissions = DB::table('permissions')
        ->select('name', 'guard_name')
        ->get();

        $existingMap = [];

        foreach ($existing_permissions as $item) {
            $existingMap[$item->name . '|' . $item->guard_name] = true;
        }

        $permission_data = [
                [
                    'id' => 4,
                    'name' => 'products-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 5,
                    'name' => 'products-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 6,
                    'name' => 'products-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 7,
                    'name' => 'products-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 8,
                    'name' => 'purchases-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 9,
                    'name' => 'purchases-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 10,
                    'name' => 'purchases-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 11,
                    'name' => 'purchases-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 12,
                    'name' => 'sales-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 13,
                    'name' => 'sales-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 14,
                    'name' => 'sales-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 15,
                    'name' => 'sales-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 16,
                    'name' => 'quotes-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 17,
                    'name' => 'quotes-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 18,
                    'name' => 'quotes-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 19,
                    'name' => 'quotes-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 20,
                    'name' => 'transfers-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 21,
                    'name' => 'transfers-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 22,
                    'name' => 'transfers-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 23,
                    'name' => 'transfers-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 24,
                    'name' => 'returns-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 25,
                    'name' => 'returns-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 26,
                    'name' => 'returns-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 27,
                    'name' => 'returns-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 28,
                    'name' => 'customers-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 29,
                    'name' => 'customers-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 30,
                    'name' => 'customers-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 31,
                    'name' => 'customers-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 32,
                    'name' => 'suppliers-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 33,
                    'name' => 'suppliers-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 34,
                    'name' => 'suppliers-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 35,
                    'name' => 'suppliers-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 36,
                    'name' => 'product-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 37,
                    'name' => 'purchase-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 38,
                    'name' => 'sale-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 39,
                    'name' => 'customer-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 40,
                    'name' => 'due-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 41,
                    'name' => 'users-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 42,
                    'name' => 'users-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 43,
                    'name' => 'users-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 44,
                    'name' => 'users-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 45,
                    'name' => 'profit-loss',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 46,
                    'name' => 'best-seller',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 47,
                    'name' => 'daily-sale',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 48,
                    'name' => 'monthly-sale',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 49,
                    'name' => 'daily-purchase',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 50,
                    'name' => 'monthly-purchase',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 51,
                    'name' => 'payment-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 52,
                    'name' => 'warehouse-stock-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 53,
                    'name' => 'product-qty-alert',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 54,
                    'name' => 'supplier-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 55,
                    'name' => 'expenses-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 56,
                    'name' => 'expenses-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 57,
                    'name' => 'expenses-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 58,
                    'name' => 'expenses-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 59,
                    'name' => 'general_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 60,
                    'name' => 'mail_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 61,
                    'name' => 'pos_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 62,
                    'name' => 'hrm_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 63,
                    'name' => 'purchase-return-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 64,
                    'name' => 'purchase-return-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 65,
                    'name' => 'purchase-return-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 66,
                    'name' => 'purchase-return-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 67,
                    'name' => 'account-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 68,
                    'name' => 'balance-sheet',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 69,
                    'name' => 'account-statement',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 70,
                    'name' => 'department',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 71,
                    'name' => 'attendance',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 72,
                    'name' => 'payroll',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 73,
                    'name' => 'employees-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 74,
                    'name' => 'employees-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 75,
                    'name' => 'employees-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 76,
                    'name' => 'employees-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 77,
                    'name' => 'user-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 78,
                    'name' => 'stock_count',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 79,
                    'name' => 'adjustment',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 80,
                    'name' => 'sms_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 81,
                    'name' => 'create_sms',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 82,
                    'name' => 'print_barcode',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 83,
                    'name' => 'empty_database',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 84,
                    'name' => 'customer_group',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 85,
                    'name' => 'unit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 86,
                    'name' => 'tax',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 87,
                    'name' => 'gift_card',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 88,
                    'name' => 'coupon',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 89,
                    'name' => 'holiday',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 90,
                    'name' => 'warehouse-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 91,
                    'name' => 'warehouse',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 92,
                    'name' => 'brand',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 93,
                    'name' => 'billers-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 94,
                    'name' => 'billers-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 95,
                    'name' => 'billers-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 96,
                    'name' => 'billers-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 97,
                    'name' => 'money-transfer',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 98,
                    'name' => 'category',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 99,
                    'name' => 'delivery',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 100,
                    'name' => 'send_notification',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 101,
                    'name' => 'today_sale',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 102,
                    'name' => 'today_profit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 103,
                    'name' => 'currency',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 104,
                    'name' => 'backup_database',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 105,
                    'name' => 'reward_point_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 106,
                    'name' => 'revenue_profit_summary',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 107,
                    'name' => 'cash_flow',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 108,
                    'name' => 'monthly_summary',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 109,
                    'name' => 'yearly_report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 110,
                    'name' => 'discount_plan',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 111,
                    'name' => 'discount',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 112,
                    'name' => 'product-expiry-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 113,
                    'name' => 'purchase-payment-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 114,
                    'name' => 'purchase-payment-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 115,
                    'name' => 'purchase-payment-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 116,
                    'name' => 'purchase-payment-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 117,
                    'name' => 'sale-payment-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 118,
                    'name' => 'sale-payment-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 119,
                    'name' => 'sale-payment-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 120,
                    'name' => 'sale-payment-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 121,
                    'name' => 'all_notification',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 122,
                    'name' => 'sale-report-chart',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 123,
                    'name' => 'dso-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 124,
                    'name' => 'product_history',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 125,
                    'name' => 'supplier-due-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 126,
                    'name' => 'custom_field',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 127,
                    'name' => 'incomes-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 128,
                    'name' => 'incomes-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 129,
                    'name' => 'incomes-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 130,
                    'name' => 'incomes-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 131,
                    'name' => 'packing_slip_challan',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 132,
                    'name' => 'biller-report',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 133,
                    'name' => 'payment_gateway_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 134,
                    'name' => 'barcode_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 135,
                    'name' => 'language_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 136,
                    'name' => 'addons',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 137,
                    'name' => 'account-selection',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 138,
                    'name' => 'invoice_setting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 139,
                    'name' => 'invoice_create_edit_delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 140,
                    'name' => 'handle_discount',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 145,
                    'name' => 'products-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 146,
                    'name' => 'purchases-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 147,
                    'name' => 'sales-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 148,
                    'name' => 'customers-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 149,
                    'name' => 'billers-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 150,
                    'name' => 'suppliers-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 151,
                    'name' => 'categories-add',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 152,
                    'name' => 'categories-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 153,
                    'name' => 'categories-index',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 154,
                    'name' => 'categories-edit',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 155,
                    'name' => 'categories-delete',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 156,
                    'name' => 'role_permission',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 157,
                    'name' => 'cart-product-update',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 158,
                    'name' => 'transfers-import',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 159,
                    'name' => 'change_sale_date',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 160,
                    'name' => 'sidebar_product',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 161,
                    'name' => 'sidebar_purchase',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 162,
                    'name' => 'sidebar_sale',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 163,
                    'name' => 'sidebar_quotation',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 164,
                    'name' => 'sidebar_transfer',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 165,
                    'name' => 'sidebar_expense',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 166,
                    'name' => 'sidebar_income',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 167,
                    'name' => 'sidebar_accounting',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 168,
                    'name' => 'sidebar_hrm',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 169,
                    'name' => 'sidebar_people',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 170,
                    'name' => 'sidebar_reports',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 171,
                    'name' => 'sidebar_settings',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 172,
                    'name' => 'sale_export',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 173,
                    'name' => 'product_export',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 174,
                    'name' => 'purchase_export',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 175,
                    'name' => 'designations',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 176,
                    'name' => 'shift',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 177,
                    'name' => 'overtime',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 178,
                    'name' => 'leave-type',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 179,
                    'name' => 'leave',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 180,
                    'name' => 'hrm-panel',
                    'guard_name' => 'web',
                ],
                [
                    'id' => 181,
                    'name' => 'sale-agents',
                    'guard_name' => 'web',
                ],
        ];

        $insertData = [];

        foreach ($permission_data as $row) {
            $lookupKey = $row['name'] . '|' . $row['guard_name'];

            if (!isset($existingMap[$lookupKey])) {
                $insertData[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'guard_name' => $row['guard_name']
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('permissions')->insert($insertData);
        }
        ///permissions table data insert end///

        ///role_has_permissions table data insert start///
        $existing_role_has_permissions = DB::table('role_has_permissions')
        ->select('permission_id', 'role_id')
        ->get();

        $existingMap = [];

        foreach ($existing_role_has_permissions as $item) {
            $existingMap[$item->permission_id . '|' . $item->role_id] = true;
        }

        $basic_permissions_role = [];

        if(!config('database.connections.saleprosaas_landlord')) {
            foreach ($permission_data as $row) {
                $basic_permissions_role[] = [
                    'permission_id' => $row['id'],
                    'role_id' => 1,
                ];
            }
        }
        else {
            $basic_permissions_role = [
                [
                    'permission_id' => 4,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 5,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 6,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 7,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 8,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 9,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 10,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 11,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 12,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 13,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 14,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 15,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 28,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 29,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 30,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 31,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 32,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 33,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 34,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 35,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 41,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 42,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 43,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 44,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 59,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 60,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 61,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 80,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 81,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 82,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 83,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 84,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 85,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 86,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 87,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 88,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 91,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 92,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 93,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 94,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 95,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 96,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 98,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 100,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 101,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 102,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 103,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 104,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 105,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 106,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 107,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 108,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 109,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 110,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 111,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 113,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 114,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 115,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 116,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 117,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 118,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 119,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 120,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 121,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 124,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 126,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 131,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 133,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 134,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 135,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 137,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 138,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 139,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 140,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 145,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 146,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 147,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 148,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 149,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 150,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 151,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 152,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 153,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 154,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 155,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 156,
                    'role_id' => 1,
                ],
                [
                    'permission_id' => 157,
                    'role_id' => 1,
                ]
            ];
        }

        $merged_permissions_role = !empty(self::$tenantData) ? array_merge($basic_permissions_role, self::$tenantData['package_permissions_role']) : $basic_permissions_role;

        $insertData = [];

        foreach ($merged_permissions_role as $row) {
            $lookupKey = $row['permission_id'] . '|' . $row['role_id'];

            if (!isset($existingMap[$lookupKey])) {
                $insertData[] = [
                    'permission_id' => $row['permission_id'],
                    'role_id' => $row['role_id'],
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('role_has_permissions')->insert($insertData);
        }
        ///role_has_permissions table data insert end///

        if (!DB::table('accounts')->count()) {
            DB::table('accounts')->insert([
                [
                    'id' => 1,
                    'account_no' => '019912229',
                    'name' => 'Sales Account',
                    'initial_balance' => 0.0,
                    'total_balance' => 0.0,
                    'note' => 'This is the default account.',
                    'is_default' => 1,
                    'is_active' => 1,
                    'code' => NULL,
                    'type' => 'Bank Account',
                    'parent_account_id' => NULL,
                    'is_payment' => 1,
                ],
            ]);
        }

        if (!DB::table('billers')->count()) {
            DB::table('billers')->insert([
                [
                    'id' => 1,
                    'name' => 'Test Biller',
                    'image' => NULL,
                    'company_name' => 'Test Company',
                    'vat_number' => NULL,
                    'email' => 'test@gmail.com',
                    'phone_number' => '12312',
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('brands')->count()) {
            DB::table('brands')->insert([
                ['id' => 1, 'title' => 'Apple', 'image' => NULL, 'is_active' => 1],
                ['id' => 2, 'title' => 'Dell', 'image' => NULL, 'is_active' => 1],
                ['id' => 3, 'title' => 'HP', 'image' => NULL, 'is_active' => 1],
                ['id' => 4, 'title' => 'Lenovo', 'image' => NULL, 'is_active' => 1],
                ['id' => 5, 'title' => 'ASUS', 'image' => NULL, 'is_active' => 1],
                ['id' => 6, 'title' => 'Acer', 'image' => NULL, 'is_active' => 1],
                ['id' => 7, 'title' => 'Microsoft', 'image' => NULL, 'is_active' => 1],
                ['id' => 8, 'title' => 'Samsung', 'image' => NULL, 'is_active' => 1],
                ['id' => 9, 'title' => 'MSI', 'image' => NULL, 'is_active' => 1],
                ['id' => 10, 'title' => 'Razer', 'image' => NULL, 'is_active' => 1],
                ['id' => 11, 'title' => 'Logitech', 'image' => NULL, 'is_active' => 1],
                ['id' => 12, 'title' => 'Baseus', 'image' => NULL, 'is_active' => 1],
                ['id' => 13, 'title' => 'Anker', 'image' => NULL, 'is_active' => 1],
            ]);
        }

        if (!DB::table('categories')->count()) {
            DB::table('categories')->insert([
                ['id' => 1, 'name' => 'Laptops & Computers', 'image' => NULL, 'parent_id' => NULL, 'is_active' => 1],
                ['id' => 2, 'name' => 'Business Laptops', 'image' => NULL, 'parent_id' => 1, 'is_active' => 1],
                ['id' => 3, 'name' => 'Gaming Laptops', 'image' => NULL, 'parent_id' => 1, 'is_active' => 1],
                ['id' => 4, 'name' => 'Ultrabooks & Thin Laptops', 'image' => NULL, 'parent_id' => 1, 'is_active' => 1],
                ['id' => 5, 'name' => 'MacBooks', 'image' => NULL, 'parent_id' => 1, 'is_active' => 1],
                ['id' => 6, 'name' => 'Chromebooks', 'image' => NULL, 'parent_id' => 1, 'is_active' => 1],
                ['id' => 7, 'name' => 'Laptop Accessories', 'image' => NULL, 'parent_id' => NULL, 'is_active' => 1],
                ['id' => 8, 'name' => 'Adapters & Chargers', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 9, 'name' => 'Laptop Bags & Sleeves', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 10, 'name' => 'Keyboards & Mice', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 11, 'name' => 'RAM & SSD Upgrades', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 12, 'name' => 'USB-C Hubs & Docks', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 13, 'name' => 'Cooling Pads & Stands', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 14, 'name' => 'Cables & Converters', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
                ['id' => 15, 'name' => 'Laptop Displays & Monitors', 'image' => NULL, 'parent_id' => 7, 'is_active' => 1],
            ]);
        }

        if (!DB::table('currencies')->count()) {
            DB::table('currencies')->insert([
                [
                    'id' => 1,
                    'name' => 'US Dollar',
                    'code' => 'USD',
                    'exchange_rate' => 1.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('customer_groups')->count()) {
            DB::table('customer_groups')->insert([
                [
                    'id' => 1,
                    'name' => 'General',
                    'percentage' => '0',
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('customers')->count()) {
            DB::table('customers')->insert([
                [
                    'id' => 1,
                    'customer_group_id' => 1,
                    'user_id' => NULL,
                    'name' => 'John Doe',
                    'company_name' => 'Test Company',
                    'email' => 'john@gmail.com',
                    'phone_number' => '231312',
                    'tax_no' => NULL,
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'points' => NULL,
                    'is_active' => 1,
                    'deposit' => NULL,
                    'expense' => NULL,
                ]
            ]);
        }

        if (!DB::table('pos_setting')->count()) {
            DB::table('pos_setting')->insert([
                [
                    'id' => 1,
                    'customer_id' => 1,
                    'warehouse_id' => 1,
                    'biller_id' => 1,
                    'product_number' => 2,
                    'keybord_active' => 1,
                    'is_table' => 0,
                    'send_sms' => 0,
                    'stripe_public_key' => NULL,
                    'stripe_secret_key' => NULL,
                    'paypal_live_api_username' => NULL,
                    'paypal_live_api_password' => NULL,
                    'paypal_live_api_secret' => NULL,
                    'payment_options' => 'cash,card,cheque,gift_card,deposit,paypal',
                    'invoice_option' => 'thermal',
                    'thermal_invoice_size' => '80',
                ]
            ]);
        }

        if (!DB::table('product_purchases')->count()) {
            DB::table('product_purchases')->insert([
                [
                    'id' => 1,
                    'purchase_id' => 1,
                    'product_id' => 1,
                    'product_batch_id' => NULL,
                    'variant_id' => NULL,
                    'imei_number' => NULL,
                    'qty' => 10.0,
                    'recieved' => 10.0,
                    'return_qty' => 0.0,
                    'purchase_unit_id' => 1,
                    'net_unit_cost' => 10.0,
                    'discount' => 0.0,
                    'tax_rate' => 10.0,
                    'tax' => 10.0,
                    'total' => 110.0,
                ]
            ]);
        }

        if (!DB::table('product_warehouse')->count()) {
            DB::table('product_warehouse')->insert([
                [
                    'id' => 1,
                    'product_id' => '1',
                    'product_batch_id' => NULL,
                    'variant_id' => NULL,
                    'imei_number' => NULL,
                    'warehouse_id' => 1,
                    'qty' => 10.0,
                    'price' => 20.0,
                ]
            ]);
        }

        if (!DB::table('products')->count()) {
            DB::table('products')->insert([
                [
                    'id' => 1,
                    'name' => 'Lenovo ThinkPad T14 Gen 2 (Core i5 11th Gen, 16GB, 512GB SSD)',
                    'code' => 'LNV-TP-T14G2',
                    'model' => 'ThinkPad T14 Gen 2',
                    'processor' => 'Intel Core i5-1145G7',
                    'ram' => '16GB DDR4',
                    'storage' => '512GB NVMe SSD',
                    'display' => '14" FHD IPS Anti-Glare',
                    'dedicated_graphics' => 'Intel Iris Xe',
                    'adapter_condition' => 'Original 65W Type-C Adapter',
                    'product_condition' => 'used',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 4,
                    'category_id' => 2,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 45000.0,
                    'price' => 58000.0,
                    'discount_price' => 56000.0,
                    'last_border_price' => 54000.0,
                    'wholesale_price' => NULL,
                    'qty' => 10.0,
                    'alert_quantity' => 2,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>High performance business laptop with military-grade durability.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'Dell XPS 15 9520 (Core i7 12th Gen, 32GB, 1TB SSD, RTX 3050Ti)',
                    'code' => 'DELL-XPS-9520',
                    'model' => 'XPS 15 9520',
                    'processor' => 'Intel Core i7-12700H',
                    'ram' => '32GB DDR5',
                    'storage' => '1TB NVMe PCIe Gen4 SSD',
                    'display' => '15.6" 3.5K OLED InfinityEdge Touch',
                    'dedicated_graphics' => 'NVIDIA GeForce RTX 3050 Ti 4GB',
                    'adapter_condition' => 'Original 130W Type-C Adapter',
                    'product_condition' => 'open_box',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 4,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 135000.0,
                    'price' => 165000.0,
                    'discount_price' => 160000.0,
                    'last_border_price' => 155000.0,
                    'wholesale_price' => NULL,
                    'qty' => 5.0,
                    'alert_quantity' => 1,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Premium creator laptop with OLED infinity display.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 3,
                    'name' => 'Apple MacBook Pro 14 M2 Pro (16GB, 512GB SSD, Space Gray)',
                    'code' => 'APP-MBP14-M2',
                    'model' => 'MacBook Pro 14 (2023)',
                    'processor' => 'Apple M2 Pro (10-Core CPU, 16-Core GPU)',
                    'ram' => '16GB Unified Memory',
                    'storage' => '512GB SSD',
                    'display' => '14.2" Liquid Retina XDR 120Hz',
                    'dedicated_graphics' => 'Apple 16-Core GPU',
                    'adapter_condition' => 'Original 67W USB-C Adapter + MagSafe 3 Cable',
                    'product_condition' => 'brand_new',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 5,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 190000.0,
                    'price' => 225000.0,
                    'discount_price' => 220000.0,
                    'last_border_price' => 215000.0,
                    'wholesale_price' => NULL,
                    'qty' => 4.0,
                    'alert_quantity' => 1,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Supercharged by M2 Pro for professional workflows.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 4,
                    'name' => 'Original 65W USB-C Type-C Laptop Fast Charger',
                    'code' => 'ACC-CHG-65W',
                    'model' => '65W GaN Type-C',
                    'processor' => NULL,
                    'ram' => NULL,
                    'storage' => NULL,
                    'display' => NULL,
                    'dedicated_graphics' => NULL,
                    'adapter_condition' => NULL,
                    'product_condition' => 'brand_new',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 8,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1800.0,
                    'price' => 2800.0,
                    'discount_price' => 2600.0,
                    'last_border_price' => 2400.0,
                    'wholesale_price' => NULL,
                    'qty' => 50.0,
                    'alert_quantity' => 5,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 0,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Universal 65W Type-C charger for Dell, HP, Lenovo, and MacBook.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 5,
                    'name' => 'Lenovo 15.6-inch Laptop Commuter Backpack',
                    'code' => 'ACC-BAG-LNV',
                    'model' => 'B210 Casual',
                    'processor' => NULL,
                    'ram' => NULL,
                    'storage' => NULL,
                    'display' => NULL,
                    'dedicated_graphics' => NULL,
                    'adapter_condition' => NULL,
                    'product_condition' => 'brand_new',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 4,
                    'category_id' => 9,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1200.0,
                    'price' => 2000.0,
                    'discount_price' => 1900.0,
                    'last_border_price' => 1700.0,
                    'wholesale_price' => NULL,
                    'qty' => 30.0,
                    'alert_quantity' => 5,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 0,
                    'featured' => 0,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Water-repellent fabric commuter backpack for laptops up to 15.6 inches.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 6,
                    'name' => 'Logitech MX Master 3S Performance Wireless Mouse',
                    'code' => 'ACC-MOU-MX3S',
                    'model' => 'MX Master 3S',
                    'processor' => NULL,
                    'ram' => NULL,
                    'storage' => NULL,
                    'display' => NULL,
                    'dedicated_graphics' => NULL,
                    'adapter_condition' => NULL,
                    'product_condition' => 'brand_new',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 11,
                    'category_id' => 10,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 8500.0,
                    'price' => 11500.0,
                    'discount_price' => 11000.0,
                    'last_border_price' => 10500.0,
                    'wholesale_price' => NULL,
                    'qty' => 15.0,
                    'alert_quantity' => 2,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 0,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quiet clicks, 8K DPI any-surface tracking ergonomics.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 7,
                    'name' => 'Samsung 980 Pro 1TB PCIe 4.0 NVMe M.2 SSD',
                    'code' => 'ACC-SSD-1TB',
                    'model' => '980 Pro 1TB',
                    'processor' => NULL,
                    'ram' => NULL,
                    'storage' => NULL,
                    'display' => NULL,
                    'dedicated_graphics' => NULL,
                    'adapter_condition' => NULL,
                    'product_condition' => 'brand_new',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 8,
                    'category_id' => 11,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 8800.0,
                    'price' => 12000.0,
                    'discount_price' => 11500.0,
                    'last_border_price' => 11000.0,
                    'wholesale_price' => NULL,
                    'qty' => 20.0,
                    'alert_quantity' => 3,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 0,
                    'featured' => 0,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Next-gen NVMe SSD with read speeds up to 7000 MB/s for laptop upgrade.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 8,
                    'name' => 'Baseus 8-in-1 Dual 4K HDMI USB-C Docking Station',
                    'code' => 'ACC-HUB-BAS8',
                    'model' => 'Baseus Metal Gleam 8-in-1',
                    'processor' => NULL,
                    'ram' => NULL,
                    'storage' => NULL,
                    'display' => NULL,
                    'dedicated_graphics' => NULL,
                    'adapter_condition' => NULL,
                    'product_condition' => 'brand_new',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 12,
                    'category_id' => 12,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 3200.0,
                    'price' => 4800.0,
                    'discount_price' => 4500.0,
                    'last_border_price' => 4200.0,
                    'wholesale_price' => NULL,
                    'qty' => 25.0,
                    'alert_quantity' => 3,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => NULL,
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 0,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Multi-port hub with 100W PD charging, 4K HDMI, Gigabit Ethernet, and USB 3.0.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('purchases')->count()) {
            DB::table('purchases')->insert([
                [
                    'id' => 1,
                    'reference_no' => 'pr-20230528-125929',
                    'user_id' => 1,
                    'warehouse_id' => 1,
                    'supplier_id' => NULL,
                    'currency_id' => 1,
                    'exchange_rate' => 1.0,
                    'item' => 1,
                    'total_qty' => 10,
                    'total_discount' => 0.0,
                    'total_tax' => 10.0,
                    'total_cost' => 110.0,
                    'order_tax_rate' => 0.0,
                    'order_tax' => 0.0,
                    'order_discount' => 0.0,
                    'shipping_cost' => 0.0,
                    'grand_total' => 110.0,
                    'paid_amount' => 0.0,
                    'status' => 1,
                    'payment_status' => 1,
                    'document' => NULL,
                    'note' => NULL,
                ]
            ]);
        }

        if (!DB::table('suppliers')->count()) {
            DB::table('suppliers')->insert([
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'image' => NULL,
                    'company_name' => 'Test Company',
                    'vat_number' => NULL,
                    'email' => 'john@gmail.com',
                    'phone_number' => '231312',
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('taxes')->count()) {
            DB::table('taxes')->insert([
                [
                    'id' => 1,
                    'name' => 'VAT 10%',
                    'rate' => 10.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('units')->count()) {
            DB::table('units')->insert([
                [
                    'id' => 1,
                    'unit_code' => 'Pc',
                    'unit_name' => 'piece',
                    'base_unit' => NULL,
                    'operator' => '*',
                    'operation_value' => 1.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('warehouses')->count()) {
            DB::table('warehouses')->insert([
                [
                    'id' => 1,
                    'name' => 'Test Shop',
                    'phone' => '9991111',
                    'email' => NULL,
                    'address' => 'Test address',
                    'is_active' => 1,
                ]
            ]);
        }
    }
}
