@extends('layouts.app')

@section('title', 'Extraction — Spectra Watermarking')

@section('footer_code', '03 · EXTRACTION')

@section('content')
<section class="pagehead">
  <div class="wrap">
    <div class="breadcrumb"><a href="{{ route('index') }}">Beranda</a> / Extraction</div>
    <h1>Ekstraksi watermark secara blind</h1>
    <p class="lead">Sistem hanya membutuhkan citra dan secret key — tanpa citra asli — untuk mencoba memulihkan kembali watermark yang tersisip.</p>
    <div class="stepnav">
      <a href="{{ route('embedding.index') }}">01 Embedding</a><span class="arrow">→</span>
      <a href="{{ route('attack.index') }}">02 Attack</a><span class="arrow">→</span>
      <a class="here">03 Extraction</a><span class="arrow">→</span>
      <a href="{{ route('evaluation.index') }}">04 Evaluation</a>
    </div>
  </div>
</section>

<section style="padding-top:56px">
  <div class="wrap">
    <div class="wsbox">
      <div class="wshead"><span><span class="dot on"></span>WORKSPACE.EXTRACT</span><span id="vStatus">{{ ($run && !empty($run['extracted_image'])) ? 'Selesai' : (($run && !empty($run['attacked_image'])) ? 'Citra siap diekstraksi' : 'Menunggu citra') }}</span></div>
      
      <form method="POST" action="{{ route('extraction.run') }}" enctype="multipart/form-data" id="extractionForm">
        @csrf
        <div class="wsgrid">
          <div class="wscol">

            <!-- Tab: Dari Server / Upload Sendiri -->
            <div style="border:1px solid var(--line);background:#fff;margin-bottom:16px">
              <div style="padding:16px 20px;border-bottom:1px solid var(--line)">
                <div style="display:flex;gap:16px;flex-wrap:wrap">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                    <input type="radio" name="source_type" value="server" id="sourceServer" checked onchange="toggleSource()">
                    <span>Dari Server (hasil Attack)</span>
                  </label>
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                    <input type="radio" name="source_type" value="upload" id="sourceUpload" onchange="toggleSource()">
                    <span>Upload Sendiri</span>
                  </label>
                </div>
              </div>

<<<<<<< HEAD
              <!-- Panel: Dari Server -->
              <div id="panelServer" style="padding:16px 20px">
                @if (count($serverImages) > 0)
                  <!-- Preview gambar attacked dari attack -->
                  <div style="margin-bottom:16px;padding:16px;border:1px solid var(--line);border-radius:6px;background:#f8f9fa">
                    <div style="font-size:13px;font-weight:500;margin-bottom:8px">Preview Citra Attacked (Hasil Attack)</div>
                    <div style="text-align:center">
                      <img src="{{ route('artifacts.show', ['kind' => 'attacked']) }}?path={{ urlencode($serverImages[0]['path']) }}" alt="Citra attacked" style="max-width:100%;max-height:200px;border:1px solid var(--line);border-radius:4px;background:#fff;">
                    </div>
                    <div style="margin-top:8px;font-size:12px;color:var(--slate)">
                      Attack: {{ str_replace('_', ' ', $run['attack_type'] ?? 'none') }} · Parameter: {{ $run['parameter'] ?? '—' }}
                    </div>
                  </div>
                  
                  <div style="display:flex;flex-direction:column;gap:8px">
                    @foreach ($serverImages as $image)
                      <label style="display:flex;align-items:center;gap:12px;padding:10px;border:1px solid var(--line);border-radius:6px;cursor:pointer">
                        <input type="checkbox" name="server_images[]" value="{{ $image['path'] }}" checked>
                        <div>
                          <div style="font-weight:500;font-size:13px">{{ $image['name'] }}</div>
                          <div style="font-size:11px;color:var(--slate)">{{ $image['path'] }}</div>
                        </div>
                      </label>
                    @endforeach
                  </div>
                @else
                  <div style="border:1px dashed var(--line);padding:20px;text-align:center;font-size:13px;color:#8A93A2">
                    Belum ada hasil dari halaman Attack di browser ini. <a href="{{ route('attack.index') }}">Jalankan attack dulu</a>.
                  </div>
                @endif
              </div>

              <!-- Panel: Upload Sendiri -->
              <div id="panelUpload" style="padding:16px 20px;display:none">
                <div style="border:2px dashed var(--line);border-radius:8px;padding:24px;text-align:center;margin-bottom:12px" id="dropZoneExtract">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" style="width:40px;height:40px;margin:0 auto 8px;display:block;color:var(--slate)"><path d="M12 16V4M12 4l-4 4M12 4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                  <p style="margin:0;font-size:13px">Tarik &amp; letakkan gambar attacked, atau klik untuk memilih</p>
                  <p style="margin:4px 0 0;font-size:11px;color:var(--slate)">PNG / JPG, maksimal 5 MB per file</p>
                  <input type="file" name="uploaded_images[]" id="fileInputExtract" accept="image/png,image/jpeg" multiple style="display:none">
                </div>
                <div id="uploadListExtract" style="display:flex;flex-direction:column;gap:8px"></div>
                
                @if (count($uploadedImages) > 0)
                  <div style="margin-top:12px">
                    <div style="font-size:12px;font-weight:500;margin-bottom:6px">File upload tersimpan:</div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                      @foreach ($uploadedImages as $img)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;border:1px solid var(--line);border-radius:4px">
                          <div>
                            <div style="font-size:12px">{{ $img['name'] }}</div>
                            <div style="font-size:10px;color:var(--slate)">{{ number_format($img['size'] / 1024, 1) }} KB</div>
                          </div>
                          <form method="POST" action="{{ route('extraction.delete_upload') }}" style="display:inline">
                            @csrf
                            <input type="hidden" name="path" value="{{ $img['path'] }}">
                            <button type="submit" style="background:none;border:none;color:#c0392b;cursor:pointer;font-size:11px">Hapus</button>
                          </form>
                        </div>
                      @endforeach
                    </div>
                  </div>
                @endif
              </div>
            </div>

            <!-- Upload watermark asli (opsional) untuk NC/BER -->
            <div style="border:1px solid var(--line);background:#fff;margin-bottom:16px">
              <div style="padding:16px 20px;border-bottom:1px solid var(--line)">
                <div style="font-size:13px;font-weight:500">Watermark Asli (Opsional)</div>
                <div style="font-size:11px;color:var(--slate);margin-top:4px">Upload untuk perhitungan NC/BER. Jika tidak diupload, hanya extracted watermark yang ditampilkan.</div>
              </div>
              <div style="padding:16px 20px">
                <input type="file" name="watermark_image" accept="image/png,image/jpeg" style="font-size:13px">
              </div>
            </div>

