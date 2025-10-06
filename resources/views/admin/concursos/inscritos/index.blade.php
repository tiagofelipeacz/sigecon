@extends('layouts.sigecon')
@section('title', 'Inscrições - SIGECON')

@php
  // Espera: $concurso, $inscricoes, $statusCounts, $STATUS, $STATUS_LBL, $MODALIDADES, $cargos, $q
@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-row-2  { display:grid; grid-template-columns: 1fr; gap:14px; }

  .table { width:100%; border-collapse: collapse; }
  .table thead th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table tbody td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; vertical-align: top; }

  .muted{ color:#6b7280; }
  .mb-2{ margin-bottom:8px; }
  .mb-3{ margin-bottom:12px; }
  .mt-2{ margin-top:8px; }

  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:#111827; color:white; border-color:#111827; }
  .btn.danger{ background:#fee2e2; color:#991b1b; border-color:#fecaca; }

  .grid{ display:grid; gap:10px; }
  .g-3{ grid-template-columns: 1fr 1fr 1fr; }

  .chip{ background:#eef2ff; color:#3730a3; padding:3px 8px; border-radius:999px; font-size:12px; }
  .w-full{ width:100%; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .x-scroll{ overflow-x:auto; }

  /* toolbar grid */
  .filters{ display:grid; grid-template-columns: 1fr 180px 180px auto; gap:8px; align-items:center; }

  /* modal simples */
  .modal{position:fixed; inset:0; z-index:60; display:none;}
  .modal-backdrop{position:absolute; inset:0; background:#0006}
  .modal-content{position:relative; background:#fff; max-width:520px; margin:12vh auto; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb}
  .modal-header,.modal-footer{padding:12px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb}
  .modal-footer{border-top:1px solid #e5e7eb; border-bottom:none}
  .modal-body{padding:16px}

</style>

<div class="gc-page">
  {{-- Lateral: menu (marca "inscritos" ativo) --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscritos'
    ])
  </div>

  {{-- Conteúdo --}}
  <div class="gc-row-2">

    <div class="gc-card">
      <div class="gc-body">

        <div class="mb-2" style="font-weight:600">Inscritos</div>
        <div class="muted mb-3">{{ $concurso->titulo }}</div>

        {{-- Chips de status --}}
        <div class="mb-3" style="display:flex; gap:8px; flex-wrap:wrap">
          @foreach ($STATUS as $st)
            @php $total = $statusCounts[$st] ?? 0; @endphp
            <span class="chip">{{ $STATUS_LBL[$st] ?? ucfirst(str_replace('_',' ',$st)) }}: <strong>{{ $total }}</strong></span>
          @endforeach
        </div>

        {{-- Toolbar / filtros simples --}}
        <form id="formFiltro" method="GET" action="{{ route('admin.concursos.inscritos.index', $concurso) }}" class="filters mb-3">
          <input type="text" name="q" value="{{ request('q', $q ?? '') }}"
                 class="input" placeholder="Buscar por inscrição, nome, cargo, CPF ou situação" autocomplete="off"/>

          <select name="status" class="input">
            <option value="">- Status -</option>
            @foreach ($STATUS as $st)
              <option value="{{ $st }}" @selected(request('status')===$st)>{{ $STATUS_LBL[$st] }}</option>
            @endforeach
          </select>

          <select name="modalidade" class="input">
            <option value="">- Modalidade -</option>
            @foreach ($MODALIDADES as $m)
              <option value="{{ $m }}" @selected(request('modalidade')===$m)>{{ ucfirst($m) }}</option>
            @endforeach
          </select>

          <div style="display:flex; gap:8px; justify-content:flex-end">
            <button type="button" class="btn" id="btnAdv"><i data-lucide="filter"></i> Avançada</button>

            {{-- Importar e Nova agora funcionam --}}
            <a href="{{ route('admin.concursos.inscritos.import', $concurso) }}" class="btn"><i data-lucide="upload"></i> Importar</a>
            <button type="button" class="btn" id="btnNova"><i data-lucide="plus"></i> Nova</button>

            <button type="submit" class="btn primary"><i data-lucide="search"></i> Filtrar</button>
          </div>
        </form>

        {{-- Busca avançada (painel) --}}
        <div id="advBox" class="gc-card" style="display:none; border-style:dashed">
          <div class="gc-body">
            <div class="mb-2" style="display:flex; align-items:center; justify-content:space-between">
              <strong>Busca avançada</strong>
              <button type="button" class="btn" id="btnAdvClose"><i data-lucide="x"></i> Fechar</button>
            </div>

            <div class="grid g-3">
              <div>
                <label class="muted">Inscrição</label>
                <input class="input" name="adv[inscricao]" form="formFiltro" value="{{ data_get(request('adv'), 'inscricao') }}">
              </div>
              <div>
                <label class="muted">Nome Inscrição</label>
                <input class="input" name="adv[nome_inscricao]" form="formFiltro" value="{{ data_get(request('adv'), 'nome_inscricao') }}">
              </div>
              <div>
                <label class="muted">Nome Candidato</label>
                <input class="input" name="adv[nome_candidato]" form="formFiltro" value="{{ data_get(request('adv'), 'nome_candidato') }}">
              </div>

              <div>
                <label class="muted">CPF</label>
                <input class="input" name="adv[cpf]" form="formFiltro" placeholder="Somente números" value="{{ data_get(request('adv'), 'cpf') }}">
              </div>
              <div>
                <label class="muted">Documento</label>
                <input class="input" name="adv[documento]" form="formFiltro" value="{{ data_get(request('adv'), 'documento') }}">
              </div>
              <div>
                <label class="muted">Nascimento</label>
                <input type="date" class="input" name="adv[nascimento]" form="formFiltro" value="{{ data_get(request('adv'), 'nascimento') }}">
              </div>

              <div>
                <label class="muted">Vaga (cargo)</label>
                <select class="input" name="adv[cargo_id]" form="formFiltro">
                  <option value="">- Todas -</option>
                  @foreach ($cargos as $c)
                    <option value="{{ $c->id }}" @selected(data_get(request('adv'),'cargo_id')==$c->id)>{{ $c->nome }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="muted">Data de Inscrição</label>
                <input type="date" class="input" name="adv[data_inscricao]" form="formFiltro" value="{{ data_get(request('adv'), 'data_inscricao') }}">
              </div>
              <div>
                <label class="muted">Situação</label>
                <select class="input" name="adv[situacao]" form="formFiltro">
                  <option value="">- Todas -</option>
                  @foreach ($STATUS as $st)
                    <option value="{{ $st }}" @selected(data_get(request('adv'), 'situacao')===$st)>{{ $STATUS_LBL[$st] }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="muted">Cidade</label>
                <input class="input" name="adv[cidade]" form="formFiltro" value="{{ data_get(request('adv'), 'cidade') }}">
              </div>
            </div>

            <div class="mt-2" style="display:flex; gap:8px; justify-content:flex-end">
              <a class="btn" href="{{ route('admin.concursos.inscritos.index', $concurso) }}">Limpar</a>
              <button type="submit" form="formFiltro" class="btn primary"><i data-lucide="search"></i> Buscar</button>
            </div>
          </div>
        </div>

        {{-- Lista --}}
        <div class="x-scroll">
          <table class="table">
            <thead>
              <tr>
                <th style="width:120px">Inscrição</th>
                <th>Nome Inscrição</th>
                <th style="width:140px">Nascimento</th>
                <th>Vaga</th>
                <th style="width:170px">Data de Inscrição</th>
                <th style="width:140px">Situação</th>
                <th style="width:90px">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse($inscricoes as $i)
                @php
                  $numeroInscricao = (int)$concurso->sequence_inscricao + (int)$i->id;
                  $nomeInscricao   = $i->nome_inscricao ?? $i->nome_candidato ?? $i->nome ?? '-';
                @endphp
                <tr>
                  <td>{{ $numeroInscricao }}</td>
                  <td>{{ $nomeInscricao }}</td>
                  <td>
                    @if(!empty($i->nascimento))
                      {{ \Illuminate\Support\Carbon::parse($i->nascimento)->format('d/m/Y') }}
                    @else
                      <span class="muted">-</span>
                    @endif
                  </td>
                  <td>{{ $i->cargo_nome ?? '-' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($i->created_at)->format('d/m/Y H:i') }}</td>
                  <td><span class="chip">{{ $STATUS_LBL[$i->status] ?? ucfirst($i->status) }}</span></td>
                  <td>
                    <form method="POST"
                          action="{{ route('admin.concursos.inscritos.destroy', ['concurso'=>$concurso->id, 'inscricao'=>$i->id]) }}"
                          onsubmit="return confirm('Excluir esta inscrição? Esta ação não pode ser desfeita.');">
                      @csrf @method('DELETE')
                      <button class="btn danger" title="Excluir"><i data-lucide="trash-2"></i> Excluir</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="muted" style="text-align:center; padding:20px">Nenhuma inscrição encontrada.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-2">
          {{ $inscricoes->links() }}
        </div>
      </div>
    </div>

  </div>
</div>

{{-- MODAL: CPF → Inscrever --}}
<div id="novaModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="novaTitle">
  <div class="modal-backdrop"></div>
  <div class="modal-content">
    <div class="modal-header" style="display:flex; align-items:center; justify-content:space-between">
      <strong id="novaTitle">Nova inscrição</strong>
      <button class="btn" id="novaClose"><i data-lucide="x"></i> Fechar</button>
    </div>

    <div class="modal-body">
      <form id="cpfForm">
        @csrf
        <label class="muted" for="cpfInput">CPF do candidato</label>
        <input id="cpfInput" name="cpf" class="input" inputmode="numeric" autocomplete="off"
               placeholder="Digite 11 dígitos" maxlength="14" />
        <div class="muted" style="margin-top:6px">
          Digite 11 dígitos. O sistema validará e buscará dados existentes.
        </div>
      </form>

      <div id="cpfInfo" class="mt-2" style="display:none"></div>
    </div>

    <div class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end">
      <button class="btn primary" id="cpfCheckBtn">
        <i data-lucide="check-circle-2"></i> Inscrever
      </button>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();

    const adv = document.getElementById('advBox');
    document.getElementById('btnAdv')?.addEventListener('click', () => {
      adv.style.display = (adv.style.display === 'none' || !adv.style.display) ? 'block' : 'none';
    });
    document.getElementById('btnAdvClose')?.addEventListener('click', () => adv.style.display = 'none');

    // Modal Nova
    const modal = document.getElementById('novaModal');
    const open  = document.getElementById('btnNova');
    const close = document.getElementById('novaClose');
    const backd = modal.querySelector('.modal-backdrop');

    const openModal  = () => { modal.style.display = 'block'; setTimeout(()=>document.getElementById('cpfInput')?.focus(),100); };
    const closeModal = () => { modal.style.display = 'none'; };

    open?.addEventListener('click', openModal);
    close?.addEventListener('click', closeModal);
    backd?.addEventListener('click', closeModal);

    // CPF -> check
    const btnChk  = document.getElementById('cpfCheckBtn');
    const form    = document.getElementById('cpfForm');
    const infoBox = document.getElementById('cpfInfo');
    const input   = document.getElementById('cpfInput');

    // mascara leve (999.999.999-99)
    input?.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\D/g,'').slice(0,11);
      if (v.length > 9)  e.target.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
      else if (v.length > 6) e.target.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
      else if (v.length > 3) e.target.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
      else e.target.value = v;
    });

    const postCheck = async () => {
      const raw = (input.value || '').replace(/\D/g,'');
      infoBox.style.display = 'none';
      infoBox.innerHTML = '';

      if (raw.length !== 11) {
        infoBox.style.display = 'block';
        infoBox.innerHTML = '<span class="chip" style="background:#fef3c7;color:#92400e">Informe 11 dígitos.</span>';
        return;
      }

      btnChk.disabled = true;
      btnChk.innerHTML = '<i data-lucide="loader-2"></i> Verificando...';
      window.lucide?.createIcons();

      try {
        const resp = await fetch(`{{ route('admin.concursos.inscritos.checkCpf', $concurso) }}`, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': form.querySelector('input[name=_token]').value,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ cpf: raw })
        });

        const data = await resp.json();

        if (!resp.ok || data.ok === false) {
          infoBox.style.display = 'block';
          infoBox.innerHTML = '<span class="chip" style="background:#fee2e2;color:#991b1b">CPF inválido. Verifique os 11 dígitos.</span>';
          return;
        }

        if (data.ja_inscrito) {
          infoBox.style.display = 'block';
          infoBox.innerHTML = '<span class="chip" style="background:#fee2e2;color:#991b1b">Este CPF já possui inscrição neste concurso.</span>';
          return;
        }

        // Redireciona para a tela de criação, com params
        const params = new URLSearchParams({ cpf: raw });
        if (data.candidato_id) params.set('candidato_id', data.candidato_id);

        window.location.href = `{{ route('admin.concursos.inscritos.create', $concurso) }}?` + params.toString();
      } catch (e) {
        infoBox.style.display = 'block';
        infoBox.innerHTML = '<span class="chip" style="background:#fee2e2;color:#991b1b">Erro ao validar CPF.</span>';
      } finally {
        btnChk.disabled = false;
        btnChk.innerHTML = '<i data-lucide="check-circle-2"></i> Inscrever';
        window.lucide?.createIcons();
      }
    };

    btnChk?.addEventListener('click', postCheck);
  });
</script>
@endsection
