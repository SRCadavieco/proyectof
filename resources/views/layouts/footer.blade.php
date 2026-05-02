<!-- ================= FOOTER ================= -->
<footer class="border-t border-cream-300 bg-cream-100">
    <div class="max-w-7xl mx-auto px-6 py-16">
        <div class="flex flex-col md:flex-row justify-between items-start gap-10">
            <!-- Brand -->
            <div class="space-y-4">
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-10 w-10">
                    <span class="font-serif text-xl text-ink">FabricAI</span>
                </a>
                <p class="text-sm text-ink-muted max-w-xs">AI-powered clothing design, crafted for creators.</p>
                <div class="w-10 h-px bg-accent"></div>
            </div>
            <!-- Links -->
            <div class="flex gap-16 text-sm">
                <div class="space-y-3">
                    <p class="font-medium text-ink uppercase tracking-widest text-xs">Product</p>
                    <a href="/#how-it-works" class="block text-ink-muted hover:text-accent transition-colors">How it works</a>
                    <a href="/pricing" class="block text-ink-muted hover:text-accent transition-colors">Pricing</a>
                    <a href="/faq" class="block text-ink-muted hover:text-accent transition-colors">FAQ</a>
                </div>
                <div class="space-y-3">
                    <p class="font-medium text-ink uppercase tracking-widest text-xs">Legal</p>
                    <a href="#" class="block text-ink-muted hover:text-accent transition-colors">Privacy</a>
                    <a href="#" class="block text-ink-muted hover:text-accent transition-colors">Terms</a>
                </div>
            </div>
        </div>
        <div class="mt-12 pt-8 border-t border-cream-300 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-ink-muted">
            <span>&copy; {{ date('Y') }} FabricAI. All rights reserved.</span>
            <span class="flex items-center gap-2">
                Made with <span class="text-accent">&#9679;</span> for creators
            </span>
        </div>
    </div>
</footer>
