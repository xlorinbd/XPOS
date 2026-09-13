# KHAN GADGET POS – CONTEXT & BUSINESS ARCHITECTURE

## ১. প্রজেক্ট পরিচিতি ও লক্ষ্য (Project Overview)
**Khan Gadget POS** হলো একটি বিশেষায়িত গ্যাজেট ও মাল্টি-ব্রাঞ্চ রিটেইল ইআরপি ও পিওএস সিস্টেম। এটি সাধারণ রিটেইল শপ থেকে সম্পূর্ণ আলাদা, কারণ এতে প্রতিটি ল্যাপটপ/গ্যাজেটের জন্য স্বতন্ত্র **সিরিয়াল/আইএমইআই (Serial/IMEI) লাইফসাইকেল ট্র্যাকিং**, টেকনিক্যাল স্পেসিফিকেশন (Processor, RAM, Storage, Condition ইত্যাদি), আন্তর্জাতিক পারচেজ ও কার্গো শিপমেন্ট ট্র্যাকিং, আন্তঃশাখা স্টক ও ক্যাশ ট্রান্সফার একসেপ্টেন্স ওয়ার্কফ্লো এবং পূর্ণাঙ্গ ওয়ারেন্টি সার্ভিস ম্যানেজমেন্ট প্রয়োজন।

- **বেস কোডবেস (Base Codebase):** SalePro POS v5.6.1 (Laravel 10 / PHP 8.2 / MySQL 8)
- **ট্রেসিবিলিটি ব্লুপ্রিন্ট:** `KHAN GADGET POS - Complete Software Overview & Business Logic Document` (11 Pages)
- **বর্তমান সার্ভার ইউআরএল:** `http://127.0.0.1:8000`

---

## ২. মূল প্রযুক্তিগত স্ট্যাক (Technology Stack)
- **ব্যাকএন্ড:** Laravel Framework (PHP 8.2.12)
- **ডাটাবেস:** MySQL 8.0.30 (Laragon Environment, Database: `poskrp`)
- **অথেনটিকেশন ও পারমিশন:** Laravel Auth + Spatie Laravel-Permission
- **ফ্রন্টএন্ড আর্কিটেকচার:** Blade Templates, jQuery, Vanilla JavaScript, DataTables, Bootstrap
- **টুলস ও প্যাকেজ:** Composer 2.9+, Node.js v24+, Artisan CLI

---

## ৩. কোর বিজনেস এনটিটি ও রোল হায়ারার্কি (Core Business Roles & Entities)
```
[Main Company / Super Admin]
       │
       ├── Central Warehouse ──────► [Shipment Cargo / International Purchase]
       │         │
       │         ▼ (Controlled Transfer with Acceptance Workflow)
       │
       ├── Branch 1 (e.g. Dhaka Branch) ──► Branch Manager / Seller / Staff
       └── Branch 2 (e.g. Rajshahi Branch) ──► Branch Manager / Seller / Staff
```

### প্রধান ব্যবহারকারী রোল (User Roles)
1. **Super Admin / Main Company:** সমগ্র সিস্টেমের সেন্ট্রাল এক্সেস, সব ব্রাঞ্চ ও ওয়ারহাউসের সার্বিক রিপোর্ট, পারচেজ প্রাইস ও প্রফিট অ্যানালাইসিস।
2. **Branch Manager:** শুধুমাত্র নিজের নির্দিষ্ট ব্রাঞ্চের অপারেশনাল ডাটা, স্টক, সেলস, ড্যামেজ ও এক্সপেন্স ম্যানেজমেন্ট।
3. **Seller / Sales Staff:** ডেডিকেটেড পিওএস স্ক্রিন, প্রোডাক্ট সার্চ, সিরিয়াল সিলেক্ট করে বিল তৈরি। (গুরুত্বপূর্ণ: Purchase Price লুকানো থাকবে)।
4. **Accountant:** ক্যাশ ট্রানজেকশন, ১০ ধরনের একাউন্টস ট্রান্সফার, ব্যাংক ও রিফান্ড অনুমোদন।
5. **Other Staff:** এসাইন করা দায়িত্ব অনুযায়ী নির্দিষ্ট স্ক্রিন এক্সেস।

---

## ৪. গ্যাজেট স্পেসিফিক প্রোডাক্ট ও সিরিয়াল লজিক (Product & Serial Lifecycle)

