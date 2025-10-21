{{-- resources/views/site/concursos/show.blade.php --}}
@extends('layouts.site')
@section('title', $concurso->titulo ?? 'Concurso')

@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;

  // ===== Helpers =====
  $fmt = function ($d) {
    if (empty($d)) return null;
    try { return Carbon::parse($d)->format('d/m/Y'); } catch (\Throwable $e) { return null; }
  };

  // Caminho/URL -> URL pública
  $resolvePublicUrl = function (?string $p): ?string {
    if (!$p) return null;
    $p = trim(str_replace('\\','/',$p));
    if ($p === '') return null;

    if (Str::startsWith($p, ['http://','https://','data:image'])) return $p;
    if (Str::startsWith($p, ['/storage/','storage/'])) return asset(ltrim($p,'/'));

    $norm = ltrim($p,'/');
    if (Str::startsWith($norm, 'public/')) return asset('storage/'.substr($norm,7));
    if (file_exists(public_path($p)))               return asset($p);
    if (file_exists(public_path($norm)))            return asset($norm);
    if (file_exists(public_path('storage/'.$norm))) return asset('storage/'.$norm);
    return asset('storage/'.$norm);
  };

  // ===== Logo (vários campos planos do $concurso) =====
  $logoCand = null;
  foreach ([
    'logo_url','logo_path','logo','imagem','card_image',
    'cl_logo_path','cl_logo','cliente_logo','cliente_logo_path',
    'hero_image'
  ] as $k) {
    if (!empty($concurso->{$k})) { $logoCand = (string)$concurso->{$k}; break; }
  }
  $logoUrl = $resolvePublicUrl($logoCand);

  // ===== Campos principais =====
  $titulo     = $concurso->titulo ?? $concurso->nome ?? 'Concurso';
  $editalNum  = trim((string)($concurso->edital_num ?? $concurso->edital ?? ''));
  // Procura vários nomes possíveis para as datas de inscrição
  $insIniRaw  =
      $concurso->inscricoes_ini
   ?? $concurso->inscricoes_inicio
   ?? $concurso->inscricao_ini
   ?? $concurso->inicio_inscricoes
   ?? $concurso->dt_inicio_inscricoes
   ?? $concurso->insc_inicio
   ?? null;

  $insFimRaw  =
      $concurso->inscricoes_fim
   ?? $concurso->inscricao_fim
   ?? $concurso->fim_inscricoes
   ?? $concurso->dt_fim_inscricoes
   ?? $concurso->insc_fim
   ?? null;

  $isenIni    = $concurso->isencao_ini    ?? $concurso->isencoes_ini ?? null;
  $isenFim    = $concurso->isencao_fim    ?? $concurso->isencoes_fim ?? null;

  // Situação (rótulo informativo)
  $situacao = 'Em andamento';
  if (isset($concurso->ativo)) {
    $situacao = ((int)$concurso->ativo === 1) ? 'Em andamento' : 'Finalizado';
  } elseif (isset($concurso->status)) {
    $st = Str::lower((string)$concurso->status);
    if (in_array($st, ['finalizado','encerrado','finalizada','encerrada','concluido','concluída'], true)) $situacao = 'Finalizado';
    elseif (in_array($st, ['suspenso','suspensa'], true)) $situacao = 'Suspenso';
  }

  // Coleções (podem vir do controller com qualquer nome)
  $anexos       = collect($anexos       ?? $concurso->anexos        ?? []);
  $cronograma   = collect($cronograma   ?? $concurso->cronograma    ?? $concurso->cronogramas ?? []);
  $vagasRaw     = collect($vagas        ?? $concurso->vagas         ?? []);
  $vagasLocais  = collect($vagas_locais ?? $concurso->vagas_locais  ?? []);

  // ===== Fallbacks inteligentes para Vagas =====
  // 1) Se não veio $vagas_locais, mas $vagas contém itens com localidade, usa $vagas como $vagasLocais
  if ($vagasLocais->isEmpty() && $vagasRaw->count() > 0) {
    $probe = $vagasRaw->first();
    if (isset($probe->local_nome) || isset($probe->local) || isset($probe->localidade) || isset($probe->localidade_id)) {
      $vagasLocais = $vagasRaw;
    }
  }

  // 2) Gera um resumo por cargo se $vagasRaw estiver vazio mas há localidades
  $vagasResumo = $vagasRaw;
  if ($vagasResumo->isEmpty() && $vagasLocais->isNotEmpty()) {
    $vagasResumo = $vagasLocais->groupBy(function($it){
      return $it->cargo_nome ?? $it->cargo ?? $it->nome ?? $it->titulo ?? 'Cargo';
    })->map(function($grp){
      $first = $grp->first();
      $sum = 0;
      foreach ($grp as $it) {
        $q = $it->quantidade ?? $it->qtd_total ?? $it->total ?? $it->vagas ?? $it->qtde ?? $it->qtd ?? 0;
        $sum += (int)$q;
      }
      return (object)[
        'cargo'  => $first->cargo_nome ?? $first->cargo ?? $first->nome ?? $first->titulo ?? 'Cargo',
        'vagas'  => $sum,
        'nivel'  => $first->nivel ?? $first->nivel_escolaridade ?? null,
        'codigo' => $first->codigo ?? null,
      ];
    })->values();
  }

  // 3) Agrupa localidades por cargo para exibição detalhada (public)
  $cargosLocais = [];
  foreach ($vagasLocais as $it) {
    $cid = isset($it->cargo_id) ? (int)$it->cargo_id : null;
    $chave = $cid ? ('id:'.$cid) : ($it->cargo_nome ?? $it->cargo ?? $it->nome ?? $it->titulo ?? 'Cargo');
    if (!isset($cargosLocais[$chave])) {
      $cargosLocais[$chave] = (object)[
        'cargo_id' => $cid,
        'cargo'    => $it->cargo_nome ?? $it->cargo ?? $it->nome ?? $it->titulo ?? 'Cargo',
        'codigo'   => $it->codigo ?? null,
        'nivel'    => $it->nivel ?? $it->nivel_escolaridade ?? null,
        'salario'  => $it->salario ?? null,
        'jornada'  => $it->jornada ?? null,
        'taxa'     => $it->valor_inscricao ?? $it->taxa ?? null,
        'itens'    => [],
        'total'    => 0,
      ];
    }
    $local  = $it->local_nome ?? $it->local ?? $it->localidade ?? (isset($it->localidade_id) ? ('#'.$it->localidade_id) : '-');
    $qtd    = $it->quantidade ?? $it->qtd_total ?? $it->total ?? $it->vagas ?? $it->qtde ?? $it->qtd ?? null;
    $isCR   = (isset($it->cr) && ((int)$it->cr === 1 || $it->cr === true));
    $cargosLocais[$chave]->itens[] = (object)[
      'local' => $local,
      'qtd'   => $qtd !== null ? (int)$qtd : null,
      'cr'    => $isCR,
    ];
    if (!$isCR && $qtd !== null) {
      $cargosLocais[$chave]->total += (int)$qtd;
    }
  }
  $cargosLocais = collect($cargosLocais)->values();

  // Link do botão "Inscrição Online"
  if (Route::has('candidato.inscricoes.cargos') && isset($concurso->id)) {
    $inscricaoUrl = route('candidato.inscricoes.cargos', $concurso->id);
  } elseif (Route::has('candidato.inscricoes.create')) {
    $inscricaoUrl = route('candidato.inscricoes.create', ['concurso' => $concurso->id ?? null]);
  } elseif (Route::has('candidato.login')) {
    $inscricaoUrl = route('candidato.login');
  } else {
    $inscricaoUrl = url('/candidato/login');
  }

  // Resolver link de anexo
  $anexoLink = function ($ax) use ($resolvePublicUrl, $concurso) {
    foreach (['url','arquivo_url','file_url','href'] as $k) {
      if (!empty($ax->{$k})) return (string)$ax->{$k};
    }
    foreach (['path','arquivo','file','filepath','storage_path'] as $k) {
      if (!empty($ax->{$k})) return $resolvePublicUrl($ax->{$k});
    }
    if (!empty($ax->path))    return route('media.public', ['path' => $ax->path]);
    if (!empty($ax->arquivo)) return route('media.public', ['path' => $ax->arquivo]);

    if (Route::has('admin.concursos.anexos.open') && isset($concurso->id, $ax->id)) {
      return route('admin.concursos.anexos.open', ['concurso' => $concurso->id, 'anexo' => $ax->id]);
    }
    return '#';
  };

  // Informações importantes
  $infoHtml = $concurso->infos_importantes_html
           ?? $concurso->informacoes_html
           ?? $concurso->observacoes_html
           ?? null;
  $infoText = $concurso->infos_importantes
           ?? $concurso->informacoes
           ?? $concurso->observacoes
           ?? null;

  // ===== Inscrição aberta? =====
  // 1) Se o controller já mandou um booleano, respeitamos:
  $isInscricaoAberta = null;
  foreach (['inscricao_aberta','inscricoes_aberta','inscricoes_abertas'] as $flag) {
    if (isset($concurso->{$flag})) { $isInscricaoAberta = (bool)$concurso->{$flag}; break; }
  }

  // 2) Caso contrário, calculamos somente se recebermos pelo menos uma das datas
  if ($isInscricaoAberta === null) {
    $isInscricaoAberta = false;
    if ($insIniRaw || $insFimRaw) {
      try {
        $ini = $insIniRaw ? Carbon::parse($insIniRaw)->startOfDay() : null;
        $fim = $insFimRaw ? Carbon::parse($insFimRaw)->endOfDay()   : null;
        $now = Carbon::now();

        if     ($ini && $fim)  $isInscricaoAberta = $now->between($ini, $fim);
        elseif ($ini && !$fim) $isInscricaoAberta = $now->greaterThanOrEqualTo($ini);
        elseif (!$ini && $fim) $isInscricaoAberta = $now->lessThanOrEqualTo($fim);
      } catch (\Throwable $e) { $isInscricaoAberta = false; }
    }
  }
