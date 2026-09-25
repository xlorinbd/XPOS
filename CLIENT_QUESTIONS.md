# KHAN GADGET POS — ক্লায়েন্টকে জিজ্ঞাসার প্রশ্নমালা

Requirement PDF ("Client Spech doc from KG") পড়ে যেসব জায়গায় স্পষ্ট উত্তর ছাড়া কাজ করলে ভুল হওয়ার ঝুঁকি আছে, শুধু সেগুলো এখানে রাখা হয়েছে।
**[জরুরি]** চিহ্নিত প্রশ্নের উত্তর ছাড়া সংশ্লিষ্ট মডিউলের কাজ শুরু করা নিরাপদ নয়। বাকিগুলো কাজের সাথে সাথে জানালেও চলবে।

প্রতিটি প্রশ্নের নিচে ক্লায়েন্ট সরাসরি উত্তর লিখতে পারবেন (**উত্তর:** এর পরে)।

---

## ০. যেসব ফাইল/নমুনা পাঠাতে হবে

PDF-এ উল্লেখ আছে কিন্তু সংযুক্ত নেই। এগুলো ছাড়া হুবহু মেলানো সম্ভব না।

1. **[জরুরি] POS Design Reference Screenshot** — PDF-এর ১৫ নম্বর সেকশনে "Reference Screenshot অনুযায়ী" লেখা আছে, কিন্তু ছবিটি PDF-এ নেই।
2. **[জরুরি] এখন যে Daily Account রিপোর্ট/PDF/Excel ব্যবহার করেন তার একটি বাস্তব নমুনা** (কাস্টমারের নাম মুছে দিলে চলবে)।
3. **Quick Paste-এর জন্য Excel/Google Sheet-এর একটি নমুনা** (৫–১০ সারি, যেভাবে ডাটা আপনাদের কাছে থাকে)।
4. **Processor-এর তালিকা** — আপনারা যেসব Processor বিক্রি করেন (Intel Core i3/i5/i7/i9, Ultra, AMD Ryzen, Apple M-series, Celeron, Snapdragon ইত্যাদি) তার একটি নমুনা তালিকা, আর PDF-এর মতো কোনটা কীভাবে লিখতে চান।
5. **Role-wise Permission Table** — কোন Role কোন মডিউলে কী করতে পারবে (নিচে ৩৯ নম্বর প্রশ্নে বিস্তারিত)।
6. Invoice-এর বর্তমান/পছন্দের নমুনা এবং Barcode Label-এর নমুনা।

**উত্তর:**

---

## ১. Product ও Price

**১.** **[জরুরি]** "**Last Border Price**" বলতে ঠিক কী বোঝানো হয়েছে?
- (ক) সর্বশেষ কেনা দাম (Last Bought/Purchase Price)?
- (খ) সর্বনিম্ন বিক্রয়মূল্য, যার নিচে Seller বিক্রি করতে পারবে না (Bottom/Floor Price)?
- (গ) অন্য কিছু?

এটা কি Purchase থেকে স্বয়ংক্রিয়ভাবে আসবে, না হাতে লিখতে হবে? Seller কি এটা দেখতে পারবে? Seller কি এর নিচে দামে বিক্রি করতে পারবে (অনুমতি সহ/ছাড়া)?

**উত্তর:**

**২.** **[জরুরি]** **Purchase Price কে দেখতে পারবে** — PDF-এ দুই জায়গায় দুই রকম লেখা:
- ৯ নম্বর সেকশন: "শুধুমাত্র **Admin**"
- ৩৫ নম্বর সেকশন: "শুধুমাত্র **Admin এবং Manager**"

কোনটা ঠিক? Branch Manager ও Accountant কি দেখতে পারবে? (রিপোর্টে Profit/Cost দেখানোর ক্ষেত্রেও একই নিয়ম হবে কি?)

**উত্তর:**

**৩.** **[জরুরি]** Condition (Used / Open Box / Brand New / Box Opend) **Product Level-এ** থাকবে বলা হয়েছে। এর মানে কি একই মডেলের Used ও Brand New আলাদা আলাদা Product হবে (আলাদা নাম, আলাদা Price)?
আবার Purchase Price/Cost কি প্রতি Serial-এ আলাদা হতে পারবে? (একই মডেল ভিন্ন ভিন্ন দামে কিনলে Profit হিসাব কোন দাম ধরে হবে — Product-এর একটি দাম, না প্রতিটি Serial-এর প্রকৃত কেনা দাম?)

