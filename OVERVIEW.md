# KHAN GADGET POS – SYSTEM OVERVIEW & GAP ANALYSIS

## ১. মডিউল ওভারভিউ ও বর্তমান অবস্থা (Module Status Matrix)

| # | মডিউল নাম (Module) | ডকুমেন্টের চাহিদা (Target Requirement) | বর্তমান কোডবেস অবস্থা (SalePro v5.6.1) | বাস্তবায়ন স্ট্যাটাস (Status) |
|---|---|---|---|:---:|
| 1 | **Dashboard** | ক্লিকযোগ্য কার্ড, ব্রাঞ্চ ভিত্তিক ফিল্টার, রিয়েল-টাইম ক্যাশ ও পেন্ডিং এপ্রুভাল অ্যালার্ট। | বেসিক স্ট্যাটিস্টিক কার্ড ও চার্ট আছে। | 🟡 আংশিক (Modify) |
| 2 | **Product Master & Specs** | ল্যাপটপ/গ্যাজেট স্পেক্স (Processor, RAM, ROM, Display, Graphics, Adapter, Condition, Last Border Price)। | শুধুমাত্র সাধারণ খুচরা পণ্যের ফিল্ড আছে (Name, Code, Price, Cost)। | 🔴 অনুপস্থিত (Develop) |
| 3 | **Serial Lifecycle** | প্রতি সিরিয়ালের আলাদা টেবিল, হিস্ট্রি ও স্ট্যাটাস (Available, Reserved, Sold, Service, Damaged)। | শুধু কমা-সেপারেটেড টেক্সট স্ট্রিং হিসেবে `imei_number` আছে। | 🔴 অনুপস্থিত (Develop) |
| 4 | **Quick Product Paste** | এক্সেল/গুগল শিট থেকে ট্যাব সেপারেটেড পেস্ট, কলাম ঠিক রাখা, লোকাল প্রসেসর নরম্যালাইজেশন। | শুধুমাত্র ফাইল আপলোড (CSV) আছে, লাইভ ক্লিপবোর্ড পেস্ট নেই। | 🔴 অনুপস্থিত (Develop) |
| 5 | **Purchase & Shipment** | আন্তর্জাতিক পারচেজ, কার্গো ট্র্যাকিং, হ্যান্ড ক্যারি, শিপিং কস্ট, বাংলাদেশ পৌঁছানো ও রিসিভ। | সাধারণ লোকাল পারচেজ আছে; কার্গো/হ্যান্ড ক্যারি শিপমেন্ট ট্র্যাকিং নেই। | 🔴 অনুপস্থিত (Develop) |
| 6 | **Transfer with Acceptance** | Source Transfer Send ──► In-transit ──► Destination Branch Receive/Accept করলে স্টক আপডেট। | ট্রান্সফার স্ট্যাটাস আছে কিন্তু প্রাপকের আনুষ্ঠানিক একসেপ্টেন্স ওয়ার্কফ্লো নেই। | 🟡 আংশিক (Modify) |
| 7 | **Cash Transfer & Refund** | নির্দিষ্ট ১০টি একাউন্ট রুট, রিসিভার এপ্রুভাল, রিজেক্ট হলে কারণসহ রিফান্ড ওয়ার্কফ্লো। | অতি সাধারণ `MoneyTransfer` (কোনো একসেপ্টেন্স, রিজেকশন বা রিফান্ড নেই)। | 🔴 অনুপস্থিত (Develop) |
| 8 | **POS Dedicated Layout** | ৩-কলাম স্ক্রিন (বামে ক্যাটাগরি, মাঝে স্পেক্সসহ ল্যাপটপ কার্ড, ডানে কার্ট ও পেমেন্ট), সিরিয়াল কন্ডিশন সিলেকশন। | সাধারণ ২-কলাম স্টোর স্ক্রিন, গ্যাজেট স্পেক্স বা সিরিয়াল কন্ডিশন ভিউ নেই। | 🟡 আংশিক (Modify) |
| 9 | **Pre-Order Module** | স্টক না থাকলে অন্য ব্রাঞ্চ/ওয়ারহাউস থেকে বুকিং, সোর্স ব্রাঞ্চে রিকোয়েস্ট, রেডি ও ডেলিভারি। | প্রি-অর্ডার নামে কোনো কনসেপ্ট বা সিস্টেম নেই। | 🔴 অনুপস্থিত (Develop) |
| 10 | **Warranty Claim & Service** | সিরিয়াল সার্চ ──► ক্লেইম ──► রিসিভ ──► সার্ভিসিং ──► রিপেয়ার কমপ্লিট ──► ডেলিভারি/রিজেক্ট। | প্রোডাক্ট টেবিলে শুধু 'warranty' ফিল্ড আছে, কোনো সার্ভিস ওয়ার্কফ্লো নেই। | 🔴 অনুপস্থিত (Develop) |
| 11 | **Quotation to Sale** | কোটেশন তৈরি এবং এক ক্লিকে সেলে রূপান্তর। | ইতিমধ্যে কার্যকর আছে (`QuotationController`)। | 🟢 সম্পূর্ণ প্রস্তুত (Ready) |
| 12 | **Dedicated Damage Module** | সিরিয়াল সিলেক্ট করে ড্যামেজ কারণ ও রেসপন্সিবল পারসনসহ স্টক থেকে অপসারণ। | শুধু সাধারণ কোয়ান্টিটি যোগ/বিয়োগ এডজাস্টমেন্ট আছে। | 🔴 অনুপস্থিত (Develop) |
| 13 | **Expense Management** | নির্দিষ্ট ব্রাঞ্চ ও একাউন্ট ভিত্তিক এক্সপেন্স এন্ট্রি। | ইতিমধ্যে কার্যকর আছে (`ExpenseController`)। | 🟢 সম্পূর্ণ প্রস্তুত (Ready) |
| 14 | **Daily Account Summary PDF** | প্রিভিয়াস ব্যালেন্স, সেলস, এক্সপেন্স, ক্যাশ সামারি ও ট্রান্সফার সমন্বয়ে ২-৩ পৃষ্ঠার কম্প্যাক্ট রিপোর্ট। | কোনো সমন্বিত ২-৩ পেজের কাস্টমারবিহীন ডেইলি একাউন্টস PDF নেই। | 🔴 অনুপস্থিত (Develop) |
| 15 | **HR Loan & Advance** | কর্মচারীর লোন ও এডভান্স ট্র্যাকিং এবং স্যালারি থেকে অটো ডিডাকশন। | সাধারণ পে-রোল ও এটেনডেন্স আছে, লোন/এডভান্স লেজার নেই। | 🟡 আংশিক (Modify) |
| 16 | **Role & Permissions** | Spatie ডাইনামিক রোল ও পারমিশন কন্ট্রোল। | ইতিমধ্যে কার্যকর আছে (Admin, Staff, Manager ইত্যাদি)। | 🟢 সম্পূর্ণ প্রস্তুত (Ready) |

