<!DOCTYPE html>
<html lang="en" style="background:#0d0d0d">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Privacy Policy — FabricAI</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes float-a {
            0%,100% { transform: translateY(0) rotate(0deg); }
            40%     { transform: translateY(-22px) rotate(1.5deg); }
            70%     { transform: translateY(-10px) rotate(-1deg); }
        }
        @keyframes float-b {
            0%,100% { transform: translateY(0); }
            35%     { transform: translateY(16px); }
            65%     { transform: translateY(7px); }
        }
        @keyframes shimmer-move {
            0%   { background-position: -280% 0; }
            100% { background-position:  280% 0; }
        }
        @keyframes scan-down {
            0%   { top: -3px; opacity: 1; }
            85%  { opacity: 0.5; }
            100% { top: 100%; opacity: 0; }
        }
        @keyframes pulse-ring {
            0%   { transform: scale(1);   opacity: 0.55; }
            100% { transform: scale(2.4); opacity: 0; }
        }
        .orb { position: absolute; border-radius: 50%; pointer-events: none; filter: blur(110px); }
        .orb-a { background: radial-gradient(circle, rgba(124,60,160,0.28) 0%, transparent 60%); }
        .orb-b { background: radial-gradient(circle, rgba(90,34,117,0.18)  0%, transparent 60%); }
        .scan-line {
            position: absolute; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(157,91,199,0.55), transparent);
            pointer-events: none; animation: scan-down 8s linear infinite;
        }
        .gradient-text {
            background: linear-gradient(135deg, #9d5bc7 0%, #c084fc 35%, #7c3ca0 65%, #9d5bc7 100%);
            background-size: 280% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; animation: shimmer-move 5s linear infinite;
        }
        .factory-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.033) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.033) 1px, transparent 1px);
            background-size: 72px 72px;
        }
        .tag-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 5px 14px; border-radius: 999px;
            background: rgba(124,60,160,0.12);
            border: 1px solid rgba(124,60,160,0.3);
            font-size: 10px; font-weight: 600;
            letter-spacing: .22em; text-transform: uppercase; color: #9d5bc7;
        }
        .pulse-dot { position: relative; display: inline-block; }
        .pulse-dot::before {
            content: ''; position: absolute; inset: -5px; border-radius: 50%;
            background: rgba(124,60,160,0.35);
            animation: pulse-ring 2.2s ease-out infinite;
        }
        .terms-section h2 {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(124,60,160,0.25);
        }
        .terms-section p, .terms-section li {
            color: rgba(255,255,255,0.45);
            font-size: 0.875rem;
            line-height: 1.75;
        }
        .terms-section ul {
            list-style: none;
            padding-left: 0;
        }
        .terms-section ul li {
            padding-left: 1.25rem;
            position: relative;
        }
        .terms-section ul li::before {
            content: '–';
            position: absolute;
            left: 0;
            color: rgba(157,91,199,0.6);
        }
        .terms-section a {
            color: #c084fc;
            text-decoration: none;
        }
        .terms-section a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="bg-[#0d0d0d] text-white font-sans antialiased overflow-x-hidden">
@php $navDarkHero = true; @endphp
@include('layouts.navigation')

<!-- HERO -->
<section class="relative pt-20 pb-20 text-center overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="orb orb-a" style="width:600px;height:600px;top:-180px;right:-80px;animation:float-a 10s ease-in-out infinite;"></div>
    <div class="orb orb-b" style="width:400px;height:400px;bottom:0;left:-80px;animation:float-b 13s ease-in-out infinite 2s;"></div>
    <div class="scan-line"></div>
    <div class="absolute top-8 right-8 w-6 h-6 border-t-2 border-r-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div class="absolute top-8 left-8 w-6 h-6 border-t-2 border-l-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 80)"
         :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
         class="relative z-10 max-w-3xl mx-auto px-6 transition-all duration-1000 ease-out">
        <div class="mb-8">
            <span class="tag-pill">
                <span class="pulse-dot w-1.5 h-1.5 rounded-full inline-block" style="background:#9d5bc7"></span>
                Legal
            </span>
        </div>
        <h1 class="font-serif leading-tight mb-6" style="font-size:clamp(2.8rem,7vw,6rem)">
            <span class="text-white">Privacy</span><br>
            <span class="italic gradient-text">policy.</span>
        </h1>
        <p class="text-white/50 text-lg max-w-xl mx-auto">
            How FabricAI collects, uses, and protects your information.
        </p>
    </div>
</section>