**উত্তর:**

**৪.** "**Box Opend**" বানানটি PDF-এ ইচ্ছাকৃতভাবে এভাবে (Opend, "Opened" নয়) লেখা হয়েছে বলে ধরে নিচ্ছি এবং হুবহু এভাবেই রাখব। নিশ্চিত করুন।

**উত্তর:**

**৫.** Product Details-এর "**Adapter**" ফিল্ডে কী কী অপশন থাকবে? (যেমন: Original / Duplicate / Not Included / Included)। এটা Product Level নাকি Serial Level?

**উত্তর:**

**৬.** **[জরুরি]** সব Product কি Serial-based, নাকি কিছু Product (Charger, Bag, Mouse, Accessories) শুধু Quantity দিয়ে চলবে? সেগুলোর জন্য Serial লাগবে কি?

**উত্তর:**

**৭.** **Detailed Condition** (যেমন "Top Part Color", "Body Broken", "Like New"):
- এটা কি মুক্ত লেখা (Free Text), নাকি নির্দিষ্ট তালিকা থেকে বাছাই?
- Purchase-এ Serial ঢোকানোর সময়ই কি এটা লেখা হবে? Edit করার অনুমতি কার থাকবে? Edit-এর History (কে কবে বদলাল) রাখতে হবে কি?

**উত্তর:**

**৮.** টাকার ফরম্যাট `৳40,000` — এখানে:
- দশমিক (পয়সা) থাকবে কি? (যেমন ৳40,000.50 হলে কী দেখাবে?)
- সংখ্যা ইংরেজি (40,000) নাকি বাংলা (৪০,০০০)?
- কমা বসবে পশ্চিমা নিয়মে (4,00,000 নাকি 400,000)? বাংলাদেশে লাখ-কোটি নিয়ম (4,00,000) সাধারণ — কোনটা চান?

**উত্তর:**

**৯.** **Processor Normalization** — PDF-এর উদাহরণ অনুযায়ী `AMD Ryzen™ 5 7535HS` ও `Intel® Core™ i7-1165G7` (® ও ™ চিহ্নসহ)। এই চিহ্নগুলো Product Name/Card/Invoice সবখানে দেখাবে? আর কোনো Processor চিনতে না পারলে (Unrecognized) যেমন লেখা হয়েছে তেমনই থাকবে কি?

**উত্তর:**

---

## ২. Quick Product Paste

**১০.** **[জরুরি]** একবারে কি **একটি সারি** Paste করে Form Auto-fill হবে, নাকি **একাধিক সারি** Paste করে একসাথে অনেক Product তৈরি হবে?
Paste করা Column-এর ক্রম কি ঠিক এটাই থাকবে: `Brand | Model | Processor | RAM | ROM | Display | Dedicated Graphics | Remarks`? Product Name, Condition, Adapter, Price-ও কি Paste-এর অংশ হবে, নাকি সেগুলো হাতে দেবেন? Product Name কি Brand + Model ইত্যাদি থেকে স্বয়ংক্রিয়ভাবে তৈরি হবে?

**উত্তর:**

---

## ৩. Purchase ও Shipment (বিদেশ থেকে আনা)

**১১.** **[জরুরি]** **Stock কখন Sellable হবে?** PDF-এর ১০ নম্বরে বলা আছে "Purchase করার পর Product Stock-এ যুক্ত হবে", আবার ১১ নম্বরে Product বাংলাদেশের Warehouse-এ পৌঁছানো পর্যন্ত Shipment Track করতে হবে। তাহলে:
- Purchase Entry দিলেই কি Stock দেখাবে, নাকি বাংলাদেশে পৌঁছে Receive করার পর?
- মাঝের সময়টায় (In-transit) কি Product "আসছে" হিসেবে আলাদা দেখানো হবে এবং বিক্রি/Pre-Order নেওয়া যাবে?

**উত্তর:**

**১২.** **[জরুরি]** একটি Purchase কি একাধিক Shipment-এ ভাগ হতে পারে (আংশিক চালান)? আবার একটি Shipment-এ কি একাধিক Purchase/Supplier-এর মাল একসাথে আসতে পারে?

**উত্তর:**

