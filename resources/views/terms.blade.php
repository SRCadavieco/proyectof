<!DOCTYPE html>
<html lang="en" style="background:#0d0d0d">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terms of Use — FabricAI</title>
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
            <span class="text-white">Terms</span><br>
            <span class="italic gradient-text">of use.</span>
        </h1>
        <p class="text-white/50 text-lg max-w-xl mx-auto">
            Please read these terms carefully before using FabricAI.
        </p>
    </div>
</section>

<!-- TERMS CONTENT -->
<section class="relative pb-24 overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="relative z-10 max-w-3xl mx-auto px-6">
        <div class="rounded-2xl overflow-hidden space-y-10 p-8 sm:p-12 terms-section"
             style="border:1px solid rgba(255,255,255,0.07);background:#111;">

            <!-- Last updated -->
            <p class="text-xs text-white/25 uppercase tracking-widest">Effective date: 02 May 2026</p>

            <!-- 1 -->
            <div>
                <h2>1. Acceptance of Terms</h2>
                <p>These Terms of Use govern your access to and use of FabricAI, an AI-powered clothing and fabric design platform integrated with Printify and related print-on-demand services. By accessing or using FabricAI, you agree to be bound by these Terms. If you do not agree, you must not use the Service. FabricAI is operated from Spain.</p>
            </div>

            <!-- 2 -->
            <div>
                <h2>2. Description of the Service</h2>
                <p>FabricAI provides AI-generated clothing and fabric design outputs based on user inputs (Prompts). Designs may be exported, downloaded, or sent to third-party platforms via Printify for production and sale. FabricAI does not manufacture, sell, or distribute physical goods.</p>
            </div>

            <!-- 3 -->
            <div>
                <h2>3. Account Registration</h2>
                <p>You are required to create an account to access the features. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. Account creation is done with Google authentication, and, additionally, completed by linking your Printify API token, which is a must to create the final product.</p>
            </div>

            <!-- 4 -->
            <div>
                <h2>4. Credits, Subscriptions, and Payments</h2>
                <div class="space-y-4 mt-2">
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">4.1 Credit System</p>
                        <p>FabricAI operates on a credit-based system. Credits are required to generate designs or access certain features. Credit usage and limits may vary by subscription plan.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">4.2 Subscription Billing</p>
                        <p>Paid subscriptions are processed through Stripe. By subscribing, you authorize FabricAI and Stripe to charge your selected payment method on a recurring basis according to your plan. Subscription prices may change in the future, but you will receive a warning to accept or decline the continuation of the plan.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">4.3 Cancellation</p>
                        <p>You may cancel your subscription at any time. Cancellation will take effect at the end of the current billing cycle. Unused credits do not roll over unless explicitly stated.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">4.4 Refund Policy</p>
                        <p>All payments are non-refundable, except where refunds are required by applicable law. FabricAI does not provide refunds for unused credits, canceled subscriptions, or dissatisfaction with generated content.</p>
                    </div>
                </div>
            </div>

            <!-- 5 -->
            <div>
                <h2>5. User Content and Prompts</h2>
                <div class="space-y-4 mt-2">
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">5.1 Ownership of Prompts</p>
                        <p>You retain ownership of the text prompts and other content you submit to FabricAI ("User Content"). You grant FabricAI a non-exclusive, worldwide, royalty-free license to use, process, and store your User Content solely to operate and improve the Service.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">5.2 Responsibility for Prompts</p>
                        <p>You are solely responsible for your User Content. You agree not to submit prompts that infringe, misappropriate, or violate any intellectual property rights, privacy rights, or other rights of any third party.</p>
                    </div>
                </div>
            </div>

            <!-- 6 -->
            <div>
                <h2>6. AI-Generated Content and Copyright Disclaimer</h2>
                <div class="space-y-4 mt-2">
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">6.1 Nature of AI-Generated Content</p>
                        <p>FabricAI uses generative artificial intelligence to produce design outputs (Generated Content). Due to the nature of AI, Generated Content may be similar or identical to existing works, including copyrighted works.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">6.2 No Guarantee of Non-Infringement</p>
                        <p>FabricAI does not guarantee that Generated Content is original, non-infringing, or suitable for commercial use. You acknowledge and agree that use of Generated Content is at your sole risk.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">6.3 User Responsibility for Use</p>
                        <p>You are solely responsible for reviewing, validating, and ensuring that any Generated Content complies with applicable laws, including copyright, trademark, and design laws, before using, selling, or distributing it.</p>
                    </div>
                    <div>
                        <p class="text-white/60 text-sm font-medium mb-1">6.4 Indemnification for IP Claims</p>
                        <p>You agree to indemnify, defend, and hold harmless FabricAI and its affiliates from any claims, damages, liabilities, or expenses arising from your use of Generated Content, including but not limited to intellectual property infringement claims.</p>
                    </div>
                </div>
            </div>

            <!-- 7 -->
            <div>
                <h2>7. License to Generated Content</h2>
                <p>Subject to your compliance with these Terms and payment of applicable fees, FabricAI grants you a non-exclusive, worldwide license to use Generated Content for personal or commercial purposes. This license does not transfer ownership and may be revoked if you violate these Terms.</p>
            </div>

            <!-- 8 -->
            <div>
                <h2>8. Prohibited Uses</h2>
                <p class="mb-3">You agree not to:</p>
                <ul class="space-y-2">
                    <li>Use the Service for illegal purposes</li>
                    <li>Generate content that infringes intellectual property rights</li>
                    <li>Attempt to reverse engineer or misuse the AI models</li>
                    <li>Resell, sublicense, or exploit the Service itself without authorization</li>
                    <li>Use FabricAI to create content that is defamatory, obscene, hateful, or otherwise unlawful</li>
                </ul>
            </div>

            <!-- 9 -->
            <div>
                <h2>9. Third-Party Services</h2>
                <p>FabricAI may integrate with third-party services such as Printify and Stripe. FabricAI is not responsible for third-party services, products, policies, or actions. Your use of third-party services is governed by their respective terms.</p>
            </div>

            <!-- 10 -->
            <div>
                <h2>10. Termination</h2>
                <p>We may suspend or terminate your access to FabricAI at any time, with or without notice, if you violate these Terms or if required for legal or security reasons.</p>
            </div>

            <!-- 11 -->
            <div>
                <h2>11. Disclaimer of Warranties</h2>
                <p>FabricAI is provided "AS IS" and "AS AVAILABLE" without warranties of any kind, express or implied, including warranties of merchantability, fitness for a particular purpose, or non-infringement.</p>
            </div>

            <!-- 12 -->
            <div>
                <h2>12. Limitation of Liability</h2>
                <p>To the maximum extent permitted by law, FabricAI shall not be liable for any indirect, incidental, consequential, or special damages arising out of or related to your use of the Service or Generated Content.</p>
            </div>

            <!-- 13 -->
            <div>
                <h2>13. Governing Law</h2>
                <p>These Terms shall be governed by and construed in accordance with the laws of Spain, without regard to conflict of law principles.</p>
            </div>

            <!-- 14 -->
            <div>
                <h2>14. Changes to These Terms</h2>
                <p>We may update these Terms from time to time. Continued use of the Service after changes become effective constitutes acceptance of the updated Terms.</p>
            </div>

            <!-- 15 -->
            <div>
                <h2>15. Contact Information</h2>
                <p>If you have questions about these Terms, please contact us at:<br>
                Email: <a href="mailto:support@fabricai.net">support@fabricai.net</a></p>
            </div>

            <!-- Footer note -->
            <div class="pt-4 border-t" style="border-color:rgba(124,60,160,0.2)">
                <p class="text-xs text-white/25 italic">By using FabricAI, you acknowledge that you have read, understood, and agreed to these Terms of Use.</p>
            </div>
        </div>
    </div>
</section>

@include('layouts.footer')
</body>
</html>
