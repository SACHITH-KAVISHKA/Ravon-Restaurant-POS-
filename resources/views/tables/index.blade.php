@extends('layouts.app')

@section('title', 'Tables - Ravon POS')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">Table Management</h1>
        <p class="text-gray-400">Select a table to start taking orders</p>
    </div>

    <!-- Table Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
        @forelse($tables as $table)
        <a href="{{ route('tables.show', $table->id) }}" 
           class="block transform hover:scale-105 transition-all duration-200">
            <div class="relative rounded-xl shadow-xl overflow-hidden border-2 
                {{ $table->status === 'available' ? 'bg-gray-900 border-gray-700 hover:border-blue-500' : '' }}
                {{ $table->status === 'ordered' ? 'bg-blue-900/50 border-blue-600' : '' }}
                {{ $table->status === 'serving' ? 'bg-yellow-900/50 border-yellow-600' : '' }}
                {{ $table->status === 'bill_requested' ? 'bg-orange-900/50 border-orange-600' : '' }}
                {{ $table->status === 'reserved' ? 'bg-purple-900/50 border-purple-600' : '' }}">
                
                <!-- Status Badge -->
                <div class="absolute top-2 right-2">
                    <span class="px-2 py-1 text-xs font-bold rounded-full
                        {{ $table->status === 'available' ? 'bg-green-500/20 text-green-400' : '' }}
                        {{ $table->status === 'ordered' ? 'bg-blue-500/20 text-blue-400' : '' }}
                        {{ $table->status === 'serving' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                        {{ $table->status === 'bill_requested' ? 'bg-orange-500/20 text-orange-400' : '' }}
                        {{ $table->status === 'reserved' ? 'bg-purple-500/20 text-purple-400' : '' }}">
                        {{ ucfirst($table->status) }}
                    </span>
                </div>

                <!-- Table Icon & Number -->
                <div class="p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-3 {{ $table->status === 'available' ? 'text-gray-600' : 'text-blue-400' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    
                    <h3 class="text-2xl font-bold text-white mb-1">{{ $table->table_number }}</h3>
                    <p class="text-sm text-gray-400">{{ $table->floor->name }}</p>
                    <p class="text-xs text-gray-500">Capacity: {{ $table->capacity }}</p>
                    
                    @if($table->currentOrder)
                    <div class="mt-3 pt-3 border-t border-gray-700">
                        <p class="text-sm font-semibold text-blue-400">
                            {{ $table->currentOrder->order_number }}
                        </p>
                        <p class="text-lg font-bold text-white">
                            Rs. {{ number_format($table->currentOrder->total_amount, 2) }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="col-span-full text-center py-12">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500">No tables available</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
