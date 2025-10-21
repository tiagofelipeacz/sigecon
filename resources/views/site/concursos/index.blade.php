{{-- resources/views/site/concursos/index.blade.php --}}
@extends('layouts.site')
@section('title', 'Concursos')

@php
  use Carbon\Carbon;
  use Illuminate\Support\Str;

  // Salvaguardas
  $q      = $q      ?? '';
  $status = $status ?? '';
  $site   = $site   ?? [
      'brand'        => 'GestaoConcursos',
      'primary'      => '#0f172a',
      'accent'       => '#111827',
      'banner_url'   => null,
      'banner_title' => 'Concursos e Processos Seletivos',
      'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
      'logo_url'     => null,
  ];

  // ===== Função utilitária de resolução de caminhos/URLs públicas =====
  $resolvePublicUrl = function (?string $p): ?string {
    if (!$p) return null;
    $p = trim((string)$p);
    if ($p === '') return null;

    if (Str::startsWith($p, ['http://','https://','data:image'])) return $p; // absoluta/base64
    if (Str::startsWith($p, ['/storage/','storage/'])) return asset(ltrim($p,'/'));

    $norm = ltrim($p,'/');
    if (Str::startsWith($norm, 'public/')) return asset('storage/'.substr($norm,7));
    if (file_exists(public_path($p)))               return asset($p);
    if (file_exists(public_path($norm)))            return asset($norm);
    if (file_exists(public_path('storage/'.$norm))) return asset('storage/'.$norm);

    return asset('storage/'.$norm); // fallback
  };

  // ===== Resolver URL de banner dinamicamente =====
  $resolveBanner = function(array $cfg) use ($resolvePublicUrl): ?string {
    foreach (['banner_url','hero_image','hero_url','banner','banner_path','image'] as $k) {
      if (!empty($cfg[$k])) return $resolvePublicUrl($cfg[$k]);
    }
    return null;
  };

  // (Opcional) LOGO dinâmico — caso queira usar aqui no futuro
  $resolveLogo = function(array $cfg) use ($resolvePublicUrl): ?string {
    foreach (['logo_url','logo_path','logo','site_logo','brand_logo','header_logo'] as $k) {
      if (!empty($cfg[$k])) return $resolvePublicUrl($cfg[$k]);
    }
    return null;
  };

  $bannerResolved = $resolveBanner($site);

  // Helpers de data
  $fmt = function($d){
    if (!$d) return null;
    try { return Carbon::parse($d)->format('d/m/Y'); } catch(\Throwable $e){ return null; }
  };
  $inscAberta = function($ini,$fim){
    $now = Carbon::now();
    try {
      $ini = $ini ? Carbon::parse($ini) : null;
      $fim = $fim ? Carbon::parse($fim) : null;
    } catch (\Throwable $e) { return false; }

    if ($ini && $fim)  return $now->between($ini->startOfDay(), $fim->endOfDay());
    if ($ini && !$fim) return $now->greaterThanOrEqualTo($ini->startOfDay());
    if (!$ini && $fim) return $now->lessThanOrEqualTo($fim->endOfDay());
    return false;
  };
@endphp

