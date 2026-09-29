@extends('layouts.app')

@section('title', 'Attack / Manipulation — Spectra Watermarking')

@section('footer_code', '02 · ATTACK')

@section('content')
<section class="pagehead">
  <div class="wrap">
    <div class="breadcrumb"><a href="{{ route('index') }}">Beranda</a> / Attack</div>
    <h1>Uji ketahanan citra terhadap manipulasi</h1>
    <p class="lead">Pilih citra ter-watermark yang ingin diserang, pilih jenis attack, lalu lihat perubahan visualnya sebelum dan sesudah. Perhitungan NC/BER dilakukan di halaman Extraction.</p>
    <div class="stepnav">
      <a href="{{ route('embedding.index') }}">01 Embedding</a><span class="arrow">→</span>
      <a class="here">02 Attack</a><span class="arrow">→</span>
      <a href="{{ route('extraction.index') }}">03 Extraction</a><span class="arrow">→</span>
      <a href="{{ route('evaluation.index') }}">04 Evaluation</a>
    </div>
  </div>
</section>

<section style="padding-top:56px">
  <div class="wrap">

    <div class="shead" style="margin-bottom:24px"><span class="tag">Langkah 1</span><h2 style="font-size:22px">Pilih sumber citra</h2></div>

    <form method="POST" action="{{ route('attack.run') }}" enctype="multipart/form-data" id="attackForm">
      @csrf
      <input type="hidden" name="source_type" id="sourceTypeInput" value="server">

      <!-- Tab: Dari Server / Upload Sendiri -->
      <div style="border:1px solid var(--line);background:#fff;margin-bottom:24px">
        <div style="padding:20px 28px;border-bottom:1px solid var(--line)">
          <div style="display:flex;gap:16px;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
              <input type="radio" name="source_type_radio" value="server" id="sourceServer" checked onchange="toggleSource()">
              <span>Dari Server (hasil Embedding)</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
              <input type="radio" name="source_type_radio" value="upload" id="sourceUpload" onchange="toggleSource()">
              <span>Upload Sendiri</span>
            </label>
          </div>
        </div>

        <!-- Panel: Dari Server -->
        <div id="panelServer" style="padding:20px 28px">
          @if ($run && count($serverImages) > 0)
            <!-- Preview gambar watermarked dari embedding -->
            <div style="margin-bottom:16px;padding:16px;border:1px solid var(--line);border-radius:6px;background:#f8f9fa">
              <div style="font-size:13px;font-weight:500;margin-bottom:8px">Preview Citra Watermarked (Hasil Embedding)</div>
              <div style="text-align:center">
                <img src="{{ route('artifacts.show', ['kind' => 'watermarked']) }}" alt="Citra watermarked" style="max-width:100%;max-height:200px;border:1px solid var(--line);border-radius:4px;background:#fff;">
              </div>
              <div style="margin-top:8px;font-size:12px;color:var(--slate)">
                Path: {{ $serverImages[0]['path'] }}
              </div>
              <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('artifacts.show', ['kind' => 'watermarked']) }}" download="watermarked.png" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                  Unduh Citra Watermarked
                </a>
              </div>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:12px">
              @foreach ($serverImages as $image)
                <label style="display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--line);border-radius:6px;cursor:pointer">
                  <input type="checkbox" name="server_images[]" value="{{ $image['path'] }}" checked>
                  <div>
                    <div style="font-weight:500">{{ $image['name'] }}</div>
                    <div style="font-size:12px;color:var(--slate)">{{ $image['path'] }}</div>
                  </div>
                </label>
              @endforeach
            </div>
          @else
            <div style="border:1px dashed var(--line);padding:24px;text-align:center;font-size:13px;color:#8A93A2">
              Belum ada hasil dari halaman Embedding di browser ini. <a href="{{ route('embedding.index') }}">Jalankan embedding dulu</a>.
            </div>
          @endif
        </div>

        <!-- Panel: Upload Sendiri -->
        <div id="panelUpload" style="padding:20px 28px;display:none">
          <div style="border:2px dashed var(--line);border-radius:8px;padding:32px;text-align:center;margin-bottom:16px" id="dropZoneAttack">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" style="width:48px;height:48px;margin:0 auto 12px;display:block;color:var(--slate)"><path d="M12 16V4M12 4l-4 4M12 4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
            <p style="margin:0;font-size:14px">Tarik &amp; letakkan gambar, atau klik untuk memilih</p>
            <p style="margin:4px 0 0;font-size:12px;color:var(--slate)">PNG / JPG, maksimal 5 MB per file, maksimal 10 file</p>
            <input type="file" name="uploaded_images[]" id="fileInputAttack" accept="image/png,image/jpeg" multiple style="display:none">
          </div>
          <div id="uploadListAttack" style="display:flex;flex-direction:column;gap:8px"></div>
          
          @if (count($uploadedImages) > 0)
            <div style="margin-top:16px">
              <div style="font-size:13px;font-weight:500;margin-bottom:8px">File upload tersimpan:</div>
              <div style="display:flex;flex-direction:column;gap:8px">
                @foreach ($uploadedImages as $img)
                  <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border:1px solid var(--line);border-radius:4px">
                    <div>
                      <div style="font-size:13px">{{ $img['name'] }}</div>
                      <div style="font-size:11px;color:var(--slate)">{{ number_format($img['size'] / 1024, 1) }} KB</div>
                    </div>
                    <form method="POST" action="{{ route('attack.delete_upload') }}" style="display:inline">
                      @csrf
                      <input type="hidden" name="path" value="{{ $img['path'] }}">
                      <button type="submit" style="background:none;border:none;color:#c0392b;cursor:pointer;font-size:12px">Hapus</button>
                    </form>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>

      <div id="attackPanel" style="margin-top:32px">
        <div class="shead" style="margin-bottom:24px"><span class="tag">Langkah 2</span><h2 style="font-size:22px">Pilih jenis attack &amp; jalankan</h2></div>
        <div style="border:1px solid var(--line);background:#fff">
          <div style="padding:28px">
            <div class="attackflow">
              <span class="af-node">Citra Ter-watermark</span><span class="af-arrow">→</span>
              <span class="af-node" id="atkNode">Parameter Attack</span><span class="af-arrow">→</span>
              <span class="af-node">Attacked Image</span>
            </div>

            <div class="field">
              <label>Jenis Attack</label>
              <select name="attack_type" id="attackType" required>
                <option value="" disabled {{ old('attack_type', $run['attack_type'] ?? '') === '' ? 'selected' : '' }}>Pilih jenis attack</option>
                <option value="jpeg" {{ old('attack_type', $run['attack_type'] ?? '') === 'jpeg' ? 'selected' : '' }}>JPEG compression</option>
                <option value="resize" {{ old('attack_type', $run['attack_type'] ?? '') === 'resize' ? 'selected' : '' }}>Resize murni</option>
                <option value="pure_crop" {{ old('attack_type', $run['attack_type'] ?? '') === 'pure_crop' ? 'selected' : '' }}>Pure crop</option>
                <option value="crop_resize_back" {{ old('attack_type', $run['attack_type'] ?? '') === 'crop_resize_back' ? 'selected' : '' }}>Crop lalu resize balik</option>
                <option value="gaussian_noise" {{ old('attack_type', $run['attack_type'] ?? '') === 'gaussian_noise' ? 'selected' : '' }}>Gaussian noise</option>
                <option value="brightness_contrast" {{ old('attack_type', $run['attack_type'] ?? '') === 'brightness_contrast' ? 'selected' : '' }}>Brightness & Contrast</option>
              </select>
            </div>

            <div class="field attack-param" data-attack="jpeg">
              <label>Kualitas JPEG (1–100)</label>
              <input type="number" name="quality" min="1" max="100" value="{{ old('quality', 70) }}" placeholder="cth: 70">
            </div>

            <div class="field attack-param" data-attack="resize">
              <label>Scale Resize (cth: 0.5 untuk 50%)</label>
              <input type="number" name="scale" min="0" step="any" value="{{ old('scale', 0.5) }}" placeholder="cth: 0.5">
            </div>

            <div class="field attack-param" data-attack="pure_crop,crop_resize_back">
              <label>Crop dari Setiap Sisi (%)</label>
              <input type="number" name="crop_percent" min="0" max="99.99" step="any" value="{{ old('crop_percent', 10) }}" placeholder="cth: 10">
            </div>

            <div class="field attack-param" data-attack="gaussian_noise">
              <label>Sigma Gaussian Noise</label>
              <input type="number" name="sigma" min="0" step="any" value="{{ old('sigma', 10) }}" placeholder="cth: 10">
            </div>

            <div class="field attack-param" data-attack="gaussian_noise">
              <label>Seed (opsional, untuk reproducibility)</label>
              <input type="number" name="seed" value="{{ old('seed') }}" placeholder="cth: 42">
            </div>

            <div class="field attack-param" data-attack="brightness_contrast">
              <label>Alpha / Contrast (gt: 0)</label>
              <input type="number" name="brightness_alpha" min="0" step="any" value="{{ old('brightness_alpha', 1.2) }}" placeholder="cth: 1.2">
            </div>

            <div class="field attack-param" data-attack="brightness_contrast">
              <label>Beta / Brightness</label>
              <input type="number" name="brightness_beta" step="any" value="{{ old('brightness_beta', 20) }}" placeholder="cth: 20">
            </div>

            <button class="btn btn-primary" type="submit" style="margin-top:14px" id="attackSubmit">Terapkan Attack</button>

            @if (!empty($run['attacked_images']))
              <div class="rob-viewer" id="robViewer" style="display:block;margin-top:28px;padding-top:20px;border-top:1px solid var(--line)">
                <div class="shead" style="margin-bottom:16px"><span class="tag">Hasil Attack</span><h2 style="font-size:18px">Citra Setelah Serangan</h2></div>
                <div id="attackResults">
                  @foreach ($run['attacked_images'] as $result)
                    @if ($result['success'])
                      <div style="margin-bottom:16px;padding:12px;border:1px solid var(--line);border-radius:6px">
                        <div style="font-size:13px;font-weight:500;margin-bottom:8px">{{ $result['name'] }}</div>
                        <div class="cvwrap">
                          <img src="{{ route('artifacts.show', ['kind' => 'attacked']) }}?path={{ urlencode($result['output_path']) }}" alt="Citra hasil attack" style="max-width:100%;max-height:300px;border:1px solid var(--line);border-radius:4px;">
                        </div>
                      </div>
                    @else
                      <div style="margin-bottom:12px;padding:12px;border:1px solid #f2b4a9;background:#fff5f5;border-radius:6px">
                        <div style="font-size:13px;font-weight:500;color:#7a1f1f">{{ $result['name'] }} — Gagal</div>
                        <div style="font-size:12px;color:#7a1f1f;margin-top:4px">{{ $result['error'] }}</div>
                      </div>
                    @endif
                  @endforeach
                </div>
                <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap">
                  <a class="btn btn-outline btn-sm" href="{{ route('artifacts.show', ['kind' => 'attacked']) }}" download="attacked.png">Unduh Citra Hasil Attack</a>
                </div>
                <div class="banner-info" id="nextBanner" style="display:flex;margin-top:16px">
                  Citra hasil attack tersimpan di server. Lanjutkan ke <a href="{{ route('extraction.index') }}" style="color:var(--blue-dim);font-weight:600">halaman Extraction →</a> untuk mencoba mengekstraksi kembali watermark-nya.
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </form>

  </div>
