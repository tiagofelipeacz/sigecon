@extends('layouts.sigecon')

@section('title', 'Processos Seletivos - SIGECON')

@section('content')
@php
  $q         = $q         ?? ($filtros['q']         ?? '');
  $status    = $status    ?? ($filtros['status']    ?? '');
  $client_id = $client_id ?? ($filtros['client_id'] ?? '');
  $order     = $order     ?? ($filtros['order']     ?? 'published_desc');
@endphp

  <h1>Processos Seletivos</h1>
  <p class="sub">Gerencie os certames no seu painel</p>

  <div class="toolbar">
    <form method="get" class="flex" style="gap:.5rem; width:100%">
      <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por título, órgão, cidade ou edital..." style="flex:1">

      <select name="status" class="btn" style="min-width: 180px">
        <option value="">Status: Todos</option>
        <option value="abertas"   @selected($status==='abertas')>Inscrições Abertas</option>
        <option value="embreve"   @selected($status==='embreve')>Em Breve</option>
        <option value="encerradas"@selected($status==='encerradas')>Encerradas</option>
        <option value="publicado" @selected($status==='publicado')>Publicado</option>
        <option value="rascunho"  @selected($status==='rascunho')>Rascunho</option>
      </select>

      <select name="client_id" class="btn" style="min-width: 220px">
        <option value="">Cliente: Todos</option>
        @foreach ($clientes as $cl)
          <option value="{{ $cl->id }}" @selected($client_id==$cl->id)>{{ $cl->nome }}</option>
        @endforeach
      </select>

      <select name="order" class="btn" style="min-width: 220px">
        <option value="published_desc" @selected($order==='published_desc' || $order==='')>Ordenar: Publicação (↓)</option>
        <option value="published_asc"  @selected($order==='published_asc')>Publicação (↑)</option>
        <option value="inicio_desc"    @selected($order==='inicio_desc')>Início Inscrições (↓)</option>
        <option value="inicio_asc"     @selected($order==='inicio_asc')>Início Inscrições (↑)</option>
        <option value="fim_desc"       @selected($order==='fim_desc')>Fim Inscrições (↓)</option>
        <option value="fim_asc"        @selected($order==='fim_asc')>Fim Inscrições (↑)</option>
        <option value="titulo_asc"     @selected($order==='titulo_asc')>Título (A–Z)</option>
        <option value="titulo_desc"    @selected($order==='titulo_desc')>Título (Z–A)</option>
      </select>

      <button class="btn" type="submit">Aplicar</button>
      <a class="btn" href="{{ route('admin.inicio') }}">Limpar</a>

      {{-- Botão criar com "vida" (route fallback inteligente) --}}
      <a class="btn right primary" href="{{ $url_create }}">+ Novo Processo Seletivo</a>
    </form>
  </div>

  @forelse ($concursos as $c)
    <div class="card {{ $c->status_exibicao === 'Inscrições Abertas' ? 'active' : '' }}">
      <div>
        <div class="actions">
          <a class="btn icon" href="{{ url('/concursos/'.$c->id) }}">Abrir</a>
          <a class="btn icon" href="{{ route('admin.concursos.config', $c->id) }}">Editar</a>
          <a class="btn icon" href="{{ url('/admin/concursos/'.$c->id.'/anexos') }}">Abrir anexos</a>
          <a class="btn icon" href="{{ url('/admin/concursos/'.$c->id.'/relatorios') }}">Relatórios</a>
        </div>

        <div class="sub">{{ $c->cliente ?? '- Órgão/Cliente -' }}</div>
        <div class="title">
          {{ $c->titulo }}
          @if(!empty($c->numero_edital))
            - Edital {{ $c->numero_edital }}
          @endif
        </div>
        <div class="meta">
          <span>📅 {{ $c->periodo }}</span>
          @if(!empty($c->cidade) || !empty($c->estado))
            <span>📍 {{ trim(($c->cidade ?? '').' '.(($c->estado ?? '') ? '/ '.$c->estado : '')) }}</span>
          @endif
          @if(isset($tem_inscricoes) && $tem_inscricoes && isset($c->inscritos_total))
            <span>👥 {{ number_format((int)$c->inscritos_total, 0, ',', '.') }} inscritos</span>
          @endif
          <span>🗓️ Publicado em {{ optional($c->created_at)->format('d/m/Y') }}</span>
        </div>
      </div>
      <div class="pill">{{ $c->status_exibicao }}</div>
    </div>
  @empty
    <div class="empty">Nenhum processo seletivo encontrado.</div>
  @endforelse

  <div class="pagination">
    {{ $paginator->links() }}
  </div>
@endsection


