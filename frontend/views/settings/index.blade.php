@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('subtitle', 'Konfigurasi outlet, pajak, dan struk')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-shop me-1"></i> Informasi Outlet
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Outlet</label>
                            <input type="text" name="outlet_name" class="form-control form-control-sm"
                                   value="{{ old('outlet_name', $outletName) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alamat</label>
                            <textarea name="outlet_address" class="form-control form-control-sm" rows="2">{{ old('outlet_address', $outletAddress) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor Telepon</label>
                            <input type="text" name="outlet_phone" class="form-control form-control-sm"
                                   value="{{ old('outlet_phone', $outletPhone) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Footer Struk</label>
                            <input type="text" name="receipt_footer" class="form-control form-control-sm"
                                   value="{{ old('receipt_footer', $receiptFooter) }}">
                            <small class="text-muted">Teks yang tercetak di bagian bawah struk.</small>
                        </div>

                        <hr>

                        <h6 class="fw-semibold mb-3"><i class="bi bi-percent me-1"></i> Pajak</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tarif Pajak PB1 (%)</label>
                                <input type="number" name="tax_rate" step="0.01" min="0" max="100"
                                       class="form-control form-control-sm"
                                       value="{{ old('tax_rate', (float) $taxRate) }}" required>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="tax_enabled" value="1" class="form-check-input"
                                           id="taxEnabled" @checked($taxEnabled)>
                                    <label class="form-check-label small" for="taxEnabled">
                                        Aktifkan pajak pada transaksi
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-brand btn-sm">
                                <i class="bi bi-save me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-info-circle me-1"></i> Informasi Sistem
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Aplikasi</td><td class="text-end fw-semibold">Magezz Coffee POS</td></tr>
                        <tr><td class="text-muted">Framework</td><td class="text-end">Laravel {{ app()->version() }}</td></tr>
                        <tr><td class="text-muted">PHP</td><td class="text-end">{{ PHP_VERSION }}</td></tr>
                        <tr><td class="text-muted">Database</td><td class="text-end">{{ config('database.default') }}</td></tr>
                        <tr><td class="text-muted">Zona Waktu</td><td class="text-end">{{ config('app.timezone') }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-lightbulb me-1"></i> Catatan
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2">
                        Pajak default menggunakan <strong>PB1 10%</strong> sesuai PRD. Ubah tarif bila diperlukan.
                    </p>
                    <p class="mb-0">
                        Perubahan nama outlet dan footer struk akan langsung diterapkan pada struk berikutnya.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
