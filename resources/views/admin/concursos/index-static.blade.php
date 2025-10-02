@extends('layouts.sigecon')
@section('title', 'Processos Seletivos — SIGECON')

@section('content')
  <h1>Processos Seletivos</h1>
  <p class="sub">Gerencie os certames no seu painel</p>

  <div class="toolbar">
    <form class="flex" style="gap:.5rem; width:100%">
      <input type="text" placeholder="Buscar por título, órgão ou código..." style="flex:1">
      <button class="btn" type="button">Buscar</button>
      <a class="btn primary right" href="javascript:void(0)">+ Novo Processo Seletivo</a>
    </form>
  </div>

  <div class="section-title">Certames em Andamento</div>

  <div class="card active">
    <div>
      <div class="actions">
        <a class="btn icon" href="javascript:void(0)">Abrir</a>
        <a class="btn icon" href="javascript:void(0)">Editar</a>
        <a class="btn icon" href="javascript:void(0)">Publicações</a>
        <a class="btn icon" href="javascript:void(0)">Relatórios</a>
      </div>
      <div class="sub">Prefeitura Municipal de Exemplo</div>
      <div class="title">Concurso Público 01/2025 — Nível Médio e Superior</div>
      <div class="meta">
        <span>📅 10/10/2025 – 05/11/2025</span>
        <span>👥 1.234 inscritos</span>
      </div>
    </div>
    <div class="pill">Inscrições Abertas</div>
  </div>

  <div class="card active">
    <div>
      <div class="actions">
        <a class="btn icon" href="javascript:void(0)">Abrir</a>
        <a class="btn icon" href="javascript:void(0)">Editar</a>
        <a class="btn icon" href="javascript:void(0)">Publicações</a>
        <a class="btn icon" href="javascript:void(0)">Relatórios</a>
      </div>
      <div class="sub">Instituto de Gestão Educacional</div>
      <div class="title">Processo Seletivo Simplificado — Professores</div>
      <div class="meta">
        <span>📅 01/10/2025 – 20/10/2025</span>
        <span>👥 642 inscritos</span>
      </div>
    </div>
    <div class="pill">Em Andamento</div>
  </div>

  <div class="section-title">Todos os Processos Seletivos</div>

  @foreach (range(1,3) as $i)
    <div class="card">
      <div>
        <div class="actions">
          <a class="btn icon" href="javascript:void(0)">Abrir</a>
          <a class="btn icon" href="javascript:void(0)">Editar</a>
          <a class="btn icon" href="javascript:void(0)">Publicações</a>
          <a class="btn icon" href="javascript:void(0)">Relatórios</a>
        </div>
        <div class="sub">Órgão {{ $i }} — Governo do Estado</div>
        <div class="title">Concurso {{ 100+$i }}/2025 — Cargo {{ $i }}</div>
        <div class="meta">
          <span>📅 15/09/2025 – 05/10/2025</span>
          <span>👥 {{ 350 + $i*27 }} inscritos</span>
          <span>🗓️ Publicado em 12/09/2025</span>
        </div>
      </div>
      <div class="pill">Publicado</div>
    </div>
  @endforeach

  <div class="pagination">
  </div>
@endsection