@section('content')
<style>
  :root{
    --site-primary: {{ $site['primary'] ?? '#0f172a' }};
    --site-accent:  {{ $site['accent']  ?? '#111827' }};
  }

  /* ===== util ===== */
  .container{ max-width:1100px; margin:0 auto; padding:0 16px; }
  .muted{ color:#6b7280; }
  .btn{
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid #e5e7eb; background:#fff; padding:8px 12px;
    border-radius:10px; cursor:pointer; text-decoration:none;
    color:#111827; /* contraste garantido no hero */
  }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:var(--site-accent); border-color:var(--site-accent); color:#fff; }
  .btn.primary:hover{ filter:brightness(1.05); }
  .chip{ display:inline-flex; align-items:center; gap:8px; border:1px solid #e5e7eb; padding:8px 12px; border-radius:999px; background:#fff; font-size:14px; text-decoration:none; color:#111827; }
  .chip.active{ background:#eef2ff; border-color:#e0e7ff; color:#1e3a8a; }
  .pill-ok{ display:inline-block; font-weight:700; font-size:12px; background:#16a34a; color:#fff; padding:6px 10px; border-radius:6px; }

  /* ===== HERO ===== */
  .hero{ background:var(--site-primary); color:#fff; }
  .hero-grid{ display:grid; grid-template-columns: 1.2fr 1fr; gap:24px; align-items:center; padding:40px 0 24px; }
  .hero h1{ font-size:36px; line-height:1.1; margin:0 0 8px; font-weight:800; letter-spacing:-.02em; }
  .hero p{ font-size:16px; opacity:.95; margin:0 0 16px; }
  .hero .media{
    border-radius:16px; overflow:hidden; box-shadow:0 8px 28px rgba(0,0,0,.24);
    background:#0b1220; aspect-ratio:4/5; display:flex; align-items:center; justify-content:center;
  }
  .hero .media img{ width:100%; height:100%; object-fit:cover; display:block; }

  /* ===== filtros ===== */
  .filters{ display:grid; grid-template-columns: 1fr auto auto; gap:10px; margin:16px 0 10px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; }
  .status-row{ display:flex; gap:8px; flex-wrap:wrap; margin:6px 0 0; }

  /* ===== grid de cards ===== */
  .cards{ display:grid; grid-template-columns: repeat(3, 1fr); gap:18px; margin:12px 0 26px; }
  @media (max-width: 980px){ .hero-grid{ grid-template-columns: 1fr; } .cards{ grid-template-columns: repeat(2, 1fr);} }
  @media (max-width: 640px){ .cards{ grid-template-columns: 1fr; } }

  .card{ border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff; display:flex; }
  .card > a{ display:flex; flex-direction:column; width:100%; height:100%; }
  .card a{ text-decoration:none; color:#111827; }
  .card .cover{ position:relative; width:100%; aspect-ratio:16/9; background:#f8fafc; overflow:hidden; display:flex; align-items:center; justify-content:center; }
  .card .cover img{ width:100%; height:100%; object-fit:contain; background:#fff; }
  .card .body{ flex:1 1 auto; padding:12px 12px 14px; display:flex; flex-direction:column; gap:8px; }
  .card .title{ font-weight:700; line-height:1.25; min-height:45px; }
  .line{ font-size:14px; }
  .line strong{ font-weight:700; }

  /* ===== footer do card (badge + vagas) ===== */
  .footerbar{
    display:flex; align-items:center;
    border-top:1px solid #e5e7eb;
    overflow:hidden;
  }
  .footerbar .badge-open{
    background:#16a34a; color:#fff; font-weight:800;
    padding:12px 16px;
    border-bottom-left-radius:14px;
    letter-spacing:.01em; text-transform:uppercase; font-size:12px;
  }
  .footerbar .badge-closed{
    background:#ef4444; color:#fff; font-weight:800;
    padding:12px 16px;
    border-bottom-left-radius:14px;
    letter-spacing:.01em; text-transform:uppercase; font-size:12px;
  }
  .footerbar .badge-info{
    background:var(--site-accent); color:#fff; font-weight:800;
    padding:12px 16px;
    border-bottom-left-radius:14px;
    letter-spacing:.01em; text-transform:uppercase; font-size:12px;
  }
  .footerbar .vagas{
    margin-left:auto; background:#f3f4f6;
    display:flex; align-items:center; gap:8px;
    padding:10px 16px; border-bottom-right-radius:14px;
  }
  .footerbar .vagas .num{ font-weight:800; font-size:22px; line-height:1; color:#1e3a8a; }
  .footerbar .vagas .lbl{ color:#6b7280; font-weight:600; font-size:14px; }
  @media (max-width: 640px){ .footerbar .vagas .num{ font-size:20px; } }
</style>

<div class="site">
  {{-- ================ HERO (sem header duplicado) ================ --}}
  <section class="hero">
    <div class="container">
      <div class="hero-grid">
        <div>
          <h1>{{ $site['banner_title'] ?? 'Concursos e Processos Seletivos' }}</h1>
          <p>{{ $site['banner_sub'] ?? 'Inscreva-se, acompanhe publicações e consulte resultados.' }}</p>

          <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap">
            <a href="{{ route('candidato.login') }}" class="btn primary">Área do Candidato</a>
            <a href="{{ route('site.concursos.index') }}" class="btn">Ver todos os concursos</a>
          </div>
        </div>

        <div class="media">
          @if($bannerResolved)
            <img src="{{ $bannerResolved }}" alt="Banner">
          @else
            <img src="{{ asset('images/hero-portrait.jpg') }}" alt="">
          @endif
        </div>
      </div>
    </div>
  </section>

  {{-- ================ FILTROS ================= --}}
  <div class="container">
    <form method="GET" class="filters">
      <input class="input" type="text" name="q" placeholder="Buscar por título…" value="{{ $q }}">
      <button class="btn primary" type="submit">Buscar</button>
      <a class="btn" href="{{ route('site.concursos.index') }}">Limpar</a>
    </form>

    <div class="status-row" aria-label="Filtros por situação">
      <a class="chip {{ $status==='' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q])) }}">Todos</a>

      <a class="chip {{ $status==='andamento' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q,'status'=>'andamento'])) }}">Em andamento</a>

      <a class="chip {{ $status==='finalizado' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q,'status'=>'finalizado'])) }}">Finalizado</a>

      <a class="chip {{ $status==='suspenso' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q,'status'=>'suspenso'])) }}">Suspenso</a>
    </div>
  </div>

  {{-- ================ GRID ================= --}}
  <div class="container">
    @if($concursos->count() === 0)
      <div class="muted" style="padding:18px 0">Nenhum concurso encontrado.</div>
    @else
      <div class="cards">
        @foreach($concursos as $c)
          @php
            $url        = route('site.concursos.show', $c->slug);
            $img        = $c->card_image ?? null;
            $insIni     = $c->inscricoes_ini ?? null;
            $insFim     = $c->inscricoes_fim ?? null;
            $isOpen     = $inscAberta($insIni, $insFim);
            $editalNum  = trim((string)($c->edital_num ?? ''));
            $provaData  = $fmt($c->prova_data ?? null);

            $vagasRaw = $c->total_vagas
              ?? $c->vagas_total
              ?? $c->vagas
              ?? $c->qtd_vagas
              ?? $c->quantidade_vagas
              ?? 0;
            $vagasTotal = (int) $vagasRaw;

            $isenIni = $fmt($c->isencao_ini ?? null);
            $isenFim = $fmt($c->isencao_fim ?? null);

            $andamento = true;
            if (property_exists($c,'ativo')) {
              $andamento = ((int)$c->ativo === 1);
            } elseif (property_exists($c,'status')) {
              $st = strtolower((string)$c->status);
              if (in_array($st, ['finalizado','encerrado','finalizada','encerrada','concluido','concluída'], true)) {
                $andamento = false;
              } else {
                $andamento = in_array($st, ['andamento','em_andamento','em andamento','ativo','aberto'], true);
              }
            }

            $encerradas = false;
            if ($insFim) {
              try {
                $encerradas = Carbon::now()->greaterThan(Carbon::parse($insFim)->endOfDay());
              } catch (\Throwable $e) { $encerradas = false; }
            }

            $semPeriodo = empty($insIni) && empty($insFim);
          @endphp

          <article class="card">
            <a href="{{ $url }}">
              <div class="cover">
                @if($img)
                  <img src="{{ $img }}" alt="Imagem do concurso {{ $c->titulo }}">
                @else
                  <img src="{{ asset('images/placeholder-16x9.png') }}" alt="">
                @endif
              </div>

              <div class="body">
                <div class="title">{{ $c->titulo }}</div>

                @if($editalNum !== '')
                  <div class="line">Edital nº <strong>{{ $editalNum }}</strong></div>
                @endif

                @if($provaData)
                  <div class="line">Aplicação da prova objetiva: <strong>{{ $provaData }}</strong></div>
                @endif

                @if($insIni || $insFim)
                  <div class="line">
                    Inscrições
                    @if($insIni && $insFim)
                      de <strong>{{ $fmt($insIni) }}</strong> a <strong>{{ $fmt($insFim) }}</strong>
                    @elseif($insIni && !$insFim)
                      a partir de <strong>{{ $fmt($insIni) }}</strong>
                    @elseif(!$insIni && $insFim)
                      até <strong>{{ $fmt($insFim) }}</strong>
                    @endif
                  </div>
                @endif
              </div>

              <div class="footerbar">
                @if($isOpen)
                  <div class="badge-open">Inscrições abertas</div>
                @elseif(($encerradas && $andamento) || $semPeriodo)
                  <div class="badge-info">Saiba mais</div>
                @else
                  <div class="badge-closed">Inscrições encerradas</div>
                @endif

                <div class="vagas" aria-label="Total de vagas do concurso">
                  <span class="num">{{ number_format((int)$vagasTotal, 0, ',', '.') }}</span>
                  <span class="lbl">vagas</span>
                </div>
              </div>
            </a>
          </article>
        @endforeach
      </div>

      <div style="margin:4px 0 28px">
        {{ $concursos->onEachSide(1)->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
