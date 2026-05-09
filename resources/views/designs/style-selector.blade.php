<x-app-layout>
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
    <!-- Modal Container -->
    <div class="w-full max-w-6xl">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-3">
                Empieza a generar ya
            </h1>
            <p class="text-lg text-gray-300">
                ¿Qué tipo de diseño te gustaría crear?
            </p>
        </div>

        <!-- Design Styles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Default Style -->
            <div class="group cursor-pointer style-card" data-style="default">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <!-- Image -->
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/default.webp') }}" alt="Default Style" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <!-- Content -->
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Por Defecto</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Ilustración vectorial limpia con colores planos y contornos atrevidos.</p>
                        <div class="text-xs text-blue-400 font-medium">Vector • Flat Design</div>
                    </div>
                </div>
            </div>

            <!-- Realistic Drawing -->
            <div class="group cursor-pointer style-card" data-style="realistic_drawing">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/realistic_drawing.webp') }}" alt="Realistic Drawing" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Dibujo Realista</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Proporciones naturales, acabado hecho a mano con sombreado refinado.</p>
                        <div class="text-xs text-blue-400 font-medium">Sketch • Realistic</div>
                    </div>
                </div>
            </div>

            <!-- Cartoon Drawing -->
            <div class="group cursor-pointer style-card" data-style="cartoon_drawing">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/cartoon_drawing.webp') }}" alt="Cartoon Drawing" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Dibujo Animado</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Formas simplificadas, trazo expresivo y colores vibrantes.</p>
                        <div class="text-xs text-blue-400 font-medium">Cartoon • Playful</div>
                    </div>
                </div>
            </div>

            <!-- Vector Art -->
            <div class="group cursor-pointer style-card" data-style="vector_art">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/vector_art.webp') }}" alt="Vector Art" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Arte Vectorial</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Rellenos limpios, contornos nítidos, look gráfico escalable.</p>
                        <div class="text-xs text-blue-400 font-medium">Vector • Minimalist</div>
                    </div>
                </div>
            </div>

            <!-- Photorealistic -->
            <div class="group cursor-pointer style-card" data-style="photorealistic">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/photorealistic.webp') }}" alt="Photorealistic" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Fotorrealista</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Iluminación realista, texturas y profundidad natural.</p>
                        <div class="text-xs text-blue-400 font-medium">Photo • Realistic</div>
                    </div>
                </div>
            </div>

            <!-- Ghibli -->
            <div class="group cursor-pointer style-card" data-style="ghibli">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/ghibli.webp') }}" alt="Ghibli" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Ghibli</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Estilo anime pintado a mano, paleta cálida y atmósfera mágica.</p>
                        <div class="text-xs text-blue-400 font-medium">Anime • Whimsical</div>
                    </div>
                </div>
            </div>

            <!-- Manga -->
            <div class="group cursor-pointer style-card" data-style="manga">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-blue-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-slate-800 overflow-hidden">
                        <img src="{{ asset('images/design-styles/manga.webp') }}" alt="Manga" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Manga</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Líneas de tinta expresivas, composición dinámica y contraste.</p>
                        <div class="text-xs text-blue-400 font-medium">Manga • Dynamic</div>
                    </div>
                </div>
            </div>

            <!-- Tu Propio Estilo -->
            <div class="group cursor-pointer style-card" data-style="custom">
                <div class="bg-slate-700/50 hover:bg-slate-600/50 rounded-xl overflow-hidden transition-all duration-300 border border-slate-600 hover:border-purple-500 h-full flex flex-col">
                    <div class="relative w-full h-40 bg-gradient-to-br from-purple-900/30 to-blue-900/30 flex items-center justify-center overflow-hidden">
                        <div class="text-center">
                            <i class="fas fa-sparkles text-4xl text-purple-400 group-hover:scale-110 transition-transform duration-300"></i>
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Tu Propio Estilo</h3>
                        <p class="text-sm text-gray-300 mb-3 flex-1">Describe exactamente lo que imaginas sin restricciones de contexto.</p>
                        <div class="text-xs text-purple-400 font-medium">Creative • Unlimited</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Skip Button -->
        <div class="text-center">
            <a href="{{ route('designs.form') }}" class="inline-block text-gray-400 hover:text-gray-300 text-sm font-medium transition-colors">
                Omitir selección
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const styleCards = document.querySelectorAll('.style-card');
        
        styleCards.forEach(card => {
            card.addEventListener('click', function() {
                const style = this.dataset.style;
                // Crear un chat y redirigir con el estilo seleccionado
                // Por ahora, solo guardamos en sessionStorage y redirigimos
                sessionStorage.setItem('selectedDesignStyle', style);
                window.location.href = '{{ route("designs.form") }}?style=' + style;
            });
        });
    });
</script>

<style>
    .style-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .style-card:hover {
        transform: translateY(-4px);
    }
</style>
</x-app-layout>
