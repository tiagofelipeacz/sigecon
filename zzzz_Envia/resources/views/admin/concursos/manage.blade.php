{{-- resources/views/admin/concursos/manage.blade.php --}}
@extends('layouts.sigecon')

@section('title', 'Gerenciar Concurso')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-concursos.css') }}">
@endpush

@section('content')
<div class="gc-concursos">
  <div class="gc-page-head">
    <div>
      <div class="gc-title">Gerenciar: {{ $concurso->titulo ?? $concurso->concurso ?? ('Concurso #'.$concurso->id) }}</div>
      <div class="gc-subtitle">Central de comandos do certame</div>
    </div>
    <div class="gc-head-actions">
      <a href="{{ route('admin.concursos.edit', $concurso) }}" class="gc-btn-ghost">Editar</a>
      <a href="{{ route('admin.concursos.config', $concurso) }}" class="gc-btn-primary">Configurações</a>
    </div>
  </div>

  <div class="gc-cards">
    <div class="gc-card-item">
      <div class="gc-card-head">
        <div class="gc-card-title">Visão Geral</div>
      </div>
      <div class="gc-card-meta">Cliente: {{ optional($concurso->cliente)->nome ?? '—' }}</div>
      <div class="gc-card-meta">Edital: {{ $concurso->edital ?? '—' }}</div>
      <div class="gc-card-meta">Inscrições: {{ ($concurso->data_inscricoes_inicio ?? '—') }} — {{ ($concurso->data_inscricoes_fim ?? '—') }}</div>
    </div>

    <div class="gc-card-item">
      <div class="gc-card-head">
        <div class="gc-card-title">Ações Rápidas</div>
      </div>
      <div class="gc-actions" style="margin-top:8px;">
        <a href="{{ route('admin.concursos.config', $concurso) }}" class="gc-btn gc-btn-primary">Parâmetros</a>
        <a href="{{ route('admin.concursos.edit', $concurso) }}" class="gc-btn gc-btn-ghost">Editar dados</a>
        <a href="#" class="gc-btn gc-btn-ghost">Vagas</a>
        <a href="#" class="gc-btn gc-btn-ghost">Cronograma</a>
      </div>
    </div>
  </div>
</div>
@endsection