<!-- PRIVACY CONTENT -->
<section class="relative pb-24 overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="relative z-10 max-w-3xl mx-auto px-6">
        <div class="rounded-2xl overflow-hidden space-y-10 p-8 sm:p-12 terms-section"
             style="border:1px solid rgba(255,255,255,0.07);background:#111;">

            <!-- Last updated -->
            <p class="text-xs text-white/25 uppercase tracking-widest">Effective date: 02 May 2026</p>

            <!-- 1 -->
            <div>
                <h2>1. Information We Collect</h2>
                <div class="space-y-5 mt-2">
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">1.1 Account Information</p>
                        <p class="mb-3">When you create an account, we may collect:</p>
                        <ul class="space-y-2 mb-3">
                            <li>Email address</li>
                            <li>Account identifiers</li>
                            <li>Subscription status</li>
                        </ul>
                        <p>We do not collect passwords for third-party services such as Printify or Stripe.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">1.2 Payment Information</p>
                        <p>Payments are processed through Stripe. FabricAI does not store or have access to your full payment card details. Stripe processes all billing information in accordance with its own privacy and security policies.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">1.3 Printify API Token</p>
                        <p class="mb-3">To enable design generation and product creation, FabricAI requires users to provide a custom Printify API token.</p>
                        <p class="text-white/60 text-sm font-medium mb-1">The Printify API token:</p>
                        <ul class="space-y-2 mb-3">
                            <li>Allows FabricAI to communicate with Printify on your behalf</li>
                            <li>Enables product creation, mockups, and publishing workflows</li>
                            <li>Does not grant FabricAI ownership or control of your Printify account</li>
                        </ul>
                        <p class="text-white/60 text-sm font-medium mb-1">FabricAI cannot:</p>
                        <ul class="space-y-2 mb-3">
                            <li>Change your Printify login credentials</li>
                            <li>Access your Printify password</li>
                            <li>Withdraw funds, make payments, or access billing details</li>
                            <li>Access unrelated Printify account data beyond API-permitted actions</li>
                        </ul>
                        <p>The API token functions as a limited technical permission key, not a password. You may revoke or regenerate this token at any time from your Printify dashboard, immediately disabling FabricAI's access.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">1.4 Usage Data</p>
                        <p class="mb-3">We may collect limited usage data such as:</p>
                        <ul class="space-y-2">
                            <li>Feature usage and credit consumption</li>
                            <li>Error logs and performance metrics</li>
                            <li>Device and browser information (via cookies or similar technologies)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 2 -->
            <div>
                <h2>2. How We Use Your Information</h2>
                <p class="mb-3">We use collected information to:</p>
                <ul class="space-y-2 mb-3">
                    <li>Provide and operate the FabricAI Service</li>
                    <li>Generate AI-based designs and workflows</li>
                    <li>Manage subscriptions, credits, and billing status</li>
                    <li>Improve performance, reliability, and user experience</li>
                    <li>Communicate important service-related notices</li>
                </ul>
                <p>We do not sell your personal data.</p>
            </div>

            <!-- 3 -->
            <div>
                <h2>3. AI Prompts and Generated Content</h2>
                <div class="space-y-4 mt-2">
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">3.1 Prompts</p>
                        <p>Text prompts and inputs you submit may be temporarily processed and stored to generate outputs and improve system performance.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">3.2 Generated Designs</p>
                        <p>Generated designs may be stored for a limited time to allow downloads, revisions, or re-generation. FabricAI does not claim ownership of your generated designs.</p>
                    </div>
                </div>
            </div>

            <!-- 4 -->
            <div>
                <h2>4. Data Sharing and Third Parties</h2>
                <p class="mb-3">FabricAI only shares data with trusted third parties necessary to operate the Service, including:</p>
                <ul class="space-y-2 mb-3">
                    <li>Stripe (payments and subscriptions)</li>
                    <li>Printify (product creation and publishing via API)</li>
                    <li>Infrastructure and hosting providers</li>
                </ul>
                <p>These third parties process data in accordance with their own privacy policies.</p>
            </div>

            <!-- 5 -->
            <div>
                <h2>5. Data Retention</h2>
                <p class="mb-3">We retain personal data only for as long as necessary to:</p>
                <ul class="space-y-2 mb-3">
                    <li>Provide the Service</li>
                    <li>Comply with legal and accounting obligations</li>
                    <li>Resolve disputes and enforce agreements</li>
                </ul>
                <p>You may request deletion of your account and associated data, subject to legal retention requirements.</p>
            </div>

            <!-- 6 -->
            <div>
                <h2>6. Security Measures</h2>
                <p class="mb-3">FabricAI implements reasonable technical and organizational safeguards to protect your information, including:</p>
                <ul class="space-y-2 mb-3">
                    <li>Secure storage of API tokens</li>
                    <li>Encrypted communications (HTTPS)</li>
                    <li>Restricted internal access controls</li>
                </ul>
                <p>However, no system is completely secure, and we cannot guarantee absolute security.</p>
            </div>

            <!-- 7 -->
            <div>
                <h2>7. Your Rights</h2>
                <p class="mb-3">Depending on your jurisdiction, you may have rights to:</p>
                <ul class="space-y-2 mb-3">
                    <li>Access your personal data</li>
                    <li>Request correction or deletion</li>
                    <li>Object to or restrict processing</li>
                    <li>Withdraw consent where applicable</li>
                </ul>
                <p>Requests may be submitted using the contact information below.</p>
            </div>

            <!-- 8 -->
            <div>
                <h2>8. International Data Transfers</h2>
                <p>Your information may be processed or stored in countries other than your own. Where required, we implement appropriate safeguards for international data transfers.</p>
            </div>

            <!-- 9 -->
            <div>
                <h2>9. Children's Privacy</h2>
                <p>FabricAI is not intended for individuals under the age of 18. We do not knowingly collect personal data from children.</p>
            </div>

            <!-- 10 -->
            <div>
                <h2>10. Changes to This Privacy Policy</h2>
                <p>We may update this Privacy Policy from time to time. Material changes will be communicated through the Service or by email. Continued use of FabricAI constitutes acceptance of the updated policy.</p>
            </div>

            <!-- 11 -->
            <div>
                <h2>11. Contact Information</h2>
                <p>If you have questions or requests regarding this Privacy Policy, please contact us at:</p>
                <p class="mt-2">Email: <a href="mailto:support@fabricai.net">support@fabricai.net</a></p>
            </div>

            <!-- Acknowledgement -->
            <div class="pt-4" style="border-top:1px solid rgba(255,255,255,0.06)">
                <p class="text-white/25 text-xs">By using FabricAI, you acknowledge that you have read and understood this Privacy Policy.</p>
            </div>

        </div>
    </div>
</section>

@include('layouts.footer')
</body>
</html>
