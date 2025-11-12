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
  if ($vagasLocais->isEmpty() && $vagasRaw->count() > 0) {
    $probe = $vagasRaw->first();
    if (isset($probe->local_nome) || isset($probe->local) || isset($probe->localidade) || isset($probe->localidade_id)) {
      $vagasLocais = $vagasRaw;
    }
  }

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
        'nivel'  => $first->nivel ?? null,
        'codigo' => $first->codigo ?? null,
      ];
    })->values();
  }

  /*
   * ===== Link do botão "Inscrição Online" =====
   * Agora sempre tenta ir DIRETO para a tela de nova inscrição,
   * já com o concurso atual (concurso_id) na query.
   */
  if (Route::has('candidato.inscricoes.create') && isset($concurso->id)) {
    // /candidato/inscricoes/nova?concurso_id=8 (por exemplo)
    $inscricaoUrl = route('candidato.inscricoes.create', ['concurso_id' => $concurso->id]);
  } elseif (Route::has('candidato.login')) {
    // fallback: manda para login da área do candidato
    $inscricaoUrl = route('candidato.login');
  } else {
    // fallback genérico
    $inscricaoUrl = url('/candidato/login');
  }

  /**
   * === Resolver link público do anexo ===
   * Ordem:
   * 1) href vindo do controller;
   * 2) LINK externo (http/https);
   * 3) ARQUIVO local => rota curta /anexos/{concurso}/{filename};
   * 4) Sem fallback por ID.
   */
  $anexoLink = function ($ax) use ($concurso, $resolvePublicUrl) {

    // 1) href vindo do controller
    $fromController = (string)($ax->href ?? '');
    if ($fromController !== '') {
      return $fromController;
    }

    // 2) Se for link explícito (externo)
    $tipo   = Str::lower((string)($ax->tipo ?? ''));
    $urlRaw = null;
    foreach (['url','link_url','href','arquivo_url','file_url'] as $k) {
      if (!empty($ax->{$k})) { $urlRaw = (string)$ax->{$k}; break; }
    }
    if (($urlRaw && Str::startsWith($urlRaw, ['http://','https://'])) || in_array($tipo, ['link','url'], true)) {
      return $urlRaw ?: '#';
    }

    // 3) Arquivo local => gerar rota curta por filename
    $rawPath = null;
    foreach (['arquivo_path','arquivo','path','file','filepath','storage_path','original_name','nome_arquivo'] as $k) {
      if (!empty($ax->{$k})) { $rawPath = (string)$ax->{$k}; break; }
    }
    if ($rawPath) {
      $p = str_replace('\\','/',$rawPath);
      $p = ltrim($p,'/');
      foreach (['storage/','public/','app/public/'] as $prefix) {
        if (Str::startsWith($p, $prefix)) { $p = substr($p, strlen($prefix)); break; }
      }
      $filename = rawurlencode(basename($p));
      if ($filename && isset($concurso->id) && Route::has('site.anexos.file')) {
        return route('site.anexos.file', ['concurso' => $concurso->id, 'arquivo' => $filename]);
      }
      return $resolvePublicUrl($rawPath) ?? '#';
    }

    return '#';
  };

  // ===== Classificação do anexo (LINK x ARQUIVO e rótulo do tipo) =====
  $classifyAnexo = function ($ax, string $href) {
    if (($ax->is_pdf ?? false) === true) {
      return ['file', 'PDF'];
    }
    if (($ax->is_link ?? false) === true && !Str::contains($href, ['/anexos/','/media/','/storage/','/concursos/'])) {
      return ['link', 'LINK'];
    }

    $mime = Str::lower((string)($ax->mime ?? $ax->mimetype ?? $ax->content_type ?? ''));

    $cands = [];
    foreach ([
      'arquivo','path','file','filename','filepath','storage_path',
      'url','href','download_url','original_name','original','nome_arquivo'
    ] as $k) {
      if (!empty($ax->{$k})) $cands[] = (string)$ax->{$k};
    }
    if (!empty($href)) $cands[] = $href;

    $qs = parse_url($href, PHP_URL_QUERY);
    if ($qs) {
      parse_str($qs, $q);
      foreach (['path','file','filename','f'] as $param) {
        if (!empty($q[$param])) $cands[] = (string)$q[$param];
      }
    }

    $ext = '';
    foreach ($cands as $c) {
      $pathOnly = parse_url($c, PHP_URL_PATH) ?? $c;
      $e = Str::lower(pathinfo($pathOnly, PATHINFO_EXTENSION));
      if ($e) { $ext = $e; break; }
    }

    $isPdf = ($ext === 'pdf') || Str::contains($mime, 'pdf') || (isset($ax->tipo) && Str::lower((string)$ax->tipo) === 'pdf');
    if ($isPdf) return ['file', 'PDF'];

    $isExplicitLink = (isset($ax->is_link) && ($ax->is_link === true || (int)$ax->is_link === 1))
                   || (isset($ax->tipo) && in_array(Str::lower((string)$ax->tipo), ['link','url'], true));

    $hasFileField = false;
    foreach (['arquivo','path','file','filename','storage_path'] as $k) {
      if (!empty($ax->{$k})) { $hasFileField = true; break; }
    }

    $hrefHost = parse_url($href, PHP_URL_HOST);
    $appHost  = request()->getHost();
    $isInternal = !$hrefHost || $hrefHost === $appHost
               || Str::contains($href, ['/anexos/', '/media', '/storage', '/concursos/']);

    if ($isExplicitLink && !$hasFileField && !$isInternal) {
      return ['link', 'LINK'];
    }

    if (in_array($ext, ['doc','docx']) || Str::contains($mime, ['msword','word']))                 return ['file','DOC'];
    if (in_array($ext, ['xls','xlsx','csv']) || Str::contains($mime, ['excel','sheet']))           return ['file','XLS'];
    if (in_array($ext, ['ppt','pptx']))                                                             return ['file','PPT'];
    if (in_array($ext, ['zip','rar','7z']))                                                         return ['file','ZIP'];
    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp']) || Str::contains($mime, 'image'))  return ['file','IMG'];

    return ['file', 'ARQ'];
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
  $isInscricaoAberta = null;
  foreach (['inscricao_aberta','inscricoes_aberta','inscricoes_abertas'] as $flag) {
    if (isset($concurso->{$flag})) { $isInscricaoAberta = (bool)$concurso->{$flag}; break; }
  }
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

  /* ======= Publicações: agrupa por 'grupo' e insere separador de texto ======= */
  $grouped = $anexos->groupBy(function($ax){
    $g = trim((string)($ax->grupo ?? ''));
    return $g !== '' ? $g : 'Outros'; // <-- ajuste aqui se quiser outro nome
  });

  // Ordena grupos por nome natural (padrão)
  $groupNames = $grouped->keys()->sort(SORT_NATURAL|SORT_FLAG_CASE)->values();

  // Flatten: [separator, item, item, separator, item...]
  $pubFlat = collect();
  foreach ($groupNames as $gn) {
    $pubFlat->push((object)['_sep' => true, 'grupo' => $gn]);
    $items = $grouped->get($gn);

    // ordem amigável: posicao/ordem asc, depois data desc
    $items = $items->sortBy(function($it){
      $pos = (int)($it->posicao ?? $it->ordem ?? 0);
      $dt  = $it->created_at ?? $it->data ?? null;
      $ts  = 0;
      try { if ($dt) $ts = Carbon::parse($dt)->timestamp; } catch (\Throwable $e) {}
      return sprintf('%08d-%010d', $pos, 9999999999 - $ts);
    });

    foreach ($items as $it) $pubFlat->push($it);
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
  .page{ padding:18px 0 36px; font-size:15px; }

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
  .pub-sep{ padding:12px 12px 6px; font-weight:800; color:#0f172a; opacity:.9; }
  .pdf-badge{ font-size:11px; font-weight:800; color:#fff; background:#ef4444; border-radius:6px; padding:5px 7px; }
  .link-badge{ font-size:11px; font-weight:800; color:#fff; background:#3b82f6; border-radius:6px; padding:5px 7px; }
  .file-badge{ font-size:11px; font-weight:800; color:#fff; background:#6b7280; border-radius:6px; padding:5px 7px; }
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

  .cr-badge{ font-size:11px; font-weight:800; color:#fff; background:#6b7280; border-radius:6px; padding:4px 6px; }

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
  @if($pubFlat->count() > 0)
    <div class="section">
      <h2>Publicações</h2>
      <div class="card">
        <div class="pub-head">Arquivos e Links</div>
        <ul class="pub-list">
          @foreach($pubFlat as $ax)
            @if(!empty($ax->_sep))
              <li class="pub-sep">{{ $ax->grupo }}</li>
            @else
              @php
                $href     = $anexoLink($ax);
                $tituloAx = $ax->titulo ?? $ax->nome ?? 'Arquivo';
                $dt       = $ax->created_at ?? $ax->data ?? null;
                [$tipoAx, $labelAx] = $classifyAnexo($ax, $href);
              @endphp

              <li class="pub-item">
                @if($labelAx === 'PDF')
                  <span class="pdf-badge" title="Arquivo PDF">PDF</span>
                @elseif($labelAx === 'LINK')
                  <span class="link-badge" title="Link externo">LINK</span>
                @else
                  <span class="file-badge" title="Arquivo">{{ $labelAx }}</span>
                @endif

                <a href="{{ $href }}" target="_blank" rel="noopener noreferrer">{{ $tituloAx }}</a>
                @if($dt)
                  <span class="date">({{ $fmt($dt) }})</span>
                @endif
              </li>
            @endif
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

  {{-- VAGAS POR LOCALIDADE (quando existir) --}}
  @if($vagasLocais->count() > 0)
    <div class="section">
      <h2>Vagas</h2>
      <div class="card">
        <table class="vagas-table">
          <thead>
            <tr>
              <th>Cargo</th>
              <th>Localidade</th>
              <th style="width:160px;">Qtde.</th>
            </tr>
          </thead>
          <tbody>
            @foreach($vagasLocais as $it)
              @php
                $cargo = $it->cargo_nome ?? $it->cargo ?? $it->nome ?? $it->titulo ?? 'Cargo';
                $local = $it->local_nome ?? $it->local ?? $it->localidade ?? (isset($it->localidade_id) ? ('#'.$it->localidade_id) : '-');
                $qtd   = $it->quantidade ?? $it->qtd_total ?? $it->total ?? $it->vagas ?? $it->qtde ?? $it->qtd ?? null;
                $isCR  = (isset($it->cr) && ((int)$it->cr === 1 || $it->cr === true));
              @endphp
              <tr>
                <td>{{ $cargo }}</td>
                <td>
                  {{ $local }}
                  @if($isCR)
                    <span class="cr-badge" title="Cadastro de Reserva">CR</span>
                  @endif
                </td>
                <td>{{ $qtd !== null ? $qtd : '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection
