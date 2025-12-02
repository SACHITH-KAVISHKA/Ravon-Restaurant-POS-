@extends('layouts.app')

@section('title', 'Edit Menu Item')

@section('content')
<div class="min-h-screen bg-gray-900 py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('menu.index') }}" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-white">Edit: {{ $item->name }}</h1>
                    <p class="text-gray-400 mt-1">Update item details and manage portions</p>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 p-4 bg-green-900 border border-green-700 text-green-100 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Item Details -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Item Details</h2>

                    <form action="{{ route('menu.items.update', $item) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <!-- Item Name -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Item Name *</label>
                                <input type="text" name="name" value="{{ old('name', $item->name) }}" required
                                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Category *</label>
                                <select name="category_id" required
                                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Default Price -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Default Price (Rs.)</label>
                                <input type="number" name="price" value="{{ old('price', $item->price) }}" step="0.01" min="0"
                                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500"
                                    {{ $item->modifiers->count() > 0 ? 'disabled' : '' }}>
                                @if($item->modifiers->count() > 0)
                                <p class="text-gray-500 text-sm mt-1">Disabled because this item has portions</p>
                                @endif
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                Update Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column - Portions -->
            <div>
                <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Portions / Sizes</h2>

                    <!-- Add Portion Form -->
                    <form action="{{ route('menu.modifiers.store', $item) }}" method="POST" class="mb-6">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-1">Portion Name *</label>
                                <input type="text" name="name" placeholder="e.g., Large, Small, 500ml" required
                                    class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-1">Price (Rs.) *</label>
                                <input type="number" name="price" step="0.01" min="0" required
                                    class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500 text-sm">
                                <p class="text-xs text-gray-500 mt-1">Independent price for this portion</p>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold text-sm">
                                Add Portion
                            </button>
                        </div>
                    </form>

                    <!-- Existing Portions List -->
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-400 mb-2">Existing Portions</h3>
                        @forelse($item->modifiers as $modifier)
                        <div class="bg-gray-700 rounded-lg p-3 border border-gray-600">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-semibold text-white text-sm">{{ $modifier->name }}</h4>
                                </div>
                                <span class="text-sm font-bold text-green-400">
                                    Rs. {{ number_format($modifier->price_adjustment, 2) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs text-green-400">Active</span>

                                <form action="{{ route('menu.modifiers.destroy', $modifier) }}" method="POST" onsubmit="return confirm('Delete this portion?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-xs">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-500 text-sm text-center py-4">No portions added yet</p>
                        <p class="text-gray-400 text-xs text-center">Add portions if this item comes in different sizes</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection