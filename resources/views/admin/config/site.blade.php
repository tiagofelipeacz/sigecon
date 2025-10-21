{{-- resources/views/admin/config/site.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Site - SIGECON')

@section('content')
  @php
    use Illuminate\Support\Facades\Route;

    $brand        = old('brand',        $site['brand']        ?? 'GestaoConcursos');
    $primary      = old('primary',      $site['primary']      ?? '#0f172a');
    $accent       = old('accent',       $site['accent']       ?? '#111827');
    $bannerTitle  = old('banner_title', $site['banner_title'] ?? 'Concursos e Processos Seletivos');
    $bannerSub    = old('banner_sub',   $site['banner_sub']   ?? 'Inscreva-se, acompanhe publicações e consulte resultados.');
    $bannerUrl    = old('banner_url',   $site['banner_url']   ?? null);
    $logoUrl      = old('logo_url',     $site['logo_url']     ?? null);

    $urlUpdate         = Route::has('admin.config.site.update') ? route('admin.config.site.update') : url('/admin/config/site');
    $urlDestroyBanner  = Route::has('admin.config.site.banner.destroy') ? route('admin.config.site.banner.destroy') : url('/admin/config/site/banner');
    $urlDestroyLogo    = Route::has('admin.config.site.logo.destroy')   ? route('admin.config.site.logo.destroy')   : url('/admin/config/site/logo');
    $urlIndex          = Route::has('admin.config.site.edit')   ? route('admin.config.site.edit')   : url('/admin/config/site');
  @endphp

  <h1>Site</h1>
  <p class="sub">Defina marca, cores, logo e o banner exibidos na página pública.</p>

  @if (session('success'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="mb-3 rounded border border-red-300 bg-red-50 p-3 text-red-800">
      <div class="font-semibold mb-1">Corrija os erros abaixo:</div>
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="toolbar" style="display:flex; gap:.5rem; align-items:center; margin-bottom:10px;">
    <div style="flex:1"></div>
    <a class="btn" href="{{ $urlIndex }}">Recarregar</a>
  </div>

  <style>
    .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    .card-min .hd{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; padding:10px 12px; font-weight:600; }
    .card-min .bd{ padding:12px; }
    .grid-2{ display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    .row{ display:grid; grid-template-columns: 1fr; gap:12px; }
    @media (max-width: 920px){ .grid-2{ grid-template-columns:1fr; } }
    .form-min label{ font-size:.875rem; font-weight:600; display:block; margin-bottom:.35rem; }
    .form-min input[type="text"], .form-min input[type="url"], .form-min input[type="file"]{ width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; }
    .hint{ color:#6b7280; font-size:.85rem; }
    .swatch{ width:32px; height:32px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; }
    .actions{ display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
    .preview{ border:1px dashed #cbd5e1; border-radius:12px; display:flex; align-items:center; justify-content:center; background:#f8fafc; min-height:120px; overflow:hidden; padding:10px; }
    .preview img{ max-width:100%; max-height:260px; display:block; }
  </style>

  {{-- FORM PRINCIPAL (PUT) --}}
  <form class="card-min form-min" method="POST" action="{{ $urlUpdate }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="hd">Configurações Gerais</div>
    <div class="bd">
      <div class="grid-2">
        <div>
          <label for="brand">Nome/Marca</label>
          <input id="brand" name="brand" type="text" value="{{ $brand }}" placeholder="GestaoConcursos">
        </div>

        <div class="grid-2" style="align-items:end;">
          <div>
            <label for="primary">Cor Primária (hex)</label>
            <div style="display:flex; gap:.5rem; align-items:center;">
              <input id="primary" name="primary" type="text" value="{{ $primary }}" placeholder="#0f172a" oninput="previewPrimary(this.value)">
              <span class="swatch" id="swatch-primary" style="background: {{ $primary }}"></span>
            </div>
            <div class="hint">Ex.: #0f172a</div>
          </div>
          <div>
            <label for="accent">Cor Secundária (hex)</label>
            <div style="display:flex; gap:.5rem; align-items:center;">
              <input id="accent" name="accent" type="text" value="{{ $accent }}" placeholder="#111827" oninput="previewAccent(this.value)">
              <span class="swatch" id="swatch-accent" style="background: {{ $accent }}"></span>
            </div>
            <div class="hint">Ex.: #111827</div>
          </div>
        </div>
      </div>
    </div>

    <div class="hd">Logo</div>
    <div class="bd">
      <div class="grid-2">
        <div>
          <label for="logo">Logo (upload)</label>
          <input id="logo" name="logo" type="file" accept="image/*">
          <div class="hint">PNG/SVG/JPG. Dê preferência a fundo transparente.</div>
        </div>
        <div>
          <label for="logo_url">Logo (URL direta)</label>
          <input id="logo_url" name="logo_url" type="url" value="{{ $logoUrl }}" placeholder="https://.../logo.png" oninput="previewLogo(this.value)">
          <div class="hint">Se preencher a URL, ela tem prioridade sobre o upload.</div>
        </div>
      </div>

      <div class="row" style="margin-top:12px;">
        <div>
          <label>Pré-visualização</label>
          <div class="preview" id="logo-prev">
            @if($logoUrl)
              <img id="logo-prev-img" src="{{ $logoUrl }}" alt="Logo atual">
            @else
              <div class="hint" id="logo-prev-empty">Nenhuma logo configurada.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="hd">Banner</div>
    <div class="bd">
      <div class="grid-2">
        <div>
          <label for="banner">Imagem (upload)</label>
          <input id="banner" name="banner" type="file" accept="image/*">
          <div class="hint">Formato vertical recomendado (4:5, 3:4).</div>
        </div>
        <div>
          <label for="banner_url">Imagem (URL direta)</label>
          <input id="banner_url" name="banner_url" type="url" value="{{ $bannerUrl }}" placeholder="https://.../banner.jpg" oninput="previewBanner(this.value)">
          <div class="hint">Se preencher a URL, ela tem prioridade sobre o upload.</div>
        </div>
      </div>

      <div class="grid-2" style="margin-top:10px;">
        <div>
          <label for="banner_title">Título do Banner</label>
          <input id="banner_title" name="banner_title" type="text" value="{{ $bannerTitle }}">
        </div>
        <div>
          <label for="banner_sub">Subtítulo do Banner</label>
          <input id="banner_sub" name="banner_sub" type="text" value="{{ $bannerSub }}">
        </div>
      </div>

      <div class="row" style="margin-top:12px;">
        <div>
          <label>Pré-visualização</label>
          <div class="preview" id="banner-prev">
            @if($bannerUrl)
              <img id="banner-prev-img" src="{{ $bannerUrl }}" alt="Banner atual">
            @else
              <div class="hint" id="banner-prev-empty">Nenhum banner configurado.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="actions" style="margin-top:12px;">
        <button class="btn primary" type="submit">Salvar</button>
        <a class="btn" href="{{ $urlIndex }}">Cancelar</a>
      </div>
    </div>
  </form>

  {{-- FORM SEPARADO: Remover Logo --}}
  <div class="actions" style="margin-top:8px;">
    @if($logoUrl)
      <form method="POST" action="{{ $urlDestroyLogo }}" onsubmit="return confirm('Remover a logo atual?')">
        @csrf @method('DELETE')
        <button class="btn" type="submit">Remover logo</button>
      </form>
    @endif

    {{-- FORM SEPARADO: Remover Banner --}}
    @if($bannerUrl)
      <form method="POST" action="{{ $urlDestroyBanner }}" onsubmit="return confirm('Remover o banner atual?')">
        @csrf @method('DELETE')
        <button class="btn" type="submit">Remover banner</button>
      </form>
    @endif
  </div>

  <script>
    function isHex(v){ return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v||''); }
    function previewPrimary(val){ const el=document.getElementById('swatch-primary'); if(el) el.style.background = isHex(val)?val:'#fff'; }
    function previewAccent(val){  const el=document.getElementById('swatch-accent');  if(el) el.style.background = isHex(val)?val:'#fff'; }

    function previewImageGeneric(val, wrapId, imgId, emptyId){
      const wrap=document.getElementById(wrapId);
      if(!wrap) return;
      if(val && /^https?:\/\//i.test(val)){
        const existing=document.getElementById(imgId);
        const empty=document.getElementById(emptyId);
        if (empty) empty.remove();
        if (existing){ existing.src = val; }
        else {
          const img=document.createElement('img');
          img.id = imgId; img.src = val;
          wrap.innerHTML=''; wrap.appendChild(img);
        }
      } else {
        wrap.innerHTML = '<div class="hint" id="'+emptyId+'">Nenhuma imagem configurada.</div>';
      }
    }
    function previewBanner(v){ previewImageGeneric(v,'banner-prev','banner-prev-img','banner-prev-empty'); }
    function previewLogo(v){   previewImageGeneric(v,'logo-prev',  'logo-prev-img',  'logo-prev-empty'); }
  </script>
@endsection
