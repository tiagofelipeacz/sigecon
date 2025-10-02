{{-- Header compacto para páginas de Config --}}
@php
  $title   = $title   ?? 'Configurações';
  $desc    = $desc    ?? null;
  $actions = $actions ?? []; // [['label'=>'Novo', 'href'=>route(...), 'variant'=>'primary']]
@endphp

<div class="cfg-head" style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
  <div>
    <h1 style="margin:0">{{ $title }}</h1>
    @if($desc)
      <p class="sub" style="margin:.25rem 0 0">{{ $desc }}</p>
    @endif
  </div>

  @if(!empty($actions))
    <div class="toolbar" style="display:flex; gap:.5rem; align-items:center;">
      @foreach($actions as $a)
        <a href="{{ $a['href'] }}"
           class="btn {{ $a['variant'] ?? '' }}">{{ $a['label'] }}</a>
      @endforeach
    </div>
  @endif
</div>