=======
          <form method="POST" action="{{ route('extraction.run') }}">
            @csrf
            <!-- 4. Secret Key dengan ikon mata toggle password & Tooltip -->
>>>>>>> cf5176e630dac2fe3bb7fb192a2cd73989a65c5a
            <div class="field" style="margin-top:16px">
              <div class="label-with-tooltip">
                <label for="vKey" style="margin-bottom:0">Secret Key</label>
                <div class="tooltip-trigger" tabindex="0" aria-label="Penjelasan Secret Key">?
                  <div class="tooltip-content">Secret key: kunci untuk menentukan blok mana yang dipakai menanam bit. Key yang sama wajib dipakai saat extraction, kalau berbeda watermark akan gagal terbaca.</div>
                </div>
              </div>
              <div class="input-password-wrapper" style="margin-top:6px">
                <input type="password" name="secret_key" id="vKey" placeholder="Kunci rahasia" autocomplete="new-password" required>
                <button type="button" class="toggle-password-btn" title="Tampilkan/Sembunyikan Key" onclick="togglePasswordVisibility('vKey', this)">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <!-- 8. Field on_size_mismatch: radio cards dengan 3 opsi jelas dan deskripsi -->
            <div class="field" style="margin-top:20px">
              <label style="margin-bottom:8px">Strategi Bila Ukuran Citra Berubah (on_size_mismatch)</label>
              
              <div class="mismatch-cards-grid" id="mismatchCardGrid">
                <label class="mismatch-card-option {{ old('on_size_mismatch', $defaultMismatch) === 'raise' ? 'selected' : '' }}">
                  <input type="radio" name="on_size_mismatch" value="raise" {{ old('on_size_mismatch', $defaultMismatch) === 'raise' ? 'checked' : '' }} required>
                  <div class="mismatch-title">Tolak (raise)</div>
                  <div class="mismatch-desc">Tolak proses bila dimensi citra berubah (tanpa resample / sinkronisasi ukuran).</div>
                </label>

                <label class="mismatch-card-option {{ old('on_size_mismatch', $defaultMismatch) === 'resize' ? 'selected' : '' }}">
                  <input type="radio" name="on_size_mismatch" value="resize" {{ old('on_size_mismatch', $defaultMismatch) === 'resize' ? 'checked' : '' }}>
                  <div class="mismatch-title">Sesuaikan ukuran (resize)</div>
                  <div class="mismatch-desc">Ubah ukuran citra ke dimensi semula sebelum ekstraksi (cocok untuk attack resize murni).</div>
                </label>

                <label class="mismatch-card-option {{ old('on_size_mismatch', $defaultMismatch) === 'centered_crop' ? 'selected' : '' }}">
                  <input type="radio" name="on_size_mismatch" value="centered_crop" {{ old('on_size_mismatch', $defaultMismatch) === 'centered_crop' ? 'checked' : '' }}>
                  <div class="mismatch-title">Potong di tengah (centered crop)</div>
                  <div class="mismatch-desc">Mengasumsikan crop simetris di tengah citra (cocok bila redundancy &gt; 1).</div>
                </label>
              </div>
            </div>

