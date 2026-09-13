<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ব্যবহারকারী নির্দেশিকা | খান গ্যাজেট পিওএস</title>
    <meta name="description" content="খান গ্যাজেট পিওএস সিস্টেম ব্যবহারকারী নির্দেশিকা ও ম্যানুয়াল">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <!-- Google Fonts: Noto Serif Bengali -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/dripicons/webfont.css') }}">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --secondary: #475569;
            --dark: #0f172a;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Noto Serif Bengali', serif;
            background-color: var(--light-bg);
            color: #1e293b;
            line-height: 1.85;
            font-size: 16px;
            overflow-x: hidden;
        }

        /* Documentation Top Navbar */
        .doc-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .doc-brand {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .doc-brand:hover {
            color: var(--primary);
            text-decoration: none;
        }

        .doc-badge {
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            margin-left: 0.75rem;
            font-family: 'Inter', sans-serif;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        /* Documentation Layout */
        .doc-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Documentation Sticky Sidebar */
        .doc-sidebar {
            position: sticky;
            top: 5rem;
            height: calc(100vh - 6rem);
            overflow-y: auto;
            padding-right: 1.5rem;
            border-right: 1px solid var(--border-color);
        }

        .doc-sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .doc-sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .doc-nav-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.75rem;
            font-family: 'Inter', sans-serif;
        }

        .doc-nav-link {
            display: block;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.2rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .doc-nav-link:hover {
            color: var(--primary);
            background: var(--primary-light);
            text-decoration: none;
            padding-left: 1rem;
        }

        .doc-nav-link.active {
            color: var(--primary);
            background: var(--primary-light);
            font-weight: 700;
            border-left: 3px solid var(--primary);
        }

        /* Search Bar */
        .doc-search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .doc-search-box input {
            font-family: 'Noto Serif Bengali', serif;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            font-size: 1rem;
            width: 100%;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .doc-search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .doc-search-box .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        /* Content Area & Cards */
        .doc-main-content {
            padding-left: 1.5rem;
        }

        .doc-hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 12px;
            padding: 2.5rem;
            color: #ffffff;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .doc-hero h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .doc-hero p {
            font-size: 1.15rem;
            color: #cbd5e1;
            margin-bottom: 0;
            max-width: 850px;
        }

        .doc-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .doc-card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .doc-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 1rem;
        }

        .doc-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }

        .doc-card-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 700;
            margin-right: 0.75rem;
            font-family: 'Inter', sans-serif;
            flex-shrink: 0;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            font-size: 0.95rem;
            margin-right: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .meta-pill strong {
            color: #475569;
            margin-right: 0.4rem;
        }

        .meta-pill .val {
            color: var(--dark);
            font-weight: 600;
        }

        .step-list {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .step-list li {
            margin-bottom: 0.75rem;
            color: #334155;
        }

        .step-list li strong {
            color: var(--dark);
        }

        .example-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin-top: 1.25rem;
        }

        .example-box strong {
            color: #15803d;
            font-weight: 700;
            display: block;
            margin-bottom: 0.35rem;
        }

        .example-box p {
            color: #166534;
            margin-bottom: 0;
        }

        .btn-direct-link {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        /* Quick Reference Table */
        .quick-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .quick-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 1rem;
            border-bottom: 2px solid var(--border-color);
            font-size: 0.95rem;
        }

        .quick-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .quick-table tr:hover td {
            background: #f8fafc;
        }

        .code-path {
            background: #f1f5f9;
            color: #0f172a;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            border: 1px solid #e2e8f0;
            font-family: 'Inter', monospace;
            font-weight: 600;
        }

        @media (max-width: 991px) {
            .doc-sidebar {
                display: none;
            }
            .doc-main-content {
                padding-left: 0;
            }
            .doc-hero {
                padding: 1.75rem;
            }
            .doc-hero h1 {
                font-size: 1.75rem;
            }
        }

        @media print {
            .doc-navbar, .btn-direct-link, .doc-search-box, .doc-sidebar {
                display: none !important;
            }
            .doc-main-content {
                padding: 0 !important;
            }
            .doc-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Standalone Documentation Navbar -->
    <nav class="doc-navbar">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center">
                <a href="{{ url('doc') }}" class="doc-brand">
                    <i class="fa fa-book text-primary mr-2"></i>
                    <span>খান গ্যাজেট পিওএস</span>
                </a>
                <span class="doc-badge">User Manual v2.0</span>
            </div>

            <div class="d-flex align-items-center">
                <a href="{{ route('sale.pos') }}" target="_blank" class="btn btn-success btn-sm font-weight-bold mr-2 px-3 py-2 shadow-sm">
                    <i class="dripicons-shopping-bag mr-1"></i> পিওএস কাউন্টার
                </a>
                <a href="{{ url('/dashboard') }}" target="_blank" class="btn btn-outline-secondary btn-sm font-weight-bold mr-2 px-3 py-2">
                    <i class="fa fa-dashboard mr-1"></i> ড্যাশবোর্ড
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm px-3 py-2">
                    <i class="fa fa-print mr-1"></i> প্রিন্ট ম্যানুয়াল
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="doc-container">
        <div class="row">

            <!-- Sticky Left Sidebar (TOC) -->
            <div class="col-lg-3 d-none d-lg-block">
                <aside class="doc-sidebar">
                    <div class="doc-nav-title">বিষয়বস্তু ও সূচিপত্র</div>
                    <nav id="docSideNav">
                        <a href="#sec-quick-paste" class="doc-nav-link">১. দ্রুত সিরিয়াল যোগ (Quick Paste)</a>
                        <a href="#sec-barcode" class="doc-nav-link">২. বারকোড ও থার্মাল লেবেল</a>
                        <a href="#sec-pos-sale" class="doc-nav-link">৩. পিওএস কাউন্টারে বিক্রি</a>
                        <a href="#sec-warranty" class="doc-nav-link">৪. ওয়ারেন্টি ও গ্যারান্টি চেক</a>
                        <a href="#sec-return" class="doc-nav-link">৫. পণ্য রিটার্ন (ভালো বনাম নষ্ট)</a>
                        <a href="#sec-exchange" class="doc-nav-link">৬. ডিভাইস এক্সচেঞ্জ বা সোয়াপ</a>
                        <a href="#sec-pre-order" class="doc-nav-link">৭. শাখা থেকে প্রি-অর্ডার বুকিং</a>
                        <a href="#sec-service" class="doc-nav-link">৮. সার্ভিসিং টিকিট ও মেরামত</a>
                        <a href="#sec-rma" class="doc-nav-link">৯. নষ্ট ডিভাইস লগ ও ভেন্ডর আরএমএ</a>
                        <a href="#sec-transfer" class="doc-nav-link">১০. স্টক ট্রান্সফার ও রিসিভ</a>
                        <a href="#sec-valuation" class="doc-nav-link">১১. স্টক ভ্যালুয়েশন রিপোর্ট</a>
                        <a href="#sec-warehouse" class="doc-nav-link">১২. নতুন শাখা পরিচালনা</a>
                        <a href="#sec-quick-table" class="doc-nav-link font-weight-bold text-primary">১৩. কুইক-রেফারেন্স টেবিল</a>
                    </nav>
                </aside>
            </div>

            <!-- Documentation Content Area -->
            <div class="col-lg-9 col-md-12 doc-main-content">

                <!-- Hero Section -->
                <div class="doc-hero">
                    <h1>খান গ্যাজেট পিওএস — ব্যবহারকারী নির্দেশিকা</h1>
                    <p>
                        দোকানের বিক্রয়কর্মী, ক্যাশিয়ার এবং শাখা ম্যানেজারদের প্রতিদিনের কাজের সম্পূর্ণ সহায়িকা। কোনো জটিল প্রযুক্তিগত পরিভাষা ছাড়া সাধারণ ভাষায় কোন কাজ কীভাবে করতে হবে তা নিচে দেওয়া হলো।
                    </p>
                </div>

                <!-- Interactive Search Bar -->
                <div class="doc-search-box">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" id="docSearch" placeholder="কী করতে চান লিখুন... (যেমন: এক্সচেঞ্জ, রিটার্ন, প্রি-অর্ডার, ওয়ারেন্টি, সিরিয়াল স্ক্যান, ড্যামেজ)">
                </div>

                <!-- 1. Quick Paste -->
                <article class="doc-card doc-topic" id="sec-quick-paste">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">১</span>
                            <span>নতুন প্রোডাক্ট ও সিরিয়াল নম্বর দ্রুত স্টকে যোগ করা</span>
                        </div>
                        <a href="{{ route('products.quickPaste') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-bolt"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Product <i class="fa fa-angle-right"></i> Quick Paste</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">ইনভেন্টরি স্টাফ, ক্যাশিয়ার, ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li><strong>Select Warehouse</strong> ড্রপডাউন থেকে তোমার দোকানের শাখা নির্বাচন করো।</li>
                        <li>প্রোডাক্ট ড্রপডাউন থেকে যে মডেলের পণ্যটি এসেছে সেটি সিলেক্ট করো (যেমন: Lenovo ThinkPad T480s)।</li>
                        <li><strong>Serial Numbers</strong> বক্সে বারকোড স্ক্যানার দিয়ে পরপর প্রতিটি ডিভাইসের সিরিয়াল নম্বর স্ক্যান করো অথবা টাইপ করো (প্রতি লাইনে একটি করে সিরিয়াল)।</li>
                        <li>প্রতিটি ডিভাইসের কন্ডিশন সিলেক্ট করো (যেমন: Brand New, Super Fresh, Good ইত্যাদি)।</li>
                        <li>নিচে <strong>Submit / Process Serials</strong> বাটনে ক্লিক করো। সিস্টেম স্বয়ংক্রিয়ভাবে স্টক আপডেট করে দেবে।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>দোকানে একসাথে ৫টি MacBook Air M1 এসেছে। প্রতিটি আলাদাভাবে এন্ট্রি করতে অনেক সময় লাগবে। তাই Quick Paste পেজে গিয়ে ৫টি সিরিয়াল পরপর স্ক্যান করে সাবমিট দিলেই মাত্র ১০ সেকেন্ডে ৫টি ল্যাপটপ শাখায় স্টকে যুক্ত হয়ে যাবে।</p>
                    </div>
                </article>

                <!-- 2. Barcode Label -->
                <article class="doc-card doc-topic" id="sec-barcode">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">২</span>
                            <span>বারকোড ও থার্মাল স্টিকার লেবেল প্রিন্ট করা</span>
                        </div>
                        <a href="{{ route('product.printBarcode') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-barcode"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Product <i class="fa fa-angle-right"></i> Print Barcode</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">সব বিক্রয়কর্মী, ক্যাশিয়ার, ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li><strong>Select Warehouse</strong> থেকে শাখা নির্বাচন করো।</li>
                        <li>সার্চ বক্সে প্রডাক্টের নাম বা কোড লিখে পণ্যটি সিলেক্ট করো।</li>
                        <li>ডান পাশে <strong>Select Serials</strong> বাটনে ক্লিক করে যে ডিভাইসগুলোর লেবেল প্রিন্ট করতে চাও তাদের সিরিয়ালে টিক চিহ্ন দাও। টিক দেওয়ার সাথে সাথে কোয়ান্টিটি স্বয়ংক্রিয়ভাবে লক হয়ে যাবে।</li>
                        <li>নিচে পেপার সাইজ বেছে নাও (যেমন: ৩x২ ইঞ্চি থার্মাল সাইজ)।</li>
                        <li>স্টিকারে কোন কোন তথ্য (Product Name, Price, Specs, Serial Barcode) দেখতে চাও টিক দিয়ে রাখো।</li>
                        <li><strong>Generate Barcode</strong> বাটনে চাপ দাও এবং প্রিন্টারে কমান্ড দাও।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>স্টকে নতুন আসা HP EliteBook-এ লেবেল লাগাতে হবে যেন কাস্টমার প্রসেসর, র‍্যাম এবং ডিসপ্লে সাইজ স্টিকার দেখেই বুঝতে পারে। এই পেজ থেকে নির্দিষ্ট সিরিয়াল সিলেক্ট করে প্রিন্ট দিলে প্রতিটি ল্যাপটপের জন্য আলাদা ইউনিক বারকোড স্টিকার বের হয়ে আসবে।</p>
                    </div>
                </article>

                <!-- 3. POS Sale -->
                <article class="doc-card doc-topic" id="sec-pos-sale">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৩</span>
                            <span>পিওএস কাউন্টারে কাস্টমারের কাছে পণ্য বিক্রি করা</span>
                        </div>
                        <a href="{{ route('sale.pos') }}" target="_blank" class="btn btn-success btn-direct-link">
                            <i class="dripicons-shopping-bag"></i> পিওএস স্ক্রিন ওপেন করুন
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Sale <i class="fa fa-angle-right"></i> POS (অথবা টপবার বাটন)</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">কাউন্টার সেলস স্টাফ, ক্যাশিয়ার, ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>কাস্টমারের নাম বা ফোন নম্বর সিলেক্ট/টাইপ করো (নতুন কাস্টমার হলে প্লাস আইকন দিয়ে দ্রুত নাম ও মোবাইল নম্বর যোগ করো)।</li>
                        <li>প্রোডাক্ট সার্চ বারে বারকোড স্ক্যানার দিয়ে ডিভাইসের সিরিয়াল স্ক্যান করো অথবা পণ্যের নাম লেখো।</li>
                        <li>সিরিয়ালযুক্ত প্রডাক্ট টেবিলে যোগ হওয়ার পর ড্রপডাউন থেকে ঠিক যে ফিজিক্যাল ইউনিটটি কাস্টমারকে দিচ্ছো তার সিরিয়াল নম্বর বেছে নাও।</li>
                        <li>ডিসকাউন্ট দিতে চাইলে নির্ধারিত নিয়ম অনুযায়ী বসাও। যদি নির্ধারিত ফ্লোর প্রাইসের নিচে ছাড় দেওয়ার চেষ্টা করা হয়, তবে সিস্টেম ম্যানেজার ওটিপি/অনুমোদন চাইবে।</li>
                        <li>নিচে সবুজ রঙের <strong>Cash</strong> অথবা <strong>Payment</strong> বাটনে চাপ দাও।</li>
                        <li>পেমেন্ট মেথড (ক্যাশ, কার্ড, অথবা মোবাইল ব্যাংকিং) সিলেক্ট করে <strong>Finalize Sale</strong> বাটনে চাপ দাও। রিসিট প্রিন্ট বের হবে।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>একজন ক্রেতা Dell Latitude 7490 কেনার সিদ্ধান্ত নিলেন। ক্যাশিয়ার পিওএস স্ক্রিনে প্রডাক্টটি এনে কাস্টমারের হাতে দেওয়া ল্যাপটপের পেছনের সিরিয়াল SN-DELL-8823 সিলেক্ট করলেন। এরপর পেমেন্ট নিয়ে বিল সম্পন্ন করলেন। এর ফলে ওই সুনির্দিষ্ট সিরিয়ালটি বিক্রি হয়ে গেল এবং স্বয়ংক্রিয়ভাবে তার ওয়ারেন্টি পিরিয়ড শুরু হয়ে গেল।</p>
                    </div>
                </article>

                <!-- 4. Warranty Lookup -->
                <article class="doc-card doc-topic" id="sec-warranty">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৪</span>
                            <span>কাস্টমারের ডিভাইসের ওয়ারেন্টি ও গ্যারান্টি চেক করা</span>
                        </div>
                        <a href="{{ route('warranty.lookup') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-shield"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Service & Warranty <i class="fa fa-angle-right"></i> Warranty Lookup</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">ফ্রন্ট ডেস্ক, ক্যাশিয়ার, টেকনিশিয়ান, ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>পেজের বড় সার্চ বক্সে কাস্টমারের ডিভাইসের সিরিয়াল নম্বরটি লিখো বা বারকোড দিয়ে স্ক্যান করো।</li>
                        <li><strong>Lookup Warranty</strong> বাটনে ক্লিক করো।</li>
                        <li>স্ক্রিনে সাথে সাথে বিক্রয় তারিখ, মূল ইনভয়েস নম্বর, ওয়ারেন্টি স্ট্যাটাস (Active নাকি Expired), এবং রিপ্লেসমেন্ট গ্যারান্টি ও ফ্রি সার্ভিসের আর কত দিন বাকি আছে তা দেখতে পাবে।</li>
                        <li>ওয়ারেন্টি একটিভ থাকলে সেখান থেকেই সরাসরি Exchange বা Service Ticket খোলার অপশন পাবে।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>একজন কাস্টমার ল্যাপটপ নিয়ে এসে জানালেন তার কিবোর্ডে সমস্যা। তিনি মেমো হারিয়ে ফেলেছেন। আপনি শুধু ল্যাপটপের সিরিয়াল দিয়ে সার্চ করলেন এবং দেখতে পেলেন ল্যাপটপটি ৪ মাস আগে কেনা হয়েছিল এবং এখনো ৮ মাস ফ্রি সার্ভিস ওয়ারেন্টি বাকি আছে।</p>
                    </div>
                </article>

                <!-- 5. Sale Return -->
                <article class="doc-card doc-topic" id="sec-return">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৫</span>
                            <span>পণ্য রিটার্ন বা ফেরত নেওয়া (ভালো বনাম ড্যামেজড)</span>
                        </div>
                        <a href="{{ route('return-sale.index') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-undo"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Sale <i class="fa fa-angle-right"></i> Sale Return</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">ক্যাশিয়ার, শাখা ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>যে সেল ইনভয়েসের বিপরীতে ফেরত নেওয়া হচ্ছে সেটির রেফারেন্স নম্বর সার্চ করে বের করো।</li>
                        <li>যে আইটেমটি ফেরত দেওয়া হচ্ছে তার সিরিয়াল নম্বর নির্বাচন করো।</li>
                        <li><strong>খুব গুরুত্বপূর্ণ:</strong> Condition / Return Action ড্রপডাউনে সঠিক অপশন নির্বাচন করো:
                            <ul>
                                <li><strong>Restock (Salable):</strong> ডিভাইস সম্পূর্ণ ফ্রেশ ও ভালো থাকলে এটি বেছে নাও। এটি সাথে সাথে ভালো স্টকে যুক্ত হবে।</li>
                                <li><strong>Damaged (Defective):</strong> ডিভাইসে কোনো ত্রুটি বা সমস্যা থাকলে এটি নির্বাচন করো। এটি ভালো স্টকে যাবে না, সরাসরি ড্যামেজ স্টকে চলে যাবে।</li>
                            </ul>
                        </li>
                        <li>রিফান্ডের মাধ্যম (ক্যাশ/অ্যাকাউন্ট) সিলেক্ট করে <strong>Submit</strong> চাপো।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>একজন কাস্টমার কেনার পরদিনই প্যাকেটসহ মাউস ফেরত নিয়ে এলেন কারণ তার প্রয়োজন নেই। ডিভাইসটি চেক করে সম্পূর্ণ ফ্রেশ পাওয়ায় ক্যাশিয়ার এটি Restock হিসেবে রিটার্ন করলেন, ফলে এটি সাথে সাথেই আবার বিক্রয়যোগ্য স্টকে যোগ হলো।</p>
                    </div>
                </article>

                <!-- 6. Device Exchange -->
                <article class="doc-card doc-topic" id="sec-exchange">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৬</span>
                            <span>পুরনো ডিভাইসের বদলে নতুন ডিভাইস এক্সচেঞ্জ বা সোয়াপ করা</span>
                        </div>
                        <a href="{{ route('exchange.create') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-exchange"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Service & Warranty <i class="fa fa-angle-right"></i> Device Exchange</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">ক্যাশিয়ার, শাখা ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li><strong>Old Serial Number</strong> বক্সে ফেরত আসা ডিভাইসের সিরিয়াল নম্বরটি লিখো।</li>
                        <li>সিস্টেম স্বয়ংক্রিয়ভাবে তার আগের ইনভয়েস ও ক্রয়মূল্য খুঁজে বের করবে।</li>
                        <li>ফেরত আসা ডিভাইসের অবস্থা অনুযায়ী <strong>Return Action</strong> (Restock নাকি Damaged) ঠিক করো।</li>
                        <li>কাস্টমার বিনিময়ে যে নতুন মডেল নিতে চান, <strong>New Replacement Product</strong> থেকে তা সিলেক্ট করো এবং নতুন সিরিয়াল নম্বরটি বেছে নাও।</li>
                        <li>সিস্টেম ব্যালেন্স হিসাব করে দেখাবে (সমান দাম হলে ৳০ সোয়াপ, দাম বেশি হলে অতিরিক্ত বাকি টাকা পেমেন্ট হিসেবে গ্রহণ করতে বলবে)।</li>
                        <li><strong>Confirm & Process Exchange</strong> বাটনে ক্লিক করে নতুন ইনভয়েস জেনারেট করো।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>একজন কাস্টমার ৩৫,০০০ টাকার ল্যাপটপ নিয়ে এলেন এবং ১০,০০০ টাকা অতিরিক্ত দিয়ে ৪৫,০০০ টাকার ল্যাপটপ নিতে চাইলেন। ক্যাশিয়ার এক্সচেঞ্জ পেজে গিয়ে পুরনো ও নতুন সিরিয়াল সিলেক্ট করলেন। সিস্টেম ব্যালেন্স হিসাব করে ১০,০০০ টাকা পেমেন্ট দেখালো। কাস্টমার নগদ টাকা পরিশোধ করায় নতুন এক্সচেঞ্জ ইনভয়েস তৈরি হয়ে গেল।</p>
                    </div>
                </article>

                <!-- 7. Pre-Orders -->
                <article class="doc-card doc-topic" id="sec-pre-order">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৭</span>
                            <span>অন্য শাখা থেকে ডিভাইসের জন্য কাস্টমার প্রি-অর্ডার বুক করা</span>
                        </div>
                        <a href="{{ route('pre_orders.create') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-plus-circle"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Pre-Orders <i class="fa fa-angle-right"></i> New Pre-Order</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">সব সেলস স্টাফ, ক্যাশিয়ার, ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>প্রোডাক্ট সিলেক্ট করো এবং <strong>Check Other Branches</strong> বাটনে ক্লিক করে অন্য কোন শাখায় স্টক আছে তা দেখে নাও।</li>
                        <li>সোর্স ব্রাঞ্চ (যে শাখা থেকে পণ্যটি আসবে) এবং ডেস্টিনেশন ব্রাঞ্চ (আপনার দোকান) নির্বাচন করো।</li>
                        <li>নির্দিষ্ট কোনো ইউনিক সিরিয়াল বুক করতে চাইলে সেই সিরিয়ালটি নির্বাচন করো। সাথে সাথে সোর্স ব্রাঞ্চে ওই সিরিয়ালটি <code>Reserved</code> হয়ে যাবে যাতে তারা অন্য কারও কাছে বিক্রি করতে না পারে।</li>
                        <li>কাস্টমারের নাম, ফোন নম্বর, মোট বিক্রয়মূল্য এবং প্রদত্ত অগ্রিম টাকা (Advance Amount) উল্লেখ করো।</li>
                        <li>সম্ভাব্য ডেলিভারির তারিখ লিখে <strong>Book Pre-Order</strong> বাটনে ক্লিক করো এবং কাস্টমারকে মানি রিসিট ও প্রি-অর্ডার টোকেন দাও।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>মিরপুর শাখায় একজন কাস্টমার Asus ZenBook খুঁজছেন, যা শুধু উত্তরা শাখায় ১ পিস স্টকে আছে। মিরপুরের বিক্রয়কর্মী প্রি-অর্ডার পেজে গিয়ে উত্তরা শাখা থেকে ওই ল্যাপটপের সিরিয়াল সিলেক্ট করলেন এবং কাস্টমারের কাছ থেকে ৩,০০০ টাকা অগ্রিম নিয়ে প্রি-অর্ডার বুক করলেন। সাথে সাথে উত্তরা শাখায় ল্যাপটপটি বুক হয়ে গেল।</p>
                    </div>
                </article>

                <!-- 8. Service Tickets -->
                <article class="doc-card doc-topic" id="sec-service">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৮</span>
                            <span>কাস্টমারের নষ্ট ডিভাইস মেরামতের জন্য জমা নেওয়া ও ট্র্যাক করা</span>
                        </div>
                        <a href="{{ route('service_jobs.index') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-ticket"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Service & Warranty <i class="fa fa-angle-right"></i> Service Tickets</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">ফ্রন্ট ডেস্ক স্টাফ, সার্ভিস টেকনিশিয়ান, ম্যানেজার, অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>ডিভাইসের সিরিয়াল নম্বর দিয়ে সার্চ করো।</li>
                        <li>কাস্টমারের নাম, ফোন নম্বর এবং তিনি কী সমস্যা পাচ্ছেন (Problem Description) তা বিস্তারিত লেখো।</li>
                        <li>ডিভাইসের সাথে কী এক্সেসরিজ নেওয়া হয়েছে (যেমন: চার্জার, ব্যাগ ইত্যাদি) বক্সে উল্লেখ করো।</li>
                        <li><strong>Create Service Ticket</strong> বাটনে চাপ দিয়ে কাস্টমারকে রিসিট টোকেন প্রিন্ট করে দাও।</li>
                        <li>টেকনিশিয়ান কাজ শুরু করলে স্ট্যাটাস আপডেট করবেন (Under Service, Parts Pending, Ready for Delivery)।</li>
                        <li>কাজ শেষে কাস্টমার এলে <strong>Deliver to Customer</strong> বাটনে ক্লিক করে চার্জ গ্রহণপূর্বক ডেলিভারি সম্পন্ন করো।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>কাস্টমার ডিসপ্লে না আসার কারণে ল্যাপটপ রেখে গেলেন। সার্ভিস পেজ থেকে চার্জারসহ ল্যাপটপ জমা নিয়ে টিকিট SRV-102 প্রিন্ট করে কাস্টমারকে দেওয়া হলো। টেকনিশিয়ান কাজ শেষে স্ট্যাটাস দিলেন Ready for Delivery এবং সার্ভিস চার্জ ৫০০ টাকা যোগ করলেন। কাস্টমার এসে ৫০০ টাকা দিয়ে ল্যাপটপ বুঝে নিলেন।</p>
                    </div>
                </article>

                <!-- 9. Damage & RMA -->
                <article class="doc-card doc-topic" id="sec-rma">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">৯</span>
                            <span>নষ্ট ডিভাইস লগ করা এবং সাপ্লায়ারের কাছে আরএমএ (RMA) পাঠানো</span>
                        </div>
                        <a href="{{ route('supplier_rma.create') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-truck"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Damage & RMA <i class="fa fa-angle-right"></i> New Supplier RMA</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">শাখা ম্যানেজার এবং অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>নষ্ট ডিভাইসের তালিকা দেখতে <strong>Damage Records</strong> পেজে যাও।</li>
                        <li>ভেন্ডরের কাছে পাঠানোর জন্য <strong>New Supplier RMA</strong> পেজে যাও।</li>
                        <li>সাপ্লায়ারের নাম ও শাখা নির্বাচন করে ড্যামেজড সিরিয়ালটি সিলেক্ট করো।</li>
                        <li>সমস্যা ও কুরিয়ার ট্র্যাকিং নম্বর দিয়ে <strong>Dispatch to Supplier</strong> চাপো।</li>
                        <li>সাপ্লায়ার সমাধান দিলে <strong>Supplier RMA List</strong> এ গিয়ে <strong>Resolve</strong> চাপো:
                            <ul>
                                <li>নতুন ফ্রেশ ডিভাইস দিলে: <strong>Replaced</strong> সিলেক্ট করে নতুন সিরিয়াল বসাও (এটি ভালো স্টকে ঢুকবে)।</li>
                                <li>টাকা ফেরত দিলে: <strong>Refunded</strong> সিলেক্ট করে প্রাপ্ত টাকার পরিমাণ বসাও।</li>
                            </ul>
                        </li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>দোকানে থাকা একটি ব্র্যান্ড নিউ মাদারবোর্ড ত্রুটিপূর্ণ পাওয়া গেল। ম্যানেজার এটিকে ড্যামেজ হিসেবে মার্ক করে সাপ্লায়ারের ঠিকানায় আরএমএ পাঠালেন। ৭ দিন পর সাপ্লায়ার একটি নতুন ফ্রেশ মাদারবোর্ড দিলে ম্যানেজার সিস্টেমে এসে নতুন সিরিয়াল দিয়ে টিকিটটি ক্লোজ করলেন এবং সাথে সাথে নতুন বোর্ডটি স্টকে চলে এলো।</p>
                    </div>
                </article>

                <!-- 10. Stock Transfer -->
                <article class="doc-card doc-topic" id="sec-transfer">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">১০</span>
                            <span>এক শাখা থেকে অন্য শাখায় পণ্য পাঠানো ও রিসিভ করা</span>
                        </div>
                        <a href="{{ route('transfers.create') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="dripicons-export"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Transfer <i class="fa fa-angle-right"></i> Add Transfer / Transfer List</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">শাখা ম্যানেজার ও সিনিয়র অনুমোদিত স্টাফ</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li><strong>পাঠানোর সময়:</strong> From Warehouse এবং To Warehouse নির্বাচন করে নির্দিষ্ট সিরিয়ালগুলো সিলেক্ট করো। স্ট্যাটাস Sent রেখে চালান প্রিন্ট করে মালের সাথে পাঠাও।</li>
                        <li><strong>গ্রহণ করার সময়:</strong> পার্সেল পৌঁছানোর পর Transfer List পেজে গিয়ে <strong>Receive Transfer</strong> বাটনে ক্লিক করো।</li>
                        <li>সিরিয়াল স্ক্যান করে মিলিয়ে নাও। কুরিয়ারে কোনো মাল ভেঙে গেলে বা নষ্ট হলে <strong>Report Damaged</strong> বক্সে টিক দাও।</li>
                        <li><strong>Confirm Receipt</strong> বাটনে চাপ দাও। ভালো ডিভাইসগুলো স্বয়ংক্রিয়ভাবে আপনার দোকানের স্টকে যুক্ত হয়ে যাবে।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>মতিঝিল শাখা থেকে ধানমন্ডি শাখায় ৩টি ল্যাপটপ পাঠানো হলো। ধানমন্ডি শাখা প্যাকেট খুলে দেখলো ২টি ভালো, ১টির বডি কুরিয়ারে ভেঙে গেছে। রিসিভ করার সময় ম্যানেজার ভাঙা সিরিয়ালটিকে ড্যামেজ টিক দিয়ে রিসিভ করলেন। ফলে ২টি ল্যাপটপ ভালো স্টকে এবং ভাঙা ল্যাপটপটি সরাসরি ড্যামেজ রেকর্ডে যুক্ত হলো।</p>
                    </div>
                </article>

                <!-- 11. Valuation Report -->
                <article class="doc-card doc-topic" id="sec-valuation">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">১১</span>
                            <span>সব শাখার মোট স্টকের বাজারমূল্য ও হিসাব দেখা (ভ্যালুয়েশন রিপোর্ট)</span>
                        </div>
                        <a href="{{ route('report.warehouseStockValuation') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="fa fa-balance-scale"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Reports <i class="fa fa-angle-right"></i> Warehouse Stock Valuation</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">শুধু শাখা ম্যানেজার এবং প্রধান অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>পেজে ঢুকলেই দোকানে মোট কয়টি আইটেম মজুত আছে এবং মোট কেনা দাম (Purchase Value) ও বিক্রয়মূল্য (Retail Value) দেখতে পাবে।</li>
                        <li>প্রতিটি শাখার আলাদা আলাদা টেবিল ও ট্যাব দেখে স্টক বিশ্লেষণ করো।</li>
                        <li>প্রয়োজনে Print বা Export to Excel বাটনে ক্লিক করে হিসাব সংরক্ষণ করো।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>মাসের শেষে মালিক বা অডিটর জানতে চাইলেন সব ব্রাঞ্চ মিলিয়ে দোকানে মোট কত টাকার ল্যাপটপ রয়েছে। ম্যানেজার এই পেজে ঢুকেই এক সেকেন্ডে মোট কেনা মূল্য ও বিক্রয়মূল্যের নির্ভুল রিপোর্ট প্রিন্ট করে দিলেন।</p>
                    </div>
                </article>

                <!-- 12. Warehouse Management -->
                <article class="doc-card doc-topic" id="sec-warehouse">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">১২</span>
                            <span>নতুন শাখা বা ওয়্যারহাউস যোগ করা ও পরিচালনা করা</span>
                        </div>
                        <a href="{{ route('warehouse.index') }}" target="_blank" class="btn btn-outline-primary btn-direct-link">
                            <i class="dripicons-home"></i> সরাসরি পেজে যান
                        </a>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <div class="meta-pill">
                            <strong>কোথায় পাবে:</strong>
                            <span class="val">Settings <i class="fa fa-angle-right"></i> Warehouse</span>
                        </div>
                        <div class="meta-pill">
                            <strong>কে ব্যবহার করতে পারবে:</strong>
                            <span class="val">শুধুমাত্র প্রধান অ্যাডমিন</span>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">ধাপে ধাপে কী করতে হবে:</h5>
                    <ol class="step-list">
                        <li>উপরে থাকা <strong>Add Warehouse</strong> বাটনে ক্লিক করো।</li>
                        <li>নতুন শাখার নাম, ফোন নম্বর, যোগাযোগের ইমেইল এবং পূর্ণ ঠিকানা লেখো।</li>
                        <li><strong>Submit</strong> বাটনে চাপ দাও। সাথে সাথে পুরো সিস্টেমে নতুন শাখাটি সক্রিয় হয়ে যাবে।</li>
                    </ol>
                    <div class="example-box">
                        <strong>বাস্তব উদাহরণ:</strong>
                        <p>খান গ্যাজেট চট্টগ্রামে নতুন আউটলেট চালু করতে যাচ্ছে। অ্যাডমিন সেটিংস থেকে 'Chattogram Branch' তৈরি করলেন। এর পর থেকেই ঢাকা থেকে চট্টগ্রামে স্টক ট্রান্সফার এবং চট্টগ্রামের সেলস কাউন্টারে স্বাধীনভাবে কেনাবেচা শুরু করা সম্ভব হলো।</p>
                    </div>
                </article>

                <!-- 13. Quick Reference Table -->
                <article class="doc-card doc-topic" id="sec-quick-table">
                    <div class="doc-card-header">
                        <div class="doc-card-title">
                            <span class="doc-card-num">১৩</span>
                            <span>কুইক-রেফারেন্স গাইড: কোন কাজের জন্য কোথায় যাব?</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="quick-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">আপনি যা করতে চান</th>
                                    <th style="width: 40%;">সাইডবারের মেনু পাথ</th>
                                    <th style="width: 25%;">কার অনুমতি আছে</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>নতুন পণ্যের অনেকগুলো সিরিয়াল একবারে এন্ট্রি করা</strong></td>
                                    <td><span class="code-path">Product</span> <i class="fa fa-angle-right"></i> <span class="code-path">Quick Paste</span></td>
                                    <td><span class="badge badge-info px-2 py-1">সব স্টাফ ও ম্যানেজার</span></td>
                                </tr>
                                <tr>
                                    <td><strong>ল্যাপটপের বারকোড ও কনফিগারেশন স্টিকার প্রিন্ট করা</strong></td>
                                    <td><span class="code-path">Product</span> <i class="fa fa-angle-right"></i> <span class="code-path">Print Barcode</span></td>
                                    <td><span class="badge badge-info px-2 py-1">সব স্টাফ ও ম্যানেজার</span></td>
                                </tr>
                                <tr>
                                    <td><strong>কাউন্টারে কাস্টমারের কাছে ডিভাইস বিক্রি ও রিসিট দেওয়া</strong></td>
                                    <td><span class="code-path">Sale</span> <i class="fa fa-angle-right"></i> <span class="code-path">POS</span></td>
                                    <td><span class="badge badge-success px-2 py-1">ক্যাশিয়ার ও সেলস স্টাফ</span></td>
                                </tr>
                                <tr>
                                    <td><strong>কাস্টমারের ওয়ারেন্টি বা গ্যারান্টি মেয়াদ চেক করা</strong></td>
                                    <td><span class="code-path">Service & Warranty</span> <i class="fa fa-angle-right"></i> <span class="code-path">Warranty Lookup</span></td>
                                    <td><span class="badge badge-info px-2 py-1">সব স্টাফ ও টেকনিশিয়ান</span></td>
                                </tr>
                                <tr>
                                    <td><strong>পুরনো ল্যাপটপ নিয়ে নতুন দেওয়া (সোয়াপ/এক্সচেঞ্জ)</strong></td>
                                    <td><span class="code-path">Service & Warranty</span> <i class="fa fa-angle-right"></i> <span class="code-path">Device Exchange</span></td>
                                    <td><span class="badge badge-warning text-dark px-2 py-1">ক্যাশিয়ার ও ম্যানেজার</span></td>
                                </tr>
                                <tr>
                                    <td><strong>সার্ভিসিংয়ের জন্য কাস্টমারের নষ্ট ল্যাপটপ জমা নেওয়া</strong></td>
                                    <td><span class="code-path">Service & Warranty</span> <i class="fa fa-angle-right"></i> <span class="code-path">Service Tickets</span></td>
                                    <td><span class="badge badge-info px-2 py-1">টেকনিশিয়ান ও ফ্রন্ট ডেস্ক</span></td>
                                </tr>
                                <tr>
                                    <td><strong>অন্য শাখা থেকে কাস্টমারের জন্য ডিভাইস অগ্রিম বুক করা</strong></td>
                                    <td><span class="code-path">Pre-Orders</span> <i class="fa fa-angle-right"></i> <span class="code-path">New Pre-Order</span></td>
                                    <td><span class="badge badge-info px-2 py-1">সব সেলস স্টাফ</span></td>
                                </tr>
                                <tr>
                                    <td><strong>আগের বুকিং করা প্রি-অর্ডারের তালিকা দেখা</strong></td>
                                    <td><span class="code-path">Pre-Orders</span> <i class="fa fa-angle-right"></i> <span class="code-path">Pre-Order List</span></td>
                                    <td><span class="badge badge-info px-2 py-1">সব সেলস স্টাফ</span></td>
                                </tr>
                                <tr>
                                    <td><strong>কাস্টমারের কাছ থেকে কোনো আইটেম ফেরত নেওয়া</strong></td>
                                    <td><span class="code-path">Sale</span> <i class="fa fa-angle-right"></i> <span class="code-path">Sale Return</span></td>
                                    <td><span class="badge badge-warning text-dark px-2 py-1">ক্যাশিয়ার ও ম্যানেজার</span></td>
                                </tr>
                                <tr>
                                    <td><strong>দোকানের কোনো নষ্ট পণ্যের তালিকা ও কারণ দেখা</strong></td>
                                    <td><span class="code-path">Damage & RMA</span> <i class="fa fa-angle-right"></i> <span class="code-path">Damage Records</span></td>
                                    <td><span class="badge badge-danger px-2 py-1">ম্যানেজার ও অ্যাডমিন</span></td>
                                </tr>
                                <tr>
                                    <td><strong>নষ্ট পণ্য সাপ্লায়ারের কাছে আরএমএ পাঠানো</strong></td>
                                    <td><span class="code-path">Damage & RMA</span> <i class="fa fa-angle-right"></i> <span class="code-path">Supplier RMA List</span></td>
                                    <td><span class="badge badge-danger px-2 py-1">ম্যানেজার ও অ্যাডমিন</span></td>
                                </tr>
                                <tr>
                                    <td><strong>এক শাখা থেকে অন্য শাখায় পণ্য পাঠিয়ে চালান দেওয়া</strong></td>
                                    <td><span class="code-path">Transfer</span> <i class="fa fa-angle-right"></i> <span class="code-path">Add Transfer</span></td>
                                    <td><span class="badge badge-secondary px-2 py-1">ম্যানেজার ও সিনিয়র স্টাফ</span></td>
                                </tr>
                                <tr>
                                    <td><strong>অন্য শাখা থেকে আসা পণ্য স্ক্যান করে রিসিভ করা</strong></td>
                                    <td><span class="code-path">Transfer</span> <i class="fa fa-angle-right"></i> <span class="code-path">Transfer List</span></td>
                                    <td><span class="badge badge-secondary px-2 py-1">ম্যানেজার ও সিনিয়র স্টাফ</span></td>
                                </tr>
                                <tr>
                                    <td><strong>দোকানের মোট স্টকে কত টাকার মাল আছে তা দেখা</strong></td>
                                    <td><span class="code-path">Reports</span> <i class="fa fa-angle-right"></i> <span class="code-path">Warehouse Stock Valuation</span></td>
                                    <td><span class="badge badge-danger px-2 py-1">ম্যানেজার ও অ্যাডমিন</span></td>
                                </tr>
                                <tr>
                                    <td><strong>নতুন শোরুম/শাখা তৈরি করা বা তথ্য বদলানো</strong></td>
                                    <td><span class="code-path">Settings</span> <i class="fa fa-angle-right"></i> <span class="code-path">Warehouse</span></td>
                                    <td><span class="badge badge-dark px-2 py-1">শুধুমাত্র প্রধান অ্যাডমিন</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Live Search Filter
        document.getElementById('docSearch').addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            var cards = document.querySelectorAll('.doc-topic');
            cards.forEach(function(card) {
                var content = card.innerText.toLowerCase();
                if (query === '' || content.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Smooth Scroll & Active Link highlighting
        document.querySelectorAll('.doc-nav-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');
                if (targetId.startsWith('#')) {
                    e.preventDefault();
                    var targetEl = document.querySelector(targetId);
                    if (targetEl) {
                        window.scrollTo({
                            top: targetEl.offsetTop - 90,
                            behavior: 'smooth'
                        });
                        document.querySelectorAll('.doc-nav-link').forEach(function(el) { el.classList.remove('active'); });
                        this.classList.add('active');
                    }
                }
            });
        });
    </script>
</body>
</html>