### ক. প্রোডাক্ট স্পেসিফিকেশন (Technical Specs)
সাধারণ পণ্যের বাইরে প্রতিটি ডিভাইসের নিচের টেকনিক্যাল ফিল্ডগুলো বাধ্যতামূলক:
- **Processor:** e.g., Core i5 12th Gen, M2 Chip (উইথ লোকাল নরম্যালাইজেশন)
- **RAM:** e.g., 8GB, 16GB, 32GB
- **ROM / Storage:** e.g., 512GB NVMe SSD, 1TB SSD
- **Display:** e.g., 14" OLED, 15.6" FHD IPS
- **Dedicated Graphics:** e.g., RTX 4060 8GB, Iris Xe
- **Adapter & Accessories:** সাথে কি এডাপ্টার বা কেবল আছে তা উল্লেখ করা
- **Product Condition (মাস্টার লেভেল):**
  1. `Used`
  2. `Open Box`
  3. `Brand New (Intact)`
  4. `Box Opened` (Brand New Just Box Open)
- **Pricing Tiers:**
  - `Purchase Price` (Sensitive / Permission Controlled)
  - `Regular Price`
  - `Discount Price`
  - `Last Border Price` (সর্বনিম্ন বিক্রয়যোগ্য ফ্লোর প্রাইস)

### খ. সিরিয়াল/আইএমইআই লাইফসাইকেল (Per-Serial Independent Lifecycle)
একটি প্রোডাক্টের একাধিক ইউনিট থাকলে প্রতিটি ইউনিটের জন্য আলাদা সিরিয়াল রেকর্ড থাকবে:
- **সিরিয়াল স্ট্যাটাস (Status):**
  - `available` (বিক্রয়যোগ্য এভেইলেবল স্টক)
  - `reserved` (প্রি-অর্ডার বা কাস্টমার বুকিং অবস্থায় লকড)
  - `transferring` (ইন-ট্রানজিট ব্রাঞ্চ বা ওয়্যারহাউস ট্রান্সফার)
  - `sold` (বিক্রি সম্পন্ন হয়ে গেছে)
  - `under_service` (ওয়ারেন্টির অধীনে মেরামতে আছে)
  - `damaged` (ডিভাইস ক্ষতিগ্রস্ত/অকেজো; ড্যামেজ মডিউলের মাধ্যমে জবাবদিহিতাসহ স্টক অপসারিত)
  - `cancelled` (অর্ডার/বুকিং বাতিল বা প্রশাসনিক সিদ্ধান্তে ডিকমিশনকৃত সিরিয়াল)
- **Soft Delete (`deleted_at`) বনাম `cancelled` এর পার্থক্য:**
  - `deleted_at` (Soft Delete): অসাবধানতাবশত ভুল এন্ট্রি (Human Data Entry Error) হওয়া সিরিয়াল ডাটাবেজ ব্যাকআপ ও অডিট ঠিক রেখে সাধারণ ভিউ থেকে সম্পূর্ণ লুকানোর জন্য।
  - `cancelled` (Status): ব্যবসায়িক কোনো লেনদেন বাতিল হলে সেই সিরিয়ালটি বাতিল স্ট্যাটাসে থাকবে কিন্তু হিস্ট্রি দৃশ্যমান থাকবে।
  - `damaged` (Status): ফিজিক্যাল হার্ডওয়্যার ড্যামেজের কারণে যখন ডিভাইস আর বিক্রয়যোগ্য থাকে না।
- **Serial-wise Detailed Condition:**
  - একই মডেল ও মাস্টার কন্ডিশন হলেও সিরিয়াল ভেদে অবস্থা ভিন্ন হতে পারে (যেমন: `SN00001: Like New`, `SN00002: Body Minor Scratch`, `SN00003: Body Broken`)।
  - **কঠোর নিয়ম:** এই `Detailed Condition` শুধুমাত্র অভ্যন্তরীণ স্টাফদের জন্য, কাস্টমার ইনভয়েসে প্রিন্ট হবে না।

---

## ৫. মূল বিজনেস ওয়ার্কফ্লো (Core Workflows Summary)