---

## ২. ডাটাবেসে আবশ্যক পরিবর্তনসমূহ (Database Architectural Requirements)

### নতুন টেবিল প্রয়োজন (New Tables to Create):
1. `product_serials` (id, product_id, serial_number, condition, detailed_condition, status, current_location_type, current_location_id, purchase_id, sale_id, created_at, updated_at)
2. `shipments` (id, purchase_id, shipment_type, shipment_number, cargo_details, shipment_cost, responsible_person, expected_arrival_date, actual_arrival_date, status, remarks)
3. `pre_orders` (id, reference_no, customer_id, product_id, source_branch_id, destination_branch_id, serial_id, status, expected_delivery_date, notes)
4. `warranties` (id, serial_id, sale_id, customer_id, claim_date, issue_description, status, received_by, service_notes, completion_date, delivery_date)
5. `damages` (id, serial_id, product_id, warehouse_id, reason_type, responsible_user_id, status, approved_by, remarks)
6. `cash_transfers` (id, reference_no, from_account_id, to_account_id, transfer_type, amount, status, rejection_reason, refund_status, initiated_by, approved_by)
7. `employee_loans` (id, employee_id, loan_type, amount, installments, paid_amount, status)

### বিদ্যমান টেবিলে কলাম সংযোজন (Columns to Add):
- **`products`:** `processor`, `ram`, `storage`, `display`, `dedicated_graphics`, `adapter_condition`, `product_condition`, `discount_price`, `last_border_price`.
- **`transfers`:** `accepted_by`, `accepted_at`, `rejection_reason`.
- **`users`:** `can_view_purchase_price` (boolean flag).

---

## ৩. মাস্টার বিজনেস প্রসেস ফ্লো (Master Business Flow)

```
       [ 1. PURCHASE & SERIAL ENTRY ]
                     │
       [ 2. SHIPMENT & CARGO TRACKING ]
                     │
       [ 3. BANGLADESH CENTRAL WAREHOUSE RECEIVE ]
                     │
       [ 4. CONTROLLED BRANCH TRANSFER (ACCEPTANCE) ]
                     │
       ┌─────────────┴─────────────┐
       ▼                           ▼
[ 5. POS DIRECT SALE ]     [ 6. INTER-BRANCH PRE-ORDER ]
       │                           │
       ├───────────────────────────┘
       ▼
[ 7. SERIAL MARKED SOLD & INVOICE GENERATION ]
       │
       ├───────────────────────────┬───────────────────────────┐
       ▼                           ▼                           ▼
[ 8. CASH & TRANSFER MGT ]  [ 9. WARRANTY SERVICE ]    [ 10. DAMAGE REMOVAL ]
       │
       ▼
[ 11. DAILY ACCOUNT PDF & CLOSING REPORT ]
```
