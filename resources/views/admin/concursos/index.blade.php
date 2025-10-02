@extends('layouts.sigecon')
@section('title', 'Gerenciar Processos - SIGECON')

@section('content')
@php
    use Illuminate\Support\Str;

    // Filtros (com fallback para request)
    $q            = $q            ?? request('q','');
    $statusFilter = $status       ?? request('status',''); // não colidir com status por linha
    $client_id    = $client_id    ?? request('client_id','');
    $order        = $order        ?? request('order','published_desc');

    // Link "+ Novo Processo"
    $url_create = \Route::has('admin.concursos.create')
        ? route('admin.concursos.create')
        : url('/admin/concursos/create');
@endphp

<h1>Gerenciar Processos</h1>
<p class="sub">Controle central de todos os concursos.</p>

<div class="toolbar" style="display:flex; gap:.5rem; align-items:center; margin-bottom:10px;">
  <form method="get" class="flex" style="gap:.5rem; flex:1;">
    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por título, órgão, cidade, edital…" style="flex:1">

    <select name="status" class="btn" style="min-width:180px">
      <option value="">Status: Todos</option>
      <option value="abertas"    @selected($statusFilter==='abertas')>Inscrições abertas</option>
      <option value="embreve"    @selected($statusFilter==='embreve')>Em breve</option>
      <option value="encerradas" @selected($statusFilter==='encerradas')>Encerradas</option>
      <option value="publicado"  @selected($statusFilter==='publicado')>Publicado</option>
      <option value="rascunho"   @selected($statusFilter==='rascunho')>Rascunho</option>
    </select>

    <select name="client_id" class="btn" style="min-width:220px">
      <option value="">Cliente: Todos</option>
      @foreach(($clientes ?? []) as $cl)
        <option value="{{ $cl->id }}" @selected((string)$client_id===(string)$cl->id)>{{ $cl->nome ?? $cl->name }}</option>
      @endforeach
    </select>

    <select name="order" class="btn" style="min-width:220px">
      <option value="published_desc" @selected($order==='published_desc' || $order==='')>Publicação (↓)</option>
      <option value="published_asc"  @selected($order==='published_asc')>Publicação (↑)</option>
      <option value="inicio_desc"    @selected($order==='inicio_desc')>Início inscrições (↓)</option>
      <option value="inicio_asc"     @selected($order==='inicio_asc')>Início inscrições (↑)</option>
      <option value="fim_desc"       @selected($order==='fim_desc')>Fim inscrições (↓)</option>
      <option value="fim_asc"        @selected($order==='fim_asc')>Fim inscrições (↑)</option>
      <option value="titulo_asc"     @selected($order==='titulo_asc')>Título (A–Z)</option>
      <option value="titulo_desc"    @selected($order==='titulo_desc')>Título (Z–A)</option>
    </select>

    <button class="btn" type="submit">Buscar</button>
    <a class="btn" href="{{ route('admin.concursos.index') }}">Limpar</a>
  </form>

  <a class="btn primary" href="{{ $url_create }}">+ Novo Processo</a>
</div>

