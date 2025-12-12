<nav class="bg-white border-b border-gray-200 shadow-md"  style="background: linear-gradient(90deg, rgba(255,255,255,0.98) 0%, rgba(255,255,255,1) 50%, rgba(255,255,255,0.98) 100%);">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <!-- Logo & Brand -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-[#667eea] to-[#764ba2] p-2.5 rounded-md shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            RAVON POS
                        </h1>
                        <p class="text-xs text-gray-600">Restaurant Management</p>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            @auth
            <div class="hidden md:flex items-center space-x-2">
                @role('cashier')
                <a href="{{ route('pos.index') }}" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition flex items-center space-x-2 {{ request()->routeIs('pos.*') ? 'bg-purple-100 text-purple-700 font-semibold border border-purple-200' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">POS</span>
                </a>
                @endrole

                @role('admin')
                <a href="{{ route('sales-report.index') }}" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition flex items-center space-x-2 {{ request()->routeIs('sales-report.*') ? 'bg-purple-100 text-purple-700 font-semibold border border-purple-200' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">Reports</span>
                </a>
                @endrole
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <!-- Time Display -->
                <div class="hidden lg:block text-right">
                    <div class="text-sm font-semibold text-purple-600" id="current-time"></div>
                    <div class="text-xs text-gray-500" id="current-date"></div>
                </div>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 px-4 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 border border-gray-200 transition">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#667eea] to-[#764ba2] flex items-center justify-center text-white font-bold shadow-md">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        </div>
                        <div class="hidden md:block text-left">
                            <div class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst(Auth::user()->getRoleNames()->first()) }}</div>
                        </div>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg border border-gray-200 py-1 z-50 bg-white">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <div class="text-xs text-gray-500">Logged in as</div>
                            <div class="text-sm font-semibold text-gray-800">{{ Auth::user()->username }}</div>
                        </div>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                            Profile Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endauth
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    // Update time every second
    function updateTime() {
        const now = new Date();
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');

        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        if (dateElement) {
            dateElement.textContent = now.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }

    updateTime();
    setInterval(updateTime, 1000);
</script>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>