**১৩.** **[জরুরি]** **Landed Cost** — Shipping Cost, Customs/Cargo Cost, Additional Cost কি Product-এর প্রকৃত Cost-এ যোগ হবে (যাতে Profit সঠিক হয়)? যোগ হলে কীভাবে ভাগ হবে: প্রতি পিস সমান, নাকি দামের অনুপাতে?

**উত্তর:**

**১৪.** **[জরুরি]** বিদেশি Supplier-কে কি অন্য মুদ্রায় (USD, CNY, AED ইত্যাদি) টাকা দেওয়া হয়? হলে Purchase ও Supplier Payment-এ Exchange Rate রাখতে হবে কি, নাকি সবকিছু সরাসরি ৳ (BDT)-তে লিখবেন?

**উত্তর:**

**১৫.** Shipment-এর তথ্য:
- Cargo কোম্পানি/Hand Carry ব্যক্তির তালিকা কি আগে থেকে সেট করা থাকবে?
- Hand Carry-র "Responsible Person" কি Staff, নাকি বাইরের ব্যক্তিও হতে পারে?
- Shipment Cost কোন Account থেকে দেওয়া হবে — সেটা কি স্বয়ংক্রিয়ভাবে Expense/Payment হিসেবে হিসাবে যাবে?
- Serial Number কি Purchase Entry-র সময় ঢোকাবেন, নাকি বাংলাদেশে Warehouse-এ পৌঁছে Receive করার সময়?

**উত্তর:**

**১৬.** Purchase Status ও Shipment Status-এর সম্পূর্ণ তালিকা কী কী হবে? (যেমন: Ordered → Shipped → In Transit → Arrived → Received/Partial → Cancelled)। আপনাদের নিজেদের ব্যবহৃত শব্দগুলো দিলে ভালো।

**উত্তর:**

---

## ৪. Branch, Warehouse ও Accounts

**১৭.** **[জরুরি]** বর্তমানে কয়টি Branch ও কয়টি Warehouse আছে, ভবিষ্যতে কতটি হতে পারে? Warehouse কি Branch থেকে আলাদা একটি স্থান (যেখানে কেবল মজুত ও Cash জমা হয়, বিক্রি হয় না), নাকি Warehouse-ও কখনো বিক্রি করতে পারে?

**উত্তর:**

**১৮.** **[জরুরি]** **Account কাঠামো** — "সব Account Main Account-এর অধীনে"। ঠিক কেমন?
- Main Account → Branch Account (প্রতি Branch-এর Cash Register + Bank + Mobile Wallet) → …?
- Warehouse-এর নিজস্ব Account আছে (Cash জমা থাকে)।
- **Staff Wallet** কী? এটা কি কোম্পানির টাকা যা Staff-এর কাছে আছে (Float), নাকি Staff-এর নিজস্ব বেতন-জমা? Staff-এর ব্যক্তিগত Bank/bKash থেকে কোম্পানির খরচ করে পরে ফেরত নেওয়ার ব্যবস্থা কি আছে (Reimbursement)?
- একজন Staff-এর একাধিক Bank/Mobile Wallet থাকতে পারে?

**উত্তর:**

**১৯.** **[জরুরি]** **Transfer-এর ১০ ধরন (সেকশন ২৪)** নিয়ে প্রশ্ন:
- (ক) Accept/Reject/Refund Workflow (সেকশন ২৫–২৮) শুধু "Branch থেকে Warehouse" ও "Warehouse থেকে Branch"-এর জন্য বর্ণনা করা হয়েছে। বাকি ৮টির (যেমন Branch→Branch Cash, Branch Cash→Main Account, Staff→KG Bank) জন্যও কি একই Accept-Reject লাগবে, নাকি সেগুলো সঙ্গে সঙ্গে সম্পন্ন হবে?
- (খ) "**KG Bank**" ও "**KG Refer Bank**" আলাদা Account? "Refer" কী অর্থে?
- (গ) "**Third Party Transfer**" কী — কার কাছে/কোথা থেকে, তৃতীয় পক্ষের নাম-তথ্য রেকর্ড রাখতে হবে?
- (ঘ) "**ATM Deposit**" কোন Bank Account-এ জমা হবে?
- (ঙ) "Staff Bank/Wallet থেকে **Expense**" হলে কি স্বয়ংক্রিয়ভাবে Expense রেকর্ড তৈরি হবে? কোন Category-তে?
- (চ) Dashboard-এর "Pending Transfer / Pending Incoming Transfer" এবং "Add New → Transfer" বলতে **Stock (পণ্য) Transfer**, **Cash Transfer**, নাকি দুটোই বোঝানো হয়েছে?