<style>
  .tbl-min{ width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
  .tbl-min table{ width:100%; border-collapse:collapse; }
  .tbl-min thead th{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; text-align:left; font-weight:600; padding:10px 12px; }
  .tbl-min tbody td{ border-top:1px solid #f1f5f9; padding:10px 12px; vertical-align:middle; }
  .tbl-min .nowrap{ white-space:nowrap; }
  .pill{ display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.8rem; border:1px solid transparent; }
  .pill.ok{ background:#e8faf0; color:#166534; border-color:#bbf7d0; }
  .pill.nok{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
  .pill.info{ background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
  .title a{ font-weight:600; text-decoration:none; }
  .title a:hover{ text-decoration:underline; }
  .subtle{ color:#6b7280; font-size:.85rem; }
  .btn.smol{ padding:.25rem .5rem; font-size:.85rem; }
  .btn.danger{ background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
  .btn.danger:hover{ background:#fecaca; }
  @media (max-width: 980px){
    .tbl-min table, .tbl-min thead, .tbl-min tbody, .tbl-min th, .tbl-min td, .tbl-min tr{ display:block; }
    .tbl-min thead{ display:none; }
    .tbl-min tbody td{ border:none; border-top:1px solid #f1f5f9; }
  }
</style>

<div class="tbl-min">
  <table>
    <thead>
      <tr>
        <th style="width:70px">ID</th>
        <th style="width:160px">Tipo</th>
        <th style="width:420px">Título</th> {{-- largura aumentada --}}
        <th style="width:220px">Legenda (Interno)</th>
        <th style="width:110px">Edital</th>
        <th style="width:220px">Cliente</th>
        <th style="width:140px">Situação</th>
        {{-- coluna "Início inscrições" removida --}}
        <th style="width:90px">Ativo</th>
        <th style="width:200px" class="nowrap">Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($concursos as $c)
        @php
            // Fallbacks por linha
            $cli    = $c->cliente_nome ?? optional($c->cliente)->nome ?? optional($c->cliente)->name ?? '-';
            $edital = $c->numero_edital ?? $c->edital ?? '-';
            $tipo   = $c->tipo ?? $c->tipo_concurso ?? 'Concurso Público';

            $ini   = $c->inscricao_inicio ? \Illuminate\Support\Carbon::parse($c->inscricao_inicio) : null;
            $fim   = $c->inscricao_fim ? \Illuminate\Support\Carbon::parse($c->inscricao_fim) : null;
            $agora = now();

            // Status calculado sem ternários encadeados
            $statusLabel = $c->status_exibicao ?? null;
            if ($statusLabel === null) {
                if ($ini && $fim && $agora->between($ini, $fim)) {
                    $statusLabel = 'Inscrições Abertas';
                } elseif ($ini && $agora->lt($ini)) {
                    $statusLabel = 'Em Breve';
                } elseif ($fim && $agora->gt($fim)) {
                    $statusLabel = 'Encerradas';
                } elseif (!empty($c->published_at)) {
                    $statusLabel = 'Publicado';
                } else {
                    $statusLabel = 'Rascunho';
                }
            }

            $titulo = $c->titulo ?: ('Concurso #'.$c->id);

            // URLs essenciais
            $urlSite = $c->url_show_public
                ?? (\Route::has('site.concursos.show') ? route('site.concursos.show',$c) : url('/concursos/'.$c->id));

            if (isset($c->url_edit)) {
                $urlEdit = $c->url_edit;
            } elseif (\Route::has('admin.concursos.config')) {
                $urlEdit = route('admin.concursos.config',$c);
            } elseif (\Route::has('admin.concursos.edit')) {
                $urlEdit = route('admin.concursos.edit',$c);
            } else {
                $urlEdit = url('/admin/concursos/'.$c->id.'/config');
            }

            // Rota de exclusão (fallback)
            if (\Route::has('admin.concursos.destroy')) {
                $urlDestroy = route('admin.concursos.destroy',$c);
            } else {
                $urlDestroy = url('/admin/concursos/'.$c->id);
            }

            $ativo = (int)($c->ativo ?? 0) === 1;

            $cls = match ($statusLabel) {
                'Inscrições Abertas'      => 'ok',
                'Em Breve', 'Publicado'   => 'info',
                'Encerradas', 'Rascunho'  => 'nok',
                default                   => 'info',
            };
        @endphp
        <tr>
          <td>#{{ $c->id }}</td>
          <td>{{ $tipo }}</td>
          <td>
            <div class="title">
              <a href="{{ $urlSite }}" target="_blank" rel="noopener">
                {{ Str::limit($titulo, 160) }}
              </a>
            </div>
            @if(!empty($c->subtitulo) || !empty($c->legenda))
              <div class="subtle">{{ $c->subtitulo ?? $c->legenda }}</div>
            @endif
          </td>
          <td>{{ $c->legenda ?? $c->subtitulo ?? '-' }}</td>
          <td>{{ $edital }}</td>
          <td>{{ $cli }}</td>
          <td><span class="pill {{ $cls }}">{{ $statusLabel }}</span></td>
          {{-- célula "Início inscrições" removida --}}
          <td>{!! $ativo ? '<span class="pill ok">Sim</span>' : '<span class="pill nok">Não</span>' !!}</td>
          <td class="nowrap">
            <div class="toolbar" style="margin-top:16px; display:flex; gap:12px">
              <a class="btn smol" href="{{ $urlEdit }}">Editar</a>

              <form method="POST"
                    action="{{ $urlDestroy }}"
                    onsubmit="return confirm('Tem certeza que deseja excluir {{ addslashes($titulo) }}?\nEsta ação não pode ser desfeita.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn smol danger">Excluir</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="9">Nenhum processo encontrado.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="pagination" style="margin-top:12px;">
  {{ $concursos->links() }}
</div>
@endsection