<<<<<<< HEAD
            <button class="btn btn-dark" style="margin-top:18px;width:100%" id="verifyBtn" type="submit">Ekstrak Watermark</button>
          </div>
=======
            <button class="btn btn-dark" style="margin-top:22px;width:100%" id="verifyBtn" type="submit" {{ (!$run || empty($run['attacked_image'])) ? 'disabled' : '' }}>Ekstrak Watermark</button>
          </form>
        </div>
>>>>>>> cf5176e630dac2fe3bb7fb192a2cd73989a65c5a

          <div class="wscol">
            <p style="font-size:13.5px;margin-bottom:10px">Hasil ekstraksi akan tampil di sini setelah proses selesai.</p>

            @if (!$run || empty($run['extracted_images']))
              <div id="vResultEmpty" style="border:1px dashed var(--line);padding:28px;text-align:center;font-size:13px;color:#8A93A2">Belum ada citra diproses</div>
            @else
              <div id="vResult">
                @php($metricsList = session('watermark_metrics', []))
                
                @foreach ($run['extracted_images'] as $idx => $result)
                  @if ($result['success'])
                    <div style="margin-bottom:20px;padding:16px;border:1px solid var(--line);border-radius:6px">
                      <div style="font-size:13px;font-weight:500;margin-bottom:8px">{{ $result['name'] }}</div>
                      
                      <div style="text-align:center;margin-bottom:12px">
                        <img src="{{ route('artifacts.show', ['kind' => 'extracted']) }}?path={{ urlencode($result['extracted_path']) }}" alt="Extracted Watermark" style="max-height:120px;border:1px solid var(--line);border-radius:4px;padding:4px;background:#fff;">
                        <p style="font-size:11px;color:var(--slate);margin-top:4px">Extracted Watermark</p>
                      </div>

                      @if (isset($metricsList[$idx]) && $metricsList[$idx]['ncc'] !== 'N/A')
                        <div class="metricrow">
                          <div class="metric"><div class="num">{{ $metricsList[$idx]['ncc'] }}</div><div class="lbl2">NC</div></div>
                          <div class="metric"><div class="num">{{ $metricsList[$idx]['ber'] }}</div><div class="lbl2">BER</div></div>
                          <div class="metric"><div class="num">{{ $metricsList[$idx]['status'] }}</div><div class="lbl2">Status</div></div>
                        </div>
                      @else
                        <div style="font-size:12px;color:var(--slate);text-align:center;padding:8px;background:#f8f9fa;border-radius:4px">
                          NC/BER tidak dihitung (watermark asli tidak diupload)
                        </div>
                      @endif
                    </div>
                  @else
                    <div style="margin-bottom:12px;padding:12px;border:1px solid #f2b4a9;background:#fff5f5;border-radius:6px">
                      <div style="font-size:13px;font-weight:500;color:#7a1f1f">{{ $result['name'] }} — Gagal</div>
                      <div style="font-size:12px;color:#7a1f1f;margin-top:4px">{{ $result['error'] }}</div>
                    </div>
                  @endif
                @endforeach

                <div class="banner-info" id="nextBanner" style="margin-top:18px;display:flex">
                  Tersimpan. Lihat semua hasil pengujian di <a href="{{ route('evaluation.index') }}" style="color:var(--blue-dim);font-weight:600">halaman Evaluation →</a>
                </div>
<<<<<<< HEAD
=======
              @endif

              <div class="next-steps-container" id="nextBanner">
                <div class="next-steps-title">Pilih Langkah Selanjutnya</div>
                <div class="next-steps-desc">Hasil ekstraksi tersimpan. Anda bisa melakukan ekstraksi ulang dengan strategi mismatch berbeda, atau melihat grafik &amp; rangkuman evaluasi.</div>
                <div style="display:flex;gap:12px">
                  <a href="{{ route('evaluation.index') }}" class="btn btn-primary btn-sm">Lanjut ke Evaluation →</a>
                </div>
>>>>>>> cf5176e630dac2fe3bb7fb192a2cd73989a65c5a
              </div>
            @endif
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('#mismatchCardGrid input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('#mismatchCardGrid .mismatch-card-option').forEach(card => {
                card.classList.toggle('selected', card.querySelector('input').checked);
            });
        });
    });
</script>
@endpush
