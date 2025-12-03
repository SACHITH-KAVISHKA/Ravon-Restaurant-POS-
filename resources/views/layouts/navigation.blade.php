<nav class="bg-gradient-to-r from-black via-gray-900 to-black border-b border-blue-900/50 shadow-xl">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <!-- Logo & Brand -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-700 p-2.5 rounded-lg shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-blue-600">
                            RAVON POS
                        </h1>
                        <p class="text-xs text-gray-400">Restaurant Management</p>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            @auth
            <div class="hidden md:flex items-center space-x-2">
                @role('cashier')
                <a href="{{ route('pos.index') }}" class="px-4 py-2 rounded-lg text-gray-300 hover:bg-blue-600/20 hover:text-blue-400 transition flex items-center space-x-2 {{ request()->routeIs('pos.*') ? 'bg-blue-600/30 text-blue-400' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">POS</span>
                </a>
                @endrole
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <!-- Time Display -->
                <div class="hidden lg:block text-right">
                    <div class="text-sm font-semibold text-blue-400" id="current-time"></div>
                    <div class="text-xs text-gray-500" id="current-date"></div>
                </div>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 border border-gray-700 transition">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        </div>
                        <div class="hidden md:block text-left">
                            <div class="text-sm font-semibold text-gray-200">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst(Auth::user()->getRoleNames()->first()) }}</div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 rounded-lg shadow-xl bg-gray-800 border border-gray-700 py-1 z-50">
                        <div class="px-4 py-2 border-b border-gray-700">
                            <div class="text-xs text-gray-400">Logged in as</div>
                            <div class="text-sm font-semibold text-white">{{ Auth::user()->username }}</div>
                        </div>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-blue-600/20 hover:text-blue-400">
                            Profile Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-600/20">
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