**উত্তর:**

**২০.** **[জরুরি]** **Cash Transfer-এর টাকা কখন কাটা যাবে?** PDF-এ অসঙ্গতি আছে:
- ২৫ নম্বর: Warehouse "Accept" করার পর Originating Branch থেকে Cash Deduct হবে।
- ২৬ নম্বর: Reject করলে "Refund Process শুরু হবে... Cash Originating Branch-এ ফেরত যাবে" — অর্থাৎ টাকা আগেই কাটা হয়েছিল ধরে নেওয়া হয়েছে।

সঠিক নিয়ম কোনটা?
- (ক) পাঠানোর সময়ই Branch থেকে কেটে "In-Transit/Pending" অবস্থায় থাকবে, Accept হলে Warehouse-এ যোগ হবে, Reject হলে Refund হয়ে ফিরবে।
- (খ) পাঠানোর সময় কিছুই কাটবে না, Accept হলে একসাথে কাটবে ও যোগ হবে (তাহলে Refund দরকার হয় না)।

এছাড়া: Pending অবস্থায় Sender কি Transfer বাতিল (Cancel) করতে পারবে? Reject হলে Refund কি স্বয়ংক্রিয় হবে (শুধু Branch-এর Accept বাকি), নাকি Warehouse-কে আলাদা করে টাকা "ফেরত পাঠাতে" হবে? আংশিক Refund হতে পারবে?

**উত্তর:**

**২১.** **"Received By Name"** (Mandatory) — এটা কি Login করা ব্যবহারকারীর নাম স্বয়ংক্রিয়ভাবে, নাকি টাকা যিনি হাতে পেয়েছেন তার নাম হাতে লিখতে হবে (Free Text) / Staff তালিকা থেকে বাছাই?
"Warehouse Manager / Authorized Person" কীভাবে ঠিক হবে — Role দিয়ে, নাকি নির্দিষ্ট ব্যক্তিকে Assign করে?

**উত্তর:**

**২২.** Branch থেকে Branch-এ **পণ্য (Stock/Serial) Transfer**-ও কি একই নিয়মে চলবে (পাঠানো → Receiver Accept/Reject → Reject হলে ফেরত)? Reject হলে কারণ (Reason) কি এখানেও Mandatory?

**উত্তর:**

---

## ৫. Pre-Order

**২৩.** **[জরুরি]** Pre-Order-এ **Advance টাকা**:
- Advance কি বাধ্যতামূলক, নাকি ঐচ্ছিক? Advance কোন Account-এ জমা হবে (যে Branch-এ অর্ডার নেওয়া হলো সেই Branch-এর Cash)?
- Pre-Order Cancel হলে Advance কি ফেরত যাবে? সম্পূর্ণ নাকি কিছু কেটে রেখে?

**উত্তর:**

**২৪.** **[জরুরি]** **Stock Reservation:** Pre-Order নেওয়ার সাথে সাথে কি ঐ নির্দিষ্ট Serial/Unit অন্য কারো জন্য আটকে (Reserve) যাবে? নাকি Order "Confirmed" হলে আটকাবে? একই Unit দুইজন কাস্টমার Pre-Order দিতে পারবে?

**উত্তর:**

**২৫.** Pre-Order Confirm হলে **পণ্য পাঠানোর জন্য Stock Transfer কি স্বয়ংক্রিয়ভাবে তৈরি হবে** (Source Branch/Warehouse → Delivery Branch), নাকি আলাদা করে Transfer করতে হবে? Source Branch কে "Confirm" করবে?

**উত্তর:**

**২৬.** এখনো বিদেশ থেকে আসছে (In-Transit Shipment) এমন পণ্যের উপরও কি Pre-Order নেওয়া যাবে?

**উত্তর:**

**২৭.** POS-এ **সব Branch-এর Stock** দেখানো হলে, একজন Seller অন্য Branch-এর কোন কোন তথ্য দেখতে পারবে (Stock সংখ্যা, Serial, Detailed Condition, দাম)? অন্য Branch-এর Serial-এর Detailed Condition দেখতে পারবে কি?
Pre-Order থেকে বিক্রি হলে **Sale-এর ক্রেডিট/কমিশন/রিপোর্ট** কোন Branch ও কোন Seller-এর নামে যাবে (যে অর্ডার নিল, নাকি যে ডেলিভারি দিল)?

