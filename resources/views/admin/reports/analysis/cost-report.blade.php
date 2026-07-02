@extends('layouts.app')

@section('title', 'Cost Report - Ravon Restaurant POS')

@push('styles')
    <style>
        .sticky-header thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .recipe-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .recipe-yes {
            background: #dcfce7;
            color: #166534;
        }

        .recipe-no {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
@endpush

@section('content')
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                        Cost Report
                    </h1>
                    <div class="flex items-center text-sm text-gray-500 mt-1">
                        <span>Analysis</span>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-purple-600 font-medium">Cost Report</span>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total POS Items -->
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-md p-5 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(135deg, #f3e8ff, #e9d5ff);">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total POS Items</p>
                                <p class="text-2xl font-bold text-gray-800">{{ number_format($summary->total) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items With Recipe -->
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-md p-5 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Items With Recipe</p>
                                <p class="text-2xl font-bold text-green-600">{{ number_format($summary->with_recipe) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Without Recipe -->
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-md p-5 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Items Without Recipe</p>
                                <p class="text-2xl font-bold text-red-500">{{ number_format($summary->without_recipe) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Average Profit % -->
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-md p-5 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(135deg, #dbeafe, #bfdbfe);">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Average Profit %</p>
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ number_format($summary->avg_profit_pct, 2) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
                    <form method="GET" action="{{ route('analysis.cost-report') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                            <!-- POS Item -->
                            <div class="lg:col-span-2">
                                <label for="item_id" class="block text-sm font-medium text-gray-700 mb-1">POS
                                    Item</label>
                                <select id="item_id" name="item_id"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">All Items</option>
                                    @foreach ($allItems as $item)
                                        <option value="{{ $item->id }}" {{ $itemId == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Recipe Available -->
                            <div>
                                <label for="has_recipe" class="block text-sm font-medium text-gray-700 mb-1">Recipe
                                    Available</label>
                                <select id="has_recipe" name="has_recipe"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="all" {{ $hasRecipe === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="yes" {{ $hasRecipe === 'yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="no" {{ $hasRecipe === 'no' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <!-- Sort By -->
                            <div>
                                <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort
                                    By</label>
                                <select id="sort_by" name="sort_by"
                                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="alpha" {{ $sortBy === 'alpha' ? 'selected' : '' }}>Alphabetical
                                        (A-Z)</option>
                                    <option value="profit_high" {{ $sortBy === 'profit_high' ? 'selected' : '' }}>
                                        Profit % (High → Low)</option>
                                    <option value="profit_low" {{ $sortBy === 'profit_low' ? 'selected' : '' }}>Profit (Low
                                        → High)</option>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:col-span-2">
                                <button type="submit"
                                    class="w-full min-w-0 whitespace-nowrap bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    Generate
                                </button>
                                <a href="{{ route('analysis.cost-report.export', request()->query()) }}"
                                    class="w-full min-w-0 whitespace-nowrap bg-gradient-to-r from-[#28a745] to-[#20c997] hover:shadow-lg hover:shadow-green-500/50 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Excel
                                </a>
                                <a href="{{ route('analysis.cost-report') }}"
                                    class="w-full min-w-0 whitespace-nowrap bg-white hover:bg-gray-100 text-gray-700 font-semibold py-2 px-4 rounded-lg transition border border-gray-300 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results Table Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden">
                    <div class="table-responsive">
                        <table class="w-full sticky-header">
                            <thead class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        #</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Menu Item</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Portion</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">
                                        Selling Price</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">
                                        Recipe Cost</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">
                                        Profit</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-white uppercase tracking-wider">
                                        Profit %</th>
                                    <th
                                        class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                        Recipe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($paginatedRows as $index => $row)
                                    <tr
                                        class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-purple-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                                            {{ ($paginatedRows->currentPage() - 1) * $paginatedRows->perPage() + $index + 1 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-gray-800 font-medium">{{ $row->menu_item }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($row->portion !== '-')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ $row->portion }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-800">
                                            {{ number_format($row->selling_price, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-800">
                                            {{ number_format($row->recipe_cost, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold {{ $row->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($row->profit, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold {{ $row->profit_pct >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($row->profit_pct, 2) }}%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($row->has_recipe)
                                                <span class="recipe-badge recipe-yes">YES</span>
                                            @else
                                                <span class="recipe-badge recipe-no">NO</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-gray-600 text-lg">No POS items found.</p>
                                                <p class="text-gray-500 text-sm mt-1">Try adjusting your filter
                                                    criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($paginatedRows->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Showing {{ $paginatedRows->firstItem() }} to {{ $paginatedRows->lastItem() }}
                                    of {{ $paginatedRows->total() }} items
                                </div>
                                <div>
                                    {{ $paginatedRows->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
