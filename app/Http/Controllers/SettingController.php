<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = Setting::first();
        return view('settings.index', compact('setting'));
    }

    /**
     * Store a newly created resource in storage or update the existing one.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vat' => 'nullable|numeric|min:0',
            'sscl' => 'nullable|numeric|min:0',
            'vat_reg_no' => 'nullable|string|max:50',
        ]);

        $setting = Setting::first();

        if ($setting) {
            $setting->update($validated);
            $message = 'Settings updated successfully!';
        } else {
            Setting::create($validated);
            $message = 'Settings created successfully!';
        }

        return redirect()->route('settings.index')->with('success', $message);
    }
}