**উত্তর:**

---

## ৬. POS ও Sale

**২৮.** **[জরুরি]** POS-এর Layout বর্ণনায় "Left Side Product/Category", "Middle Product List", "Right Cart/Payment" লেখা আছে। Left-এ কী থাকবে (শুধু Category ফিল্টার?), Middle-এ কী (Product Card)? **Reference Screenshot ছাড়া নিশ্চিত হওয়া যাচ্ছে না** (০ নম্বর দেখুন)।

**উত্তর:**

**২৯.** **Payment Method:** কী কী পদ্ধতি লাগবে (Cash, bKash, Nagad, Rocket, Bank Transfer, Card, POS Machine, Due)? প্রতিটি কোন Account-এ জমা হবে (যেমন bKash → Branch-এর Mobile Wallet)? এক Sale-এ একাধিক Payment (Split) লাগবে?

**উত্তর:**

**৩০.** POS-এ Seller কি Product-এর **দাম নিজে বদলাতে** পারবে (দরদাম)? Discount-এর সীমা আছে কি (যেমন সর্বোচ্চ ৫%, Manager-এর অনুমতি ছাড়া নয়)? "Last Border Price"-এর নিচে বিক্রি নিষিদ্ধ কি?

**উত্তর:**

**৩১.** **Invoice:** কাগজের ধরন (A4 / Thermal ৫৮mm / ৮০mm)? Invoice-এ Product Condition (Used/Brand New) ও Warranty-র শর্ত/তারিখ দেখাবে কি? Detailed Condition দেখানো যাবে না — এটা বুঝেছি। Invoice-এ ভাষা বাংলা না ইংরেজি? Branch অনুযায়ী আলাদা Header (ঠিকানা/ফোন) থাকবে?

**উত্তর:**

**৩২.** **Sale Return / Exchange / Purchase Return:** Return হলে টাকা কোন Account থেকে ফেরত যাবে? Return করা Serial কি সরাসরি Stock-এ ফিরবে, নাকি প্রথমে Inspection/Damage হিসেবে যাবে? Return-এর সময়সীমা ও শর্ত আছে কি?

**উত্তর:**

---

## ৭. Warranty ও Damage

**৩৩.** **Warranty:** মেয়াদ কীভাবে ঠিক হবে — Product-ভিত্তিক, বিক্রির সময় Seller বাছাই, নাকি Condition-ভিত্তিক (Used-এ একরকম, Brand New-তে অন্যরকম)? Warranty-র সার্ভিস কি আপনারা নিজেরা দেন, নাকি Supplier/Service Center-এ পাঠান? (Supplier-এর কাছে পাঠালে সেই RMA-ও কি ট্র্যাক করবেন?)

**উত্তর:**

**৩৪.** **Damage:** Damage Type-এর তালিকা কী? Damage হলে ঐ পণ্যের Cost কি **ক্ষতি (Loss/Expense)** হিসেবে Profit-এ ধরা হবে? Damage Entry-তে কারও অনুমোদন (Approval) লাগবে? Supplier-কে দোষ দিয়ে ফেরত (Claim) পাঠানো যাবে?

**উত্তর:**

---

## ৮. Notification, Daily PDF, Dashboard

**৩৫.** **Notification:** শুধু সফটওয়্যারের ভেতরে (Bell/Badge/Indicator), নাকি SMS/WhatsApp/Email-ও যাবে? কোন Event-এ কাকে (Role/Branch/নির্দিষ্ট ব্যক্তি) যাবে — একটি ছোট তালিকা দিলে ভালো।

**উত্তর:**