```
1. PURCHASE & SHIPMENT
   Supplier ──► Purchase Entry (with Serials) ──► Cargo/Hand-carry Tracking ──► Warehouse Arrival & Receive

2. WAREHOUSE & BRANCH TRANSFER
   Source Location ──► Transfer Request ──► In-Transit ──► Destination Branch Accepts ──► Stock Moved

3. CASH TRANSFER & REFUND
   Sender Branch ──► Cash Transfer Request ──► Receiver Approves
                                      └── If Rejected ──► Mandatory Reason ──► Refund Workflow to Sender

4. POS & SALES
   Customer ──► Select Product & Serial ──► Discount/Border Price Check ──► Payment/Due ──► Invoice & Warranty Start

5. PRE-ORDER
   Customer Query ──► Local Out of Stock ──► Inter-Branch Stock Lookup ──► Book from Other Branch ──► Order Ready & Delivery

6. WARRANTY CLAIM & SERVICE
   Serial Search ──► Claim Verification ──► Receive ──► Under Service ──► Repair Complete ──► Delivered/Rejected

7. DAMAGE MANAGEMENT
   Select Serial ──► Reason & Responsible Person ──► Approval ──► Deduct from Usable Stock
```

---

## ৬. আর্কিটেকচারাল মূলনীতি (Architectural Principles)
1. **No Manual Stock Screen:** কোনো স্ক্রিনে সরাসরি স্টকের সংখ্যা এডিট করা যাবে না; স্টক শুধুমাত্র পারচেজ রিসিভ, অনুমোদিত ট্রান্সফার, সেলস, রিটার্ন বা ড্যামেজ এপ্রুভালের মাধ্যমে পরিবর্তিত হবে।
2. **Double Confirmation on Transfers:** স্টক বা ক্যাশ কোনোটিই একতরফা ট্রান্সফারে ব্যালেন্স পরিবর্তন হবে না। প্রেরক পাঠাবে, প্রাপক গ্রহণ করার পরেই চূড়ান্ত লেনদেন কার্যকর হবে।
3. **Audit Trail & Immutability:** কোনো ফাইনান্সিয়াল বা স্টক ট্রানজেকশন ডেস্ট্রাক্টিভ ডিলিট করা যাবে না; রিভার্সাল বা কন্ট্রোলড ভয়েড/রিটার্ন মেকানিজম ব্যবহার করতে হবে।
4. **Currency & Localization:** কারেন্সি সিম্বল `৳` এবং ফরম্যাট `৳40,000` (কমা সেপারেটেড)।
5. **PreOrder.status vs ProductSerial.status Enum Separation:** PreOrder.status এবং ProductSerial.status দুটি সম্পূর্ণ ভিন্ন enum space। PreOrder যখন 'ready' (কাস্টমার পিকআপের জন্য শাখায় প্রস্তুত), তখন ফিজিক্যাল ডিভাইসের ক্ষেত্রে ProductSerial.status হবে 'reserved' (যেহেতু এটি ঐ কাস্টমারের জন্য সংরক্ষিত ও এভেইলেবল সেলস স্টক থেকে বাদ)। ভুলেও ProductSerial টেবিলে 'ready' স্ট্যাটাস কোয়েরি বা সেট করা যাবে না (যা ENUM ভায়োলেশন তৈরি করবে)।

---

## ৭. ব্রাঞ্চ ও ওয়্যারহাউস ম্যানেজমেন্ট ডিরেক্টরি (Branch & Warehouse Management)
- **নেভিগেশন মেনু পাথ:** `Sidebar -> Settings (#setting) -> Warehouse (/warehouse)`
- **পারমিশন গার্ড:** `@can('warehouse')` (ডিফল্টভাবে Admin / Role ID 1 ও অনুমোদিত ম্যানেজমেন্ট রোল)।
- **রাউট নেইম ও ইউআরএল:** `route('warehouse.index')` -> `http://127.0.0.1:8000/warehouse`
- **সিস্টেম ওয়াইড অটো-সিঙ্ক:**
  সিস্টেমে কোনো নতুন ব্রাঞ্চ/ওয়্যারহাউস যুক্ত করা হলে (`is_active = true`), এটি কোনো অতিরিক্ত কনফিগারেশন ছাড়াই তাৎক্ষণিকভাবে নিচের প্রতিটি মডিউলের ড্রপডাউনে লাইভ প্রতিফলিত হয়:
  1. **Device Exchange:** `/exchange/create` (Branch / Warehouse selector)
  2. **Inter-Branch Pre-Orders:** `/pre_orders/create` (Source Branch ও Destination Branch)
  3. **Stock Transfers:** `/transfers/create` (From Warehouse ও To Warehouse)
  4. **Damage / Defective Records:** `/damage` (Warehouse Filter ও Modal Selector)
  5. **Supplier RMA:** `/supplier_rma/create` (Warehouse Stock Location)
  6. **Asset Valuation Report:** `/report/warehouse_stock_valuation` (Branch Valuation Breakdown)

