@extends('layouts.sigecon')
@section('title', 'Dados Extras - SIGECON')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .table{ width:100%; border-collapse:collapse; }
  .table th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; }
  .chip{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:12px; padding:2px 10px; border:1px solid #e5e7eb; background:#fff; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscricoes'
    ])
  </div>

  <div class="gc-card">
    <div class="gc-body">
      <div class="title mb-2" style="font-weight:700">Dados Extras das Inscrições</div>

      @if (!$temCampos)
        <div class="chip" style="background:#fff7ed; border-color:#fed7aa">
          Para gerenciar campos extras por concurso, crie as tabelas
          <code>inscricoes_campos</code> e <code>inscricoes_campos_valores</code> (enviei os SQL/migrations anteriormente).
        </div>
      @else
        <table class="table">
          <thead>
            <tr>
              <th>Chave</th>
              <th>Rótulo</th>
              <th>Tipo</th>
              <th>Obrigatório</th>
              <th>Ativo</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($campos as $c)
              <tr>
                <td>{{ $c->chave }}</td>
                <td>{{ $c->rotulo }}</td>
                <td>{{ $c->tipo }}</td>
                <td>{{ $c->obrigatorio ? 'Sim' : 'Não' }}</td>
                <td>{{ $c->ativo ? 'Sim' : 'Não' }}</td>
              </tr>
            @empty
              <tr><td colspan="5">Nenhum campo configurado para este concurso.</td></tr>
            @endforelse
          </tbody>
        </table>
      @endif
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons())</script>
@endsection