@endphp

@section('content')
<style>
  :root{
    --c-primary: #252d42ff;
    --c-muted:   #6b7280;
    --c-border:  #e5e7eb;
    --c-bg:      #f8fafc;
  }
  .container{ max-width:1100px; margin:0 auto; padding:0 16px; }
  .page{ padding:18px 0 36px; font-size:15px; } /* Fonte base um pouco menor */

  /* Topo */
  .capa{
    display:grid;
    grid-template-columns: 160px 1fr;
    gap:32px;
    align-items:start;
  }
  .capa-content{ padding-top:6px; }
  .kicker{
    color:#6b7280; font-weight:700; text-transform:uppercase;
    font-size:11px; letter-spacing:.08em; margin:4px 0 10px; display:inline-block;
  }
  .logo-box{
    width:160px; height:160px; border-radius:16px; background:#fff;
    border:1px solid var(--c-border); display:flex; align-items:center; justify-content:center;
    overflow:hidden;
  }
  .logo-box img{ width:100%; height:100%; object-fit:contain; }

  .capa h1{ margin:0 0 10px; font-size:30px; line-height:1.15; letter-spacing:-.01em; }
  .meta{ display:grid; gap:8px; margin-top:10px; font-size:14px; }
  .meta .row{ display:flex; gap:8px; flex-wrap:wrap; }
  .key{ color:var(--c-muted); font-weight:700; }
  .value{ color:#0f172a; }

  .btn-apply{
    display:inline-block; margin:14px 0 6px; background:#16a34a; color:#fff;
    padding:10px 16px; border-radius:10px; font-weight:800; border:none; text-decoration:none;
  }
  .btn-apply:hover{ filter:brightness(1.06); }

  /* Blocos */
  .section{ margin-top:26px; }
  .section h2{ font-size:22px; margin:0 0 10px; letter-spacing:-.01em; }
  .card{ background:#fff; border:1px solid var(--c-border); border-radius:12px; overflow:hidden; font-size:14px; }

  /* Publicações */
  .pub-head{ background:var(--c-primary); color:#fff; padding:10px 12px; font-weight:800; font-size:14px; }
  .pub-list{ list-style:none; margin:0; padding:0; }
  .pub-item{ display:flex; align-items:center; gap:10px; padding:10px 12px; border-top:1px solid var(--c-border); }
  .pdf-badge{ font-size:11px; font-weight:800; color:#fff; background:#ef4444; border-radius:6px; padding:5px 7px; }
  .link-badge{ font-size:11px; font-weight:800; color:#fff; background:#3b82f6; border-radius:6px; padding:5px 7px; }
  .pub-item a{ color:#0f172a; text-decoration:none; font-size:14px; }
  .pub-item .date{ color:var(--c-muted); font-size:11px; margin-left:6px; }

  /* Tabelas */
  .cron-table, .vagas-table{ width:100%; border-collapse:collapse; font-size:14px; }
  .cron-table th, .cron-table td,
  .vagas-table th, .vagas-table td{ padding:10px 8px; border-top:1px solid var(--c-border); }
  .cron-table thead th,
  .vagas-table thead th{ background:var(--c-primary); color:#fff; text-align:left; font-weight:800; }

  .info-block{ background:var(--c-bg); border:1px solid var(--c-border); border-radius:12px; padding:12px; font-size:14px; }
  .info-block h3{ margin:0 0 8px; font-size:16px; }
  .info-block ul{ margin:8px 0 0 18px; }
  .info-block li{ margin:6px 0; }

  /* CR badge */
  .cr-badge{ font-size:11px; font-weight:800; color:#fff; background:#6b7280; border-radius:6px; padding:4px 6px; }

  /* Subcards de cargos/localidades */
  .subcard{ border-top:1px solid var(--c-border); padding:10px 12px; }
  .cargo-head{ display:flex; gap:12px; flex-wrap:wrap; align-items:baseline; }
  .cargo-head .name{ font-weight:800; }
  .cargo-meta{ color:#6b7280; font-size:12px; display:flex; gap:10px; flex-wrap:wrap; }
  .muted{ color:#6b7280; }

  /* CORREÇÃO: botão "Área do Candidato" (no header) não fica branco no hover) */
  .site-header .btn.primary:hover,
  .menu .btn.primary:hover,
  .btn.primary:hover{
    background: var(--site-accent);
    border-color: var(--site-accent);
    color:#fff;
    filter: brightness(1.05);
  }

  @media (max-width: 760px){
    .capa{ grid-template-columns: 1fr; gap:16px; }
    .logo-box{ width:120px; height:120px; }
    .capa-content{ padding-top:0; }
    .capa h1{ font-size:26px; }
    .section h2{ font-size:20px; }
  }
</style>

<div class="page container">
  {{-- CAPA --}}
  <div class="capa">
    <div class="logo-box" aria-label="Logo do concurso">
      @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo">
      @else
        <img src="{{ asset('images/placeholder-1x1.png') }}" alt="">
      @endif
    </div>

    <div class="capa-content">
      <span class="kicker">Concurso Público</span>
      <h1>{{ $titulo }}</h1>

      <div class="meta">
        @if($editalNum !== '')
          <div class="row"><span class="key">Edital:</span> <span class="value">{{ $editalNum }}</span></div>
        @endif

        @if($insIniRaw || $insFimRaw)
          <div class="row"><span class="key">Inscrições:</span>
            <span class="value">
              @if($insIniRaw && $insFimRaw)
                {{ $fmt($insIniRaw) }} a {{ $fmt($insFimRaw) }}
              @elseif($insIniRaw && !$insFimRaw)
                a partir de {{ $fmt($insIniRaw) }}
              @elseif(!$insIniRaw && $insFimRaw)
                até {{ $fmt($insFimRaw) }}
              @endif
            </span>
          </div>
        @endif

        @if($isenIni || $isenFim)
          <div class="row"><span class="key">Pedidos de isenção:</span>
            <span class="value">
              @if($isenIni && $isenFim)
                {{ $fmt($isenIni) }} a {{ $fmt($isenFim) }}
              @elseif($isenIni && !$isenFim)
                a partir de {{ $fmt($isenIni) }}
              @elseif(!$isenIni && $isenFim)
                até {{ $fmt($isenFim) }}
              @endif
            </span>
          </div>
        @endif

        <div class="row"><span class="key">Situação:</span> <span class="value">{{ $situacao }}</span></div>
      </div>

      {{-- Botão aparece somente se a inscrição estiver aberta --}}
      @if($isInscricaoAberta === true)
        <a href="{{ $inscricaoUrl }}" class="btn-apply">INSCRIÇÃO ONLINE</a>
      @endif
    </div>
  </div>

  {{-- Informações importantes --}}
  @if($infoHtml || $infoText)
    <div class="section">
      <div class="info-block">
        <h3>Informações importantes</h3>
        @if($infoHtml)
          {!! $infoHtml !!}
        @elseif($infoText)
          @php $lines = preg_split('/\r\n|\r|\n/', trim((string)$infoText)); @endphp
          <ul>
            @foreach($lines as $line)
              @if(trim($line) !== '')
                <li>{{ $line }}</li>
              @endif
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  @endif

  {{-- PUBLICAÇÕES --}}
  @if($anexos->count() > 0)
    <div class="section">
      <h2>Publicações</h2>
      <div class="card">
        <div class="pub-head">Edital de Abertura</div>
        <ul class="pub-list">
          @foreach($anexos as $ax)
            @php
              $href     = $anexoLink($ax);
              $tituloAx = $ax->titulo ?? $ax->nome ?? 'Arquivo';
              $dt       = $ax->created_at ?? $ax->data ?? null;

              // ===== DETECÇÃO ROBUSTA DE PDF =====
              $mime = Str::lower((string)($ax->mime ?? $ax->mimetype ?? ''));

              // candidatos a caminho/arquivo para inspecionar a extensão
              $candidates = [];
              foreach (['arquivo','path','file','filename','arquivo_url','file_url','url','href'] as $cand) {
                if (!empty($ax->{$cand})) $candidates[] = (string)$ax->{$cand};
              }
              if (!empty($href)) $candidates[] = $href;

              // se a URL tiver params (ex.: /media?path=storage/edital.pdf), checa também
              $q = [];
              $queryStr = parse_url($href, PHP_URL_QUERY);
              if ($queryStr) {
                parse_str($queryStr, $q);
                foreach (['path','file','filename','f'] as $param) {
                  if (!empty($q[$param])) $candidates[] = (string)$q[$param];
                }
              }

              // olha extensão de todos os candidatos
              $extIsPdf = false;
              foreach ($candidates as $c) {
                $pathOnly = parse_url($c, PHP_URL_PATH) ?? $c;
                $ext = Str::lower(pathinfo($pathOnly, PATHINFO_EXTENSION));
                if ($ext === 'pdf') { $extIsPdf = true; break; }
              }

              $isPdf = (Str::contains($mime, 'pdf') || $extIsPdf || (($ax->tipo ?? null) === 'pdf') || (($ax->is_pdf ?? null) === true));
            @endphp

            <li class="pub-item">
              @if($isPdf)
                <span class="pdf-badge" title="Arquivo PDF">PDF</span>
              @else
                <span class="link-badge" title="Link">LINK</span>
              @endif
              <a href="{{ $href }}" target="_blank" rel="noopener">{{ $tituloAx }}</a>
              @if($dt)
                <span class="date">({{ $fmt($dt) }})</span>
              @endif
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  {{-- CRONOGRAMA --}}
  @if($cronograma->count() > 0)
    <div class="section">
      <h2>Cronograma</h2>
      <div class="card">
        <table class="cron-table">
          <thead>
            <tr>
              <th style="width:160px;">Data</th>
              <th>Evento</th>
            </tr>
          </thead>
          <tbody>
            @foreach($cronograma as $it)
              @php
                $data = $fmt($it->data ?? $it->quando ?? null);
                $tituloEv = $it->titulo ?? $it->evento ?? $it->descricao ?? 'Evento';
              @endphp
              <tr>
                <td>{{ $data ?? '-' }}</td>
                <td>{{ $tituloEv }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  {{-- VAGAS (RESUMO POR CARGO) --}}
  @if($vagasResumo->count() > 0)
    <div class="section">
      <h2>Vagas</h2>
      <div class="card">
        <table class="vagas-table">
          <thead>
            <tr>
              <th>Vaga</th>
              <th style="width:280px;">Qtde.</th>
            </tr>
          </thead>
          <tbody>
            @foreach($vagasResumo as $v)
              @php
                $nome  = $v->cargo ?? $v->nome ?? $v->titulo ?? 'Cargo';
                $qtd   = $v->quantidade ?? $v->qtd ?? $v->qtde ?? $v->total ?? $v->vagas ?? null;
                $qtdTxt = $v->qtd_text ?? $v->descricao_qtd ?? $v->quantidade_texto ?? null;
              @endphp
              <tr>
                <td>{{ $nome }}</td>
                <td>{{ $qtdTxt ? $qtdTxt : ($qtd !== null ? $qtd : '-') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  {{-- VAGAS POR LOCALIDADE (agrupado por cargo) --}}
  @if($cargosLocais->count() > 0)
    <div class="section">
      <h2>Vagas por Localidade</h2>
      <div class="card">
        @foreach($cargosLocais as $cg)
          <div class="subcard">
            <div class="cargo-head">
              <div class="name">{{ $cg->cargo }}</div>
              <div class="cargo-meta">
                @if($cg->codigo) <span>Código: {{ $cg->codigo }}</span>@endif
                @if($cg->nivel)  <span>Nível: {{ $cg->nivel }}</span>@endif
                @if($cg->jornada) <span>Jornada: {{ $cg->jornada }}</span>@endif
                @if($cg->salario !== null && $cg->salario !== '')
                  <span>Salário: R$ {{ number_format((float)$cg->salario, 2, ',', '.') }}</span>
                @endif
                @if($cg->taxa !== null && $cg->taxa !== '')
                  <span>Taxa: R$ {{ number_format((float)$cg->taxa, 2, ',', '.') }}</span>
                @endif
              </div>
            </div>

            <div class="muted" style="margin:6px 0 8px">
              Total (sem CR): <strong>{{ (int)$cg->total }}</strong>
            </div>

            <table class="vagas-table">
              <thead>
                <tr>
                  <th>Localidade</th>
                  <th style="width:160px;">Qtde.</th>
                </tr>
              </thead>
              <tbody>
                @foreach($cg->itens as $it)
                  <tr>
                    <td>
                      {{ $it->local }}
                      @if($it->cr)
                        <span class="cr-badge" title="Cadastro de Reserva">CR</span>
                      @endif
                    </td>
                    <td>{{ $it->qtd !== null ? $it->qtd : '-' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endforeach
      </div>
    </div>
  @endif
</div>
@endsection
