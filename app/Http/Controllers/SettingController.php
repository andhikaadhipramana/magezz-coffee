<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'outletName' => Setting::get('outlet_name', 'Magezz Coffee'),
            'outletAddress' => Setting::get('outlet_address', ''),
            'outletPhone' => Setting::get('outlet_phone', ''),
            'receiptFooter' => Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda'),
            'taxEnabled' => Setting::taxEnabled(),
            'taxRate' => Setting::taxRate(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'outlet_name' => ['required', 'string', 'max:100'],
            'outlet_address' => ['nullable', 'string', 'max:255'],
            'outlet_phone' => ['nullable', 'string', 'max:30'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_enabled' => ['nullable', 'boolean'],
        ]);

        Setting::set('outlet_name', $data['outlet_name']);
        Setting::set('outlet_address', $data['outlet_address'] ?? '');
        Setting::set('outlet_phone', $data['outlet_phone'] ?? '');
        Setting::set('receipt_footer', $data['receipt_footer'] ?? '');
        Setting::set('tax_rate', $data['tax_rate']);
        Setting::set('tax_enabled', $request->boolean('tax_enabled') ? '1' : '0');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
