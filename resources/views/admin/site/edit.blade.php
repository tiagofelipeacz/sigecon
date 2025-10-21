@extends('layouts.admin') {{-- use o layout do seu admin (admin.blade) --}}

@section('content')
<div class="container">
  <h1 style="margin:0 0 16px;">Configurações do Site</h1>

  @if(session('ok'))
    <div style="padding:10px 12px; background:#ecfdf5; border:1px solid #34d399; border-radius:8px; margin-bottom:14px;">
      {{ session('ok') }}
    </div>
  @endif

  <form method="POST" action="{{ route('admin.site.update') }}" enctype="multipart/form-data" style="display:grid; gap:14px; max-width:720px;">
    @csrf

    <label>Marca
      <input type="text" name="brand" class="form-control" value="{{ old('brand', $site->brand) }}">
    </label>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
      <label>Cor primária
        <input type="color" name="primary" class="form-control" value="{{ old('primary', $site->primary ?? '#0f172a') }}">
      </label>
      <label>Cor de destaque
        <input type="color" name="accent" class="form-control" value="{{ old('accent', $site->accent ?? '#111827') }}">
      </label>
    </div>

    <label>Título do banner
      <input type="text" name="banner_title" class="form-control" value="{{ old('banner_title', $site->banner_title) }}">
    </label>

    <label>Subtítulo do banner
      <input type="text" name="banner_sub" class="form-control" value="{{ old('banner_sub', $site->banner_sub) }}">
    </label>

    <label>Imagem do banner (opcional)
      <input type="file" name="banner_image" accept="image/*">
    </label>

    @if(!empty($site->banner_image))
      <div style="margin-top:8px;">
        <div style="font-weight:600; margin-bottom:6px;">Prévia atual</div>
        <img src="{{ asset('storage/'.$site->banner_image) }}" style="max-width:360px; border:1px solid #e5e7eb; border-radius:8px;">
      </div>
    @endif

    <button class="btn btn-primary" type="submit">Salvar</button>
  </form>
</div>
@endsection
