<aside class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col">
    <div class="p-4 border-b border-gray-700">
        <h2 class="text-lg font-bold text-white">Management</h2>
    </div>
    <nav class="flex-1 p-4 space-y-2">
        <!-- Menu Items -->
        <a href="{{ route('menu.index') }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('menu.index') || request()->routeIs('menu.items.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span class="font-medium">Menu Items</span>
        </a>

        <!-- Categories -->
        <a href="{{ route('menu.categories.index') }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('menu.categories.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <span class="font-medium">Categories</span>
        </a>

        <!-- Kitchen -->
        <a href="{{ route('kitchen.index') }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('kitchen.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span class="font-medium">Kitchen</span>
        </a>

        <!-- Payments -->
        <a href="{{ route('payments.index') }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('payments.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="font-medium">Payments</span>
        </a>

        <!-- Reports -->
        <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('reports.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="font-medium">Reports</span>
        </a>
    </nav>
</aside>