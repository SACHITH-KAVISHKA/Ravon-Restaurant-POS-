<aside class="w-72 border-r border-gray-200 flex flex-col overflow-y-auto shadow-md sticky top-0 h-screen"
    style="background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);">
    <div class="p-4 border-b border-white/20" style="background: rgba(255, 255, 255, 0.1);">
        <h2 class="text-lg font-bold text-white">Management</h2>
    </div>
    <nav class="flex-1 p-4 space-y-2">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-medium">Dashboard</span>
        </a>

        <!-- Menu Management (Admin Only) -->
        @hasanyrole('admin|superadmin')
        <div x-data="{ open: {{ request()->routeIs('menu.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 {{ request()->routeIs('menu.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="font-medium">Menu</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                <a href="{{ route('menu.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('menu.index') || request()->routeIs('menu.items.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Menu Items</span>
                </a>

                <a href="{{ route('menu.categories.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('menu.categories.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Categories</span>
                </a>

                <a href="{{ route('menu.rm-report.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('menu.rm-report.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2a4 4 0 014-4h8m0 0l-3-3m3 3l-3 3M3 7h12m0 0l-3-3m3 3l-3 3" />
                    </svg>
                    <span>RM Report</span>
                </a>
            </div>
        </div>
        @endhasanyrole

        <!-- Reports (Collapsible) -->
        @hasanyrole('superadmin|admin|manager|cashier')
        <div
            x-data="{ open: {{ request()->routeIs('reports.*') || request()->routeIs('sales-report.*') || request()->routeIs('void-report.*') || request()->routeIs('wastage-report.*') || request()->routeIs('vat-report.*') || request()->routeIs('reports.rm-sales*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 {{ request()->routeIs('reports.*') || request()->routeIs('sales-report.*') || request()->routeIs('void-report.*') || request()->routeIs('wastage-report.*') || request()->routeIs('vat-report.*') || request()->routeIs('reports.rm-sales*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="font-medium">Reports</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                <a href="{{ route('sales-report.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('sales-report.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Sales Report</span>
                </a>

                
                <a href="{{ route('vat-report.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('vat-report.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                    </svg>
                    <span>VAT Report</span>
                </a>

                <a href="{{ route('reports.item-sales') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('reports.item-sales*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Item Wise Summary</span>
                </a>

                <a href="{{ route('reports.item-transactions') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('reports.item-transactions*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2a4 4 0 014-4h8m0 0l-3-3m3 3l-3 3M3 7h12m0 0l-3-3m3 3l-3 3" />
                    </svg>
                    <span>Item Transactions</span>
                </a>

                <a href="{{ route('reports.rm-sales') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('reports.rm-sales*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2a4 4 0 014-4h8m0 0l-3-3m3 3l-3 3M3 7h12m0 0l-3-3m3 3l-3 3" />
                    </svg>
                    <span>RM Sales Report</span>
                </a>

                <a href="{{ route('void-report.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('void-report.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <span>Void Report</span>
                </a>

                <a href="{{ route('wastage-report.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('wastage-report.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Wastage Report</span>
                </a>

            </div>
        </div>
        @endhasanyrole

        <!-- Stock Adjustment (Admin Only) -->
        @hasanyrole('admin|superadmin')
        <div x-data="{ open: {{ request()->routeIs('stock-adjustment.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 {{ request()->routeIs('stock-adjustment.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">Stock</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                <a href="{{ route('stock-adjustment.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('stock-adjustment.index') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Stock Adjustment</span>
                </a>

                <a href="{{ route('stock-adjustment.history') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('stock-adjustment.history') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Adjustment History</span>
                </a>
            </div>
        </div>
        @endhasanyrole

        <!-- User Management (Admin Only) -->
        @hasanyrole('admin|superadmin')
        <a href="{{ route('users.index') }}"
            class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('users.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="font-medium">User Management</span>
        </a>

        <a href="{{ route('vat-customers.index') }}"
            class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('vat-customers.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5V4H2v16h5m10 0v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4m10 0H7m3-12h4m-4 4h4" />
            </svg>
            <span class="font-medium leading-tight">VAT Customer Registration</span>
        </a>
        @endhasanyrole

        <!-- Stock Management (Supervisor view - for approving stock requests) -->
        @role('supervisor')
        <div
            x-data="{ open: {{ request()->routeIs('stock.supervisor.*') || request()->routeIs('main-stock.*') || request()->routeIs('stock-transfer.supervisor.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 {{ request()->routeIs('stock.supervisor.*') || request()->routeIs('main-stock.*') || request()->routeIs('stock-transfer.supervisor.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span class="font-medium">Stock</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                <a href="{{ route('main-stock.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('main-stock.index') || request()->routeIs('main-stock.create') || request()->routeIs('main-stock.edit') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Main Stock Items</span>
                </a>

                <a href="{{ route('main-stock.stock-update') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('main-stock.stock-update') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Update Stock</span>
                </a>

                <!-- <a href="{{ route('stock.supervisor.index') }}" class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('stock.supervisor.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Stock Requests</span>
                </a> -->

                <a href="{{ route('stock-transfer.supervisor.index') }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->routeIs('stock-transfer.supervisor.*') ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>Stock Transfers</span>
                </a>
            </div>
        </div>
        @endrole

        <!-- Cashier Stock Section -->
        @role('cashier')
        <!-- <a href="{{ route('stock.cashier.index') }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('stock.cashier.index') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span class="font-medium">Stock Requests</span>
        </a> -->
        <a href="{{ route('stock-transfer.cashier.index') }}"
            class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('stock-transfer.cashier.index') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <span class="font-medium">Incoming Transfers</span>
        </a>
        <a href="{{ route('wastage.index') }}"
            class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('wastage.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span class="font-medium">Wastage</span>
        </a>
        <div x-data="{ open: {{ request()->routeIs('stock-transfer.cashier.sub-stock') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 {{ request()->routeIs('stock-transfer.cashier.sub-stock') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="font-medium">My Stock</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                <a href="{{ route('stock-transfer.cashier.sub-stock', ['type' => 'raw_material']) }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->is('*sub-stock*') && request()->get('type') == 'raw_material' ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>RM Stock</span>
                </a>

                <a href="{{ route('stock-transfer.cashier.sub-stock', ['type' => 'finished_good']) }}"
                    class="flex items-center gap-3 px-4 py-2 {{ request()->is('*sub-stock*') && request()->get('type') == 'finished_good' ? 'bg-white/20 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>FG Stock</span>
                </a>
            </div>
        </div>
        @endrole

        <!-- Settings (Admin Only) -->
        @hasanyrole('admin|superadmin')
        <a href="{{ route('settings.index') }}"
            class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs('settings.*') ? 'bg-white/25 text-white border-l-4 border-white font-semibold' : 'text-white/80 hover:bg-white/15 hover:text-white' }} rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Settings</span>
        </a>
        @endrole
    </nav>
</aside>