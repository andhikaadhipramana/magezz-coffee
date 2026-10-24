@extends("layouts.app")

@section("title", "Kasir (POS)")
@section("subtitle", "Buat pesanan dan proses pembayaran")

@section("content")
    <div class="mz-pos-page">
        {{-- Shift Alert --}}
        @if(! $activeShift)
            <div class="alert alert-warning mz-pos-shift-alert d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Anda belum membuka shift.</strong> Transaksi tetap bisa diproses, namun tidak akan tercatat
                    pada laporan shift. Disarankan membuka shift terlebih dahulu.
                </div>
                <a href="{{ route("shifts.index") }}" class="btn btn-sm btn-dark">Buka Shift</a>
            </div>
        @else
            <div class="alert alert-success mz-pos-shift-alert d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                <div class="small">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Shift aktif sejak <strong>{{ $activeShift->started_at->format("H:i") }}</strong>
                    &middot; Kas awal {{ rupiah($activeShift->opening_cash) }}
                </div>
                <a href="{{ route("shifts.index") }}" class="btn btn-sm btn-outline-dark">Kelola Shift</a>
            </div>
        @endif

        {{-- Paying Order Banner --}}
        @if($payingOrder)
            <div class="mz-pos-paying-banner d-flex justify-content-between align-items-center py-2 gap-2 flex-wrap">
                <div>
                    <div class="mz-pos-paying-banner__title">
                        <i class="bi bi-receipt me-1"></i>
                        <strong>Membayar pesanan {{ $payingOrder->code }}</strong>
                        @if($payingOrder->table_number)
                            &middot; Meja {{ $payingOrder->table_number }}
                        @endif
                        @if($payingOrder->customer_name)
                            &middot; {{ $payingOrder->customer_name }}
                        @endif
                    </div>
                    <div class="mz-pos-paying-banner__meta">
                        @if($payingOrder->table_number)
                            <i class="bi bi-table me-1"></i> Meja {{ $payingOrder->table_number }}
                        @endif
                        @if($payingOrder->customer_name)
                            @if($payingOrder->table_number) &middot; @endif
                            <i class="bi bi-person me-1"></i> {{ $payingOrder->customer_name }}
                        @endif
                    </div>
                    <small class="mz-pos-paying-banner__note">Setelah pembayaran sukses, pesanan otomatis menjadi <strong>Diproses</strong>.</small>
                </div>
                <form method="POST" action="{{ route("orders.pay-abort") }}"
                      data-mz-confirm="Batalkan muatan pembayaran pesanan {{ $payingOrder->code }}? Keranjang akan dikosongkan.">
                    @csrf
                    <button class="btn btn-sm btn-outline-dark">Batal</button>
                </form>
            </div>
        @endif

        {{-- Main Layout --}}
        <div class="row g-3 mz-pos-layout">
            {{-- Daftar Menu --}}
            <div class="col-lg-7">
                {{-- Filter/Search Bar --}}
                <div class="mz-pos-filter">
                    <form method="GET" action="{{ route("pos.index") }}" class="row g-2">
                        <div class="col-md-5 col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Cari nama menu..." value="{{ request("search") }}">
                            </div>
                        </div>
                        <div class="col-md-5 col-lg-4">
                            <select name="category_id" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(request("category_id") == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-brand" title="Terapkan filter">
                                <i class="bi bi-funnel"></i><span class="d-md-none ms-2">Terapkan Filter</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Menu Grid --}}
                <div class="row g-2">
                    @forelse($menus as $menu)
                        @php
                            $catName = \Illuminate\Support\Str::lower($menu->category?->name ?? "");
                            $posImage = $menu->image_url;
                            $posIcon = match (true) {
                                str_contains($catName, "non-kopi") => "bi-cup-straw",
                                str_contains($catName, "kopi") => "bi-cup-hot-fill",
                                str_contains($catName, "teh") => "bi-cup-straw",
                                str_contains($catName, "makan"), str_contains($catName, "roti"),
                                str_contains($catName, "snack"), str_contains($catName, "pastry") => "bi-egg-fried",
                                str_contains($catName, "dessert"), str_contains($catName, "kue") => "bi-cake2",
                                default => "bi-cup",
                            };
                        @endphp
                        <div class="col-sm-6 col-xl-4">
                            <div class="mz-pos-tile" data-bs-toggle="modal" data-bs-target="#menuModal{{ $menu->id }}" role="button" tabindex="0" aria-label="Tambah {{ $menu->name }} ke keranjang">
                                <div class="mz-pos-tile__top">
                                    <span class="mz-pos-tile__icon {{ $posImage ? "has-image" : "" }}">
                                        @if($posImage)
                                            <img src="{{ $posImage }}"
                                                 @if($menu->image_srcset)
                                                     srcset="{{ $menu->image_srcset }}"
                                                     sizes="64px"
                                                 @endif
                                                 alt="Foto {{ $menu->name }}" loading="lazy" decoding="async" data-mz-img>
                                        @else
                                            <i class="bi {{ $posIcon }}"></i>
                                        @endif
                                    </span>
                                    <span class="mz-pos-tile__code">{{ $menu->code }}</span>
                                </div>
                                <div class="mz-pos-tile__name">{{ $menu->name }}</div>
                                <div class="mz-pos-tile__cat">
                                    {{ $menu->category?->name }}
                                    @if($menu->is_signature)
                                        <span class="mz-badge mz-badge--caramel ms-1">Signature</span>
                                    @endif
                                </div>
                                <div class="mz-pos-tile__price">{{ rupiah($menu->price) }}</div>
                            </div>
                        </div>

                        {{-- Modal Varian --}}
                        <div class="modal fade" id="menuModal{{ $menu->id }}" tabindex="-1" aria-labelledby="menuModalLabel{{ $menu->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="{{ route("pos.add") }}" class="modal-content">
                                    @csrf
                                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                                    <div class="modal-header">
                                        <div>
                                            <h6 class="modal-title mb-0" id="menuModalLabel{{ $menu->id }}">{{ $menu->name }}</h6>
                                            <small class="text-muted">{{ rupiah($menu->price) }}</small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label">Suhu</label>
                                                <select name="temperature" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    <option value="Hot">Hot</option>
                                                    <option value="Iced">Iced (+Rp 2.000)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Ukuran</label>
                                                <select name="size" class="form-select form-select-sm">
                                                    <option value="">Regular</option>
                                                    <option value="Large">Large (+Rp 5.000)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Level Gula</label>
                                                <select name="sugar" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    <option value="0">0%</option>
                                                    <option value="25">25%</option>
                                                    <option value="50">50%</option>
                                                    <option value="75">75%</option>
                                                    <option value="100">100%</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Susu Alternatif</label>
                                                <select name="milk" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    <option value="Oat Milk">Oat Milk (+Rp 10.000)</option>
                                                    <option value="Almond Milk">Almond Milk (+Rp 10.000)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Topping</label>
                                                <select name="topping" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    <option value="Whipped Cream">Whipped Cream (+Rp 5.000)</option>
                                                    <option value="Extra Keju">Extra Keju (+Rp 5.000)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Extra Shot</label>
                                                <div class="form-check mt-1">
                                                    <input type="checkbox" name="extra_shot" value="1" class="form-check-input" id="shot{{ $menu->id }}">
                                                    <label class="form-check-label" for="shot{{ $menu->id }}">+Rp 8.000</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Jumlah</label>
                                                <input type="number" name="quantity" class="form-control form-control-sm" value="1" min="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-brand">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah ke Keranjang
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="mz-empty">
                                <i class="bi bi-search"></i>
                                <strong>Tidak ada menu yang cocok</strong>
                                <p>Tidak ada menu yang cocok dengan pencarian.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Keranjang --}}
            <div class="col-lg-5">
                <div class="cart-panel mz-cart-panel" id="mzPosCart">
                    <div class="card">
                        <div class="card-header">
                            <span><i class="bi bi-cart3 me-1"></i> Keranjang</span>
                            @if(! empty($cart))
                                <form method="POST" action="{{ route("pos.clear") }}">
                                    @csrf
                                    <button class="mz-remove" type="submit">
                                        <i class="bi bi-trash3"></i> Kosongkan
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="cart-scroll mz-cart-scroll">
                            @if(empty($cart))
                                <div class="mz-empty">
                                    <i class="bi bi-inbox"></i>
                                    <strong>Keranjang masih kosong</strong>
                                    <p>Klik menu untuk menambah pesanan.</p>
                                </div>
                            @else
                                @foreach($cart as $key => $item)
                                    <div class="mz-pos-line">
                                        <div class="mz-pos-line__main">
                                            <div class="mz-pos-line__name">{{ $item["menu_name"] }}</div>
                                            @if($item["variant_info"])
                                                <div class="mz-pos-line__meta">{{ $item["variant_info"] }}</div>
                                            @endif
                                            <div class="mz-pos-line__meta">{{ rupiah($item["price"]) }} / porsi</div>

                                            <form method="POST" action="{{ route("pos.update") }}" class="mz-pos-qty">
                                                @csrf
                                                <input type="hidden" name="key" value="{{ $key }}">
                                                <button type="button" class="mz-pos-qty__btn mz-pos-qty__minus" data-key="{{ $key }}" aria-label="Kurangi jumlah">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <input type="number" name="quantity" class="mz-pos-qty__input" value="{{ $item["quantity"] }}" min="0" inputmode="numeric" aria-label="Jumlah">
                                                <button type="button" class="mz-pos-qty__btn mz-pos-qty__plus" data-key="{{ $key }}" aria-label="Tambah jumlah">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                                <button type="submit" class="mz-pos-qty__save" aria-label="Simpan jumlah" title="Simpan jumlah">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                                <span class="mz-pos-line__total">{{ rupiah($item["subtotal"]) }}</span>
                                            </form>
                                        </div>

                                        <form method="POST" action="{{ route("pos.remove") }}">
                                            @csrf
                                            <input type="hidden" name="key" value="{{ $key }}">
                                            <button class="mz-pos-remove" type="submit" aria-label="Hapus {{ $item["menu_name"] }}"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div class="card-body border-top">
                            <form method="POST" action="{{ route("pos.checkout") }}" id="checkoutForm">
                                @csrf
                                {{-- Diskon --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Diskon</label>
                                        <select name="discount_type" class="form-select form-select-sm" id="discountType">
                                            <option value="none">Tanpa Diskon</option>
                                            <option value="percent">Persen (%)</option>
                                            <option value="amount">Nominal (Rp)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Nilai Diskon</label>
                                        <input type="number" name="discount_value" id="discountValue"
                                               class="form-control form-control-sm" value="0" min="0" step="any">
                                    </div>
                                </div>

                                {{-- Totals --}}
                                <table class="mz-pos-totals mb-2">
                                    <tr>
                                        <td>Subtotal</td>
                                        <td id="rowSubtotal">{{ rupiah($totals["subtotal"]) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Diskon</td>
                                        <td class="text-danger" id="rowDiscount">- {{ rupiah($totals["discount_amount"]) }}</td>
                                    </tr>
                                    @if($taxEnabled)
                                        <tr>
                                            <td>Pajak PB1 ({{ rtrim(rtrim(number_format($taxRate, 2, ",", "."), "0"), ",") }}%)</td>
                                            <td id="rowTax">{{ rupiah($totals["tax_amount"]) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="mz-pos-totals__grand">
                                        <th>TOTAL</th>
                                        <th id="rowTotal">{{ rupiah($totals["total"]) }}</th>
                                    </tr>
                                </table>

                                {{-- Payment Method (Segmented) --}}
                                <div class="mb-2">
                                    <label class="form-label">Metode Pembayaran</label>
                                    <div class="mz-pos-payment" role="group" aria-label="Metode pembayaran">
                                        <div class="mz-pos-payment__btn">
                                            <input type="radio" name="payment_method" id="payCash" value="cash" checked>
                                            <label for="payCash"><i class="bi bi-cash"></i> Tunai</label>
                                        </div>
                                        <div class="mz-pos-payment__btn">
                                            <input type="radio" name="payment_method" id="payQris" value="qris">
                                            <label for="payQris"><i class="bi bi-qr-code"></i> QRIS</label>
                                        </div>
                                        <div class="mz-pos-payment__btn">
                                            <input type="radio" name="payment_method" id="payCard" value="card">
                                            <label for="payCard"><i class="bi bi-credit-card"></i> Kartu</label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Cash Input Row --}}
                                <div class="mz-pos-cash-row" id="cashRow">
                                    <label class="form-label">Uang Diterima</label>
                                    <input type="number" name="paid_amount" id="paidAmount"
                                           class="form-control" value="{{ (int) $totals["total"] }}" min="0" autocomplete="off">
                                    <small class="text-muted mt-2 d-block">Kembalian: <span id="changePreview" class="mz-pos-change-preview">Rp 0</span></small>
                                </div>

                                <button type="submit" class="mz-pos-checkout-btn" @disabled(empty($cart))>
                                    <i class="bi bi-check2-circle me-1"></i> Proses Pembayaran
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bar total khusus mobile: menempel di bawah, aman dari safe-area iPhone --}}
    <div class="mz-pos-mobilebar">
        <div class="mz-pos-mobilebar__total">
            <small>Total</small>
            <strong id="mzMobileTotal">{{ rupiah($totals["total"]) }}</strong>
        </div>
        <button type="button" class="mz-btn mz-btn--primary" data-mz-pos-scroll>
            <i class="bi bi-basket"></i> Keranjang ({{ array_sum(array_column($cart, "quantity")) }})
        </button>
    </div>
@endsection

@push("scripts")
<script>
    const taxEnabled = {{ $taxEnabled ? "true" : "false" }};
    const taxRate = {{ (float) $taxRate }};
    const subtotal = {{ (float) $totals["subtotal"] }};

    const discountType = document.getElementById("discountType");
    const discountValue = document.getElementById("discountValue");
    const paidAmount = document.getElementById("paidAmount");

    function formatRupiah(n) {
        return "Rp " + Math.round(n).toLocaleString("id-ID");
    }

    function recalc() {
        let discount = 0;
        const value = parseFloat(discountValue.value) || 0;

        if (discountType.value === "percent") {
            discount = subtotal * Math.min(value, 100) / 100;
        } else if (discountType.value === "amount") {
            discount = Math.min(value, subtotal);
        }

        const afterDiscount = Math.max(subtotal - discount, 0);
        const tax = taxEnabled ? afterDiscount * taxRate / 100 : 0;
        const total = afterDiscount + tax;

        document.getElementById("rowSubtotal").textContent = formatRupiah(subtotal);
        document.getElementById("rowDiscount").textContent = "- " + formatRupiah(discount);
        const taxRow = document.getElementById("rowTax");
        if (taxRow) taxRow.textContent = formatRupiah(tax);
        document.getElementById("rowTotal").textContent = formatRupiah(total);

        updateChange(total);
        return total;
    }

    function updateChange(total) {
        const paid = parseFloat(paidAmount.value) || 0;
        const change = Math.max(paid - total, 0);
        document.getElementById("changePreview").textContent = formatRupiah(change);
    }

    discountType.addEventListener("change", recalc);
    discountValue.addEventListener("input", recalc);
    paidAmount.addEventListener("input", () => updateChange(recalc()));

    // Payment method toggle
    document.querySelectorAll("input[name=\"payment_method\"]").forEach((radio) => {
        radio.addEventListener("change", () => {
            const isCash = document.getElementById("payCash").checked;
            document.getElementById("cashRow").style.display = isCash ? "block" : "none";
        });
    });

    // Stepper quantity controls
    document.addEventListener("click", (e) => {
        const minusBtn = e.target.closest(".mz-pos-qty__minus");
        const plusBtn = e.target.closest(".mz-pos-qty__plus");

        if (minusBtn || plusBtn) {
            const btn = minusBtn || plusBtn;
            const key = btn.dataset.key;
            const input = btn.closest(".mz-pos-qty").querySelector(".mz-pos-qty__input");
            const form = btn.closest("form");
            let qty = parseInt(input.value) || 0;

            if (minusBtn) {
                qty = Math.max(0, qty - 1);
            } else {
                qty += 1;
            }

            input.value = qty;

            // Auto-submit the form after a short delay
            clearTimeout(input._updateTimer);
            input._updateTimer = setTimeout(() => {
                form.submit();
            }, 300);
        }
    });

    // Also handle Enter key on quantity input
    document.addEventListener("keydown", (e) => {
        if (e.target.classList.contains("mz-pos-qty__input") && e.key === "Enter") {
            e.target.closest("form").submit();
        }
    });

    recalc();

    // Bar total mobile: cermin dari #rowTotal + tombol gulir ke keranjang
    (function () {
        const rowTotal = document.getElementById("rowTotal");
        const mobileTotal = document.getElementById("mzMobileTotal");
        const jump = document.querySelector("[data-mz-pos-scroll]");
        const cart = document.getElementById("mzPosCart");

        if (rowTotal && mobileTotal) {
            const syncTotal = () => { mobileTotal.textContent = rowTotal.textContent.trim(); };
            new MutationObserver(syncTotal).observe(rowTotal, {
                childList: true,
                characterData: true,
                subtree: true,
            });
            syncTotal();
        }

        if (jump && cart) {
            jump.addEventListener("click", () => {
                cart.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        }
    })();
</script>
@endpush
