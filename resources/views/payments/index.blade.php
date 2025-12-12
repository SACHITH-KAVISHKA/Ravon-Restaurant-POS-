@extends('layouts.app')

@section('title', 'Payments - Ravon POS')

@section('content')
<div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">Payment Processing</h1>
            <p class="text-gray-800-muted">Process payments for pending orders</p>
        </div>

        <!-- Orders List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($orders as $order)
            <div class="bg-white border border-gray-200 rounded-xl shadow-md hover:border-purple-600/50 transition">
                <div class="p-6">
                    <!-- Order Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">{{ $order->order_number }}</h3>
                            @if($order->table)
                            <p class="text-sm text-gray-800-muted">Table {{ $order->table->table_number }}</p>
                            @endif
                        </div>
                        <span class="px-3 py-1 text-xs font-bold rounded-full 
                            {{ $order->status === 'pending' ? 'bg-ravon-warning/20 text-ravon-warning' : 'bg-blue-500/20 text-blue-400' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>

                    <!-- Order Items -->
                    <div class="mb-4 max-h-40 overflow-y-auto">
                        @foreach($order->items as $item)
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-800-muted">{{ $item->quantity }}x {{ $item->item->name }}</span>
                            <span class="text-gray-800">Rs. {{ number_format($item->subtotal, 2) }}</span>
                        </div>
                        @endforeach
                    </div>

                    <!-- Order Total -->
                    <div class="pt-4 border-t border-gray-300 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-800-muted font-semibold">Total Amount:</span>
                            <span class="text-2xl font-bold text-gray-800">Rs. {{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>

                    <!-- Order Info -->
                    <div class="text-xs text-gray-800-muted mb-4">
                        <p>Waiter: {{ $order->waiter->name }}</p>
                        <p>Time: {{ $order->created_at->format('h:i A') }}</p>
                    </div>

                    <!-- Action Button -->
                    <a href="{{ route('payments.show', $order->id) }}"
                        class="block w-full bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white text-center py-3 rounded-lg font-bold hover:shadow-lg hover:shadow-purple-500/50 transition">
                        Process Payment
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-20 h-20 mx-auto mb-4 text-purple-600/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-gray-800-muted">No pending orders for payment</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection


