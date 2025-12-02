@extends('layouts.app')

@section('title', 'Dashboard - Ravon POS')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">
                Welcome back, {{ Auth::user()->name }}!
            </h1>
            <p class="text-gray-400">Here's what's happening with your restaurant today.</p>
        </div>
        @if(Auth::user()->hasRole('cashier') || Auth::user()->hasRole('admin'))
        <div>
            <a href="{{ route('pos.index') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-800 text-white text-lg font-bold rounded-xl shadow-2xl hover:from-blue-700 hover:to-blue-900 transform hover:scale-105 transition-all duration-200">
                <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Open POS
            </a>
        </div>
        @endif
    </div>

    <!-- Content Removed as per request -->
</div>
@endsection