</section>
@endsection

@push('scripts')
<script>
    // Toggle sumber gambar
    function toggleSource() {
        const source = document.querySelector('input[name="source_type_radio"]:checked').value;
        document.getElementById('sourceTypeInput').value = source;
        document.getElementById('panelServer').style.display = source === 'server' ? 'block' : 'none';
        document.getElementById('panelUpload').style.display = source === 'upload' ? 'block' : 'none';
    }

    // Drag & drop untuk upload
    const dropZone = document.getElementById('dropZoneAttack');
    const fileInput = document.getElementById('fileInputAttack');
    const uploadList = document.getElementById('uploadListAttack');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--blue)'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = 'var(--line)'; });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--line)';
            if (e.dataTransfer.files) {
                fileInput.files = e.dataTransfer.files;
                updateUploadList(e.dataTransfer.files);
            }
        });
        fileInput.addEventListener('change', e => {
            if (e.target.files) updateUploadList(e.target.files);
        });
    }

    function updateUploadList(files) {
        uploadList.innerHTML = '';
        Array.from(files).forEach((file, idx) => {
            const size = (file.size / 1024).toFixed(1);
            const ext = file.name.split('.').pop().toUpperCase();
            const div = document.createElement('div');
            div.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border:1px solid var(--line);border-radius:4px';
            div.innerHTML = '<div><div style="font-size:13px">' + file.name + '</div><div style="font-size:11px;color:var(--slate)">' + size + ' KB · ' + ext + '</div></div>';
            uploadList.appendChild(div);
        });
    }

    // Toggle parameter attack
    const attackType = document.getElementById('attackType');
    const atkNode = document.getElementById('atkNode');
    if (attackType) {
        const updateParameters = () => {
            const selected = attackType.value;
            document.querySelectorAll('.attack-param').forEach(field => {
                const activeTypes = field.dataset.attack.split(',');
                const active = activeTypes.includes(selected);
                field.hidden = !active;
                field.querySelector('input').disabled = !active;
            });
            if (atkNode) {
                const labels = {
                    jpeg: 'JPEG Compression',
                    resize: 'Resize Murni',
                    pure_crop: 'Pure Crop',
                    crop_resize_back: 'Crop lalu Resize Balik',
                    gaussian_noise: 'Gaussian Noise',
                    brightness_contrast: 'Brightness & Contrast'
                };
                atkNode.textContent = labels[selected] || 'Parameter Attack';
            }
        };
        attackType.addEventListener('change', updateParameters);
        updateParameters();
    }

    // Loading state saat submit
    const attackForm = document.getElementById('attackForm');
    if (attackForm) {
        attackForm.addEventListener('submit', function() {
            const btn = document.getElementById('attackSubmit');
            btn.disabled = true;
            btn.textContent = 'Memproses...';
        });
    }
</script>
@endpush