**৩৬.** **[জরুরি]** **Daily Account PDF:**
- এটা কি প্রতি **Branch-এর আলাদা**, নাকি সব Branch একত্রে (Consolidated)? কে ও কখন বের করবে — হাতে বাটন চেপে, নাকি দিনের শেষে Day Close করলে?
- **Opening Balance** কি আগের দিনের Closing থেকে স্বয়ংক্রিয়ভাবে আসবে?
- ৯টি অংশে "Today Sale" (২), "Today's Sales Summary" (৭), "Today's Sales Details" (৮) ও "Product Full Details" (৫) ওভারল্যাপ করছে, আবার "একই তথ্য বারবার নয়" বলা হয়েছে। **Product Full Details** কি আলাদা অংশ, নাকি Sales Details-এর প্রতি সারির ভেতরেই থাকবে? "Product Full Details" বলতে কোন কোন ফিল্ড (Name, Processor, RAM, Storage, Display, Graphics, Condition, Serial)?
- "**Total Transferred Amount**" এ কোন Transfer ধরা হবে — শুধু সম্পন্ন (Completed)? Pending/Rejected বাদ? বহির্গামী (Outgoing) না উভয় দিক?
- দিনে ১০০+ Sale হলে ২–৩ পেজের সীমা কীভাবে রাখব (ছোট ফন্টে সব সারি, নাকি সীমার বেশি হলে আলাদা পেজ)?
- PDF ইংরেজিতে, নাকি বাংলা লেখাও থাকবে?

**উত্তর:**

**৩৭.** **Dashboard-এর সংজ্ঞা:**
- "**Today's Profit**" = বিক্রয় − পণ্যের প্রকৃত Cost (Landed Cost সহ)? নাকি খরচও (Expense) বাদ দিয়ে Net Profit?
- "**Total Stock**" সংখ্যা (Quantity), না মূল্য (Value)? মূল্য দেখালে কে দেখতে পারবে?
- "**Cash Summary**"-এ কী কী থাকবে (Cash, Bank, Wallet আলাদা)?
- "**Pending Warranty / Pending Purchase**" বলতে কোন Status গুলো বোঝাচ্ছেন?
- Dashboard কি Login করা ব্যবহারকারীর Branch অনুযায়ী দেখাবে, নাকি Admin সব Branch একসাথে?

**উত্তর:**

---

## ৯. HR, Role ও অন্যান্য

**৩৮.** **HR:** বেতন কাঠামো কী — নির্ধারিত মাসিক বেতন, নাকি বিক্রয়-ভিত্তিক Commission/Incentive-ও আছে? Attendance কীভাবে নেওয়া হবে (হাতে, ফিঙ্গারপ্রিন্ট মেশিন থেকে Import, বা অন্য কিছু)? Loan/Advance কি মাসিক বেতন থেকে কিস্তিতে স্বয়ংক্রিয় কাটা যাবে?

**উত্তর:**

**৩৯.** **[জরুরি]** **Role:** Admin, Manager, Branch Manager, Seller, Accountant, Staff — এদের মধ্যে Manager ও Branch Manager-এর পার্থক্য কী? Branch Manager/Seller কি শুধু নিজের Branch-এর তথ্য দেখতে পাবে? একজন ব্যবহারকারী কি একাধিক Branch-এ কাজ করতে পারে? প্রতিটি Role-এর জন্য মডিউলভিত্তিক অনুমতির ছক (View/Add/Edit/Delete) পাঠান।

**উত্তর:**

**৪০.** **Barcode:** Barcode-এ কী Encode হবে — Product Code, নাকি Serial Number (Serial Barcode)? কোন কোন Label Size লাগবে (মাপ mm-এ)? Label-এ কী কী ছাপা হবে (নাম, Spec, দাম, Serial)? Barcode Scanner ও Label Printer-এর মডেল কী?

**উত্তর:**

**৪১.** **[জরুরি]** **বিদ্যমান ডাটা:** এখন কি Excel/অন্য সফটওয়্যারে Product, Serial, Customer, Supplier, বকেয়া (Due) ও Account-এর Opening Balance আছে যা নতুন সিস্টেমে আনতে হবে? কোন ফরম্যাটে?

**উত্তর:**

**৪২.** **অগ্রাধিকার ও পরিবেশ:**
- কোন মডিউল আগে চালু (Go-Live) করা জরুরি? (যেমন POS + Sale + Stock আগে, Accounts/Transfer পরে?)
- দোকানে ইন্টারনেট চলে গেলে কি বিক্রি চালিয়ে যেতে হবে (Offline POS)?
- সিস্টেমটি কি শুধু Khan Gadget-এর নিজস্ব, নাকি ভবিষ্যতে অন্য কোম্পানিও ব্যবহার করবে?
- একসাথে কতজন ব্যবহারকারী চালাবেন (প্রায়)? সফটওয়্যারের ভাষা বাংলা লাগবে কি?

**উত্তর:**
