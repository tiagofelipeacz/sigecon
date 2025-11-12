{{-- resources/views/admin/concursos/vagas/index.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Vagas - SIGECON')

@php
  // Variáveis esperadas do controller:
  // $concurso (Model), $cargos (Collection), $itens (Collection),
  // $niveis (Collection), $tipos (Collection), $hasOrdemItem (bool), $defaults (array)

  $cargos       = $cargos ?? collect();
  $itens        = $itens ?? collect();
  $niveis       = $niveis ?? collect();
  $tipos        = $tipos ?? collect();
  $hasOrdemItem = $hasOrdemItem ?? false;

  $df     = $defaults ?? [];
  $isEdit = !empty($df['cargo_id'] ?? null);
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
  .table-sm thead th, .table-sm tbody td{ padding:6px 8px; font-size:13px; }
  .muted{ color:#6b7280; }
  .mb-2{ margin-bottom:8px; }
  .mb-3{ margin-bottom:12px; }
  .mt-2{ margin-top:8px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:#111827; color:white; border-color:#111827; }
  .btn.danger{ background:#fee2e2; color:#991b1b; border-color:#fecaca; }
  .grid{ display:grid; gap:10px; }
  .g-2{ grid-template-columns: 1fr 1fr; }
  .g-3{ grid-template-columns: 1fr 1fr 1fr; }
  .g-4{ grid-template-columns: 1fr 1fr 1fr 1fr; }
  .g-5{ grid-template-columns: repeat(5, 1fr); }
  .chip{ background:#eef2ff; color:#3730a3; padding:3px 8px; border-radius:999px; font-size:12px; }
  .inline-help{ font-size:12px; color:#6b7280; }
  .hr{ height:1px; background:#f3f4f6; margin:10px 0; }
  .tag{ font-size:12px; color:#6b7280; }
  .repeater-row{ border:1px dashed #e5e7eb; border-radius:10px; padding:10px; }
  .x-scroll{ overflow-x:auto; }
  .w-120{ width:120px; }
  .w-full{ width:100%; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .input-lg{ padding:10px 12px; font-size:15px; }
</style>

<div class="gc-page">
  {{-- Lateral: menu (marca "vagas" ativo) --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'vagas'
    ])
  </div>

  {{-- Conteúdo --}}
  <div class="gc-row-2">

    {{-- NOVO CARGO / EDIÇÃO (com várias localidades) --}}
    <div class="gc-card">
      <div class="gc-body">
        <div class="mb-2" style="font-weight:600">
          {{ $isEdit ? 'Editar Cargo (com Localidades)' : 'Cadastrar Cargo (com Localidades)' }}
        </div>

        <form method="post"
              action="{{ $isEdit
                ? route('admin.concursos.vagas.update', ['concurso' => $concurso->id, 'cargo' => $df['cargo_id']])
                : route('admin.concursos.vagas.store', $concurso->id) }}"
              id="formNovoCargo">
          @csrf
          @if($isEdit)
            @method('PUT')
            <input type="hidden" name="cargo_id" value="{{ $df['cargo_id'] }}">
          @endif

          {{-- Cabeçalho do cargo --}}
          <div class="grid g-3">
            <div>
              <label class="tag">Código do Cargo (opcional)</label>
              <input type="text" name="codigo" maxlength="20" class="input"
                     value="{{ old('codigo', $df['codigo'] ?? '') }}" />
            </div>

            <div>
              <label class="tag">Nível de Escolaridade</label>
              <select name="nivel_id" class="input">
                <option value="">- Selecionar -</option>
                @foreach($niveis as $n)
                  <option value="{{ $n->id }}"
                    @selected((int)old('nivel_id', $df['nivel_id'] ?? 0) === (int)$n->id)>
                    {{ $n->nome }}
                  </option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="tag">Taxa de inscrição (R$)</label>
              <input type="text" name="valor_inscricao" placeholder="Ex.: 85,00" class="input"
                     value="{{ old('valor_inscricao', $df['valor_inscricao'] ?? '') }}" />
            </div>
          </div>

          <div class="mt-2">
            <label class="tag">Nome/Título do Cargo</label>
            <input type="text" name="nome" required class="input input-lg"
                   value="{{ old('nome', $df['nome'] ?? '') }}" />
          </div>

          {{-- Campos adicionais do quadro de cargos --}}
          <div class="grid g-2 mt-2">
            <div>
              <label class="tag">Salário (R$) - opcional</label>
              <input type="text" name="salario" placeholder="Ex.: 2.345,67" class="input"
                     value="{{ old('salario', $df['salario'] ?? '') }}" />
            </div>
            <div>
              <label class="tag">Jornada - opcional</label>
              <input type="text" name="jornada" placeholder="Ex.: 40h semanais" class="input"
                     value="{{ old('jornada', $df['jornada'] ?? '') }}" />
            </div>
          </div>

          <div class="mt-2">
            <label class="tag">Detalhes/Observações do Cargo (opcional)</label>
            <textarea name="detalhes" rows="2" class="input">{{ old('detalhes', $df['detalhes'] ?? '') }}</textarea>
          </div>

          <div class="hr"></div>

          {{-- LOCALIDADES --}}
          <div class="mb-2" style="font-weight:600">Localidades do Cargo</div>
          <div class="inline-help">
            Digite o nome da localidade. Se for Cadastro de Reserva (CR), marque a caixa.
            Se não for CR, informe a quantidade total.
          </div>

          <div id="repLocalidades" class="grid" style="gap:12px">
            @php
              $locaisOld = old('locais', $df['locais'] ?? []);
            @endphp

            @forelse($locaisOld as $i => $loc)
              @php
                $nomeLoc = $loc['local'] ?? '';
                $qtd     = (int)($loc['qtd_total'] ?? 0);
                $cr      = !empty($loc['cr']) ? 1 : 0;
                $map     = $loc['cotas'] ?? [];
              @endphp
              <div class="repeater-row" data-row="{{ $i }}">
                <div class="grid g-3">
                  <div>
                    <label class="tag">Localidade</label>
                    <input type="text" name="locais[{{ $i }}][local]" class="input"
                           value="{{ $nomeLoc }}" placeholder="Digite o nome da localidade" />
                  </div>

                  <div>
                    <label class="tag">Qtd. total de vagas</label>
                    <input type="number" name="locais[{{ $i }}][qtd_total]" value="{{ $qtd }}" min="0" class="input" />
                  </div>

                  <div style="display:flex; align-items:flex-end">
                    <label class="tag" style="width:100%">
                      <input type="checkbox" name="locais[{{ $i }}][cr]" value="1"
                             {{ $cr ? 'checked' : '' }} onchange="toggleCR(this)" />
                      &nbsp;Cadastro de Reserva (CR)
                    </label>
                  </div>
                </div>

                @if($tipos->count())
                  <div class="mt-2">
                    <div class="tag" style="display:block; margin-bottom:6px">Cotas por localidade (opcional)</div>
                    <div class="grid g-4">
                      @foreach($tipos as $t)
                        @php $val = (int) ($map[$t->id] ?? 0); @endphp
                        <div>
                          <label class="tag">{{ $t->nome }}</label>
                          <input type="number" name="locais[{{ $i }}][cotas][{{ $t->id }}]"
                                 value="{{ $val }}" min="0" class="input" />
                        </div>
                      @endforeach
                    </div>
                    <div class="inline-help">
                      A soma das cotas não pode exceder a quantidade total (quando não for CR).
                    </div>
                  </div>
                @endif

                <div class="mt-2" style="display:flex; gap:8px; justify-content:flex-end">
                  <button type="button" class="btn danger" onclick="this.closest('.repeater-row').remove()">
                    <i data-lucide="x"></i> Remover localidade
                  </button>
                </div>
              </div>
            @empty
              {{-- sem locais: JS adiciona a primeira linha --}}
            @endforelse
          </div>

          <div class="mt-2">
            <button type="button" class="btn" id="btnAddLoc">
              <i data-lucide="plus"></i> Adicionar localidade
            </button>
          </div>

          <div class="hr"></div>

          <div>
            <button class="btn primary" type="submit">
              <i data-lucide="save"></i>
              {{ $isEdit ? 'Atualizar Cargo e Localidades' : 'Salvar Cargo e Localidades' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- LISTA DE CARGOS --}}
    <div class="gc-card">
      <div class="gc-body x-scroll">
        <div class="mb-2" style="font-weight:600">Cargos cadastrados</div>

        <table class="table table-sm">
          <thead>
            <tr>
              <th>Código</th>
              <th>Cargo</th>
              <th>Nível</th>
              <th>Localidades (resumo)</th>
              <th class="w-120">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse($cargos as $cg)
              @php
                // monta resumo das localidades a partir de $itens
                $linhasResumo = $itens
                  ->where('cargo_id', $cg->id)
                  ->map(function($it){
                      $nm = $it->local_nome ?: 'Localidade';
                      $q  = (int)($it->quantidade ?? 0);
                      return $nm . ($q ? " ({$q})" : '');
                  })->values()->all();
              @endphp
              <tr>
                <td>{{ $cg->codigo ?? '-' }}</td>
                <td>{{ $cg->nome ?? ('Cargo #'.$cg->id) }}</td>
                <td>{{ $cg->nivel_escolaridade ?? $cg->nivel ?? '-' }}</td>
                <td>
                  @if(empty($linhasResumo))
                    <span class="muted">-</span>
                  @else
                    {!! collect($linhasResumo)->map(fn($t)=>'<span class="chip">'.$t.'</span>')->implode(' ') !!}
                  @endif
                </td>
                <td>
                  <div style="display:flex; gap:8px; flex-wrap:wrap">
                    {{-- Editar cargo --}}
                    <a class="btn"
                       href="{{ route('admin.concursos.vagas.edit', ['concurso' => $concurso->id, 'cargo' => $cg->id]) }}">
                      <i data-lucide="pencil"></i> Editar
                    </a>

                    {{-- Excluir cargo --}}
                    <form method="post"
                          action="{{ route('admin.concursos.vagas.cargos.destroy', ['concurso'=>$concurso->id, 'cargo'=>$cg->id]) }}"
                          onsubmit="return confirm('Remover cargo e todas as suas localidades?')">
                      @csrf @method('delete')
                      <button class="btn danger" type="submit">
                        <i data-lucide="trash-2"></i> Remover
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="muted">Nenhum cargo cadastrado.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- ITENS (Cargo x Localidade) --}}
    <div class="gc-card">
      <div class="gc-body x-scroll">
        <div class="mb-2" style="font-weight:600">Itens de Vaga (Cargo x Localidade)</div>

        @if(!$hasOrdemItem)
          <div class="inline-help" style="margin-bottom:8px; color:#b91c1c;">
            A coluna <strong>ordem</strong> não existe na tabela <code>concursos_vagas_itens</code>.  
            Os itens estão sendo listados apenas por cargo e localidade.
            Se você quiser ordenar manualmente, crie a coluna
            <code>ordem INT NULL</code> nessa tabela (via migration ou SQL).
          </div>
        @endif

        @if($hasOrdemItem)
          {{-- Formulário para reordenar (quando existir coluna ordem) --}}
          <form class="mb-2" method="post" action="{{ route('admin.concursos.vagas.reorder', $concurso) }}">
            @csrf
            <div class="inline-help">Edite os números no campo “Ordem” e salve.</div>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th style="width:80px">Ordem</th>
                  <th>Cargo</th>
                  <th>Localidade</th>
                  <th>Qtd.</th>
                  <th>Jornada</th>
                  <th>Salário</th>
                  <th>Taxa</th>
                  <th class="w-120">Ações</th>
                </tr>
              </thead>
              <tbody>
                @forelse($itens as $it)
                  @php
                    $cargoNome = $it->cargo_nome ?? ('Cargo #'.$it->cargo_id);
                    $locNome   = $it->local_nome ?: 'Localidade';
                  @endphp
                  <tr>
                    <td>
                      <input type="number" name="ordem[{{ $it->id }}]"
                             value="{{ (int)($it->ordem ?? 0) }}"
                             class="input" style="width:70px; text-align:right" />
                    </td>
                    <td>{{ $cargoNome }}</td>
                    <td>{{ $locNome }}</td>
                    <td>{{ (int)($it->quantidade ?? 0) }}</td>
                    <td>{{ $it->jornada ?? '-' }}</td>
                    <td>
                      @php
                        $sal = $it->salario;
                        echo $sal !== null && $sal !== ''
                          ? 'R$ '.number_format((float)$sal, 2, ',', '.')
                          : '-';
                      @endphp
                    </td>
                    <td>
                      @php
                        $tx = $it->valor_inscricao ?? null;
                        echo $tx !== null && $tx !== ''
                          ? 'R$ '.number_format((float)$tx, 2, ',', '.')
                          : '-';
                      @endphp
                    </td>
                    <td>
                      <form method="post"
                            action="{{ route('admin.concursos.vagas.itens.destroy', [$concurso, $it->id]) }}"
                            onsubmit="return confirm('Remover item?')">
                        @csrf @method('delete')
                        <button class="btn danger" type="submit">
                          <i data-lucide="trash-2"></i> Remover
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="muted">Sem itens cadastrados.</td></tr>
                @endforelse
              </tbody>
            </table>

            <div class="mt-2">
              <button class="btn primary" type="submit">
                <i data-lucide="save"></i> Salvar ordem
              </button>
            </div>
          </form>
        @else
          {{-- Versão somente leitura (sem coluna de ordem) --}}
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Cargo</th>
                <th>Localidade</th>
                <th>Qtd.</th>
                <th>Jornada</th>
                <th>Salário</th>
                <th>Taxa</th>
                <th class="w-120">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse($itens as $it)
                @php
                  $cargoNome = $it->cargo_nome ?? ('Cargo #'.$it->cargo_id);
                  $locNome   = $it->local_nome ?: 'Localidade';
                @endphp
                <tr>
                  <td>{{ $cargoNome }}</td>
                  <td>{{ $locNome }}</td>
                  <td>{{ (int)($it->quantidade ?? 0) }}</td>
                  <td>{{ $it->jornada ?? '-' }}</td>
                  <td>
                    @php
                      $sal = $it->salario;
                      echo $sal !== null && $sal !== ''
                        ? 'R$ '.number_format((float)$sal, 2, ',', '.')
                        : '-';
                    @endphp
                  </td>
                  <td>
                    @php
                      $tx = $it->valor_inscricao ?? null;
                      echo $tx !== null && $tx !== ''
                        ? 'R$ '.number_format((float)$tx, 2, ',', '.')
                        : '-';
                    @endphp
                  </td>
                  <td>
                    <form method="post"
                          action="{{ route('admin.concursos.vagas.itens.destroy', [$concurso, $it->id]) }}"
                          onsubmit="return confirm('Remover item?')">
                      @csrf @method('delete')
                      <button class="btn danger" type="submit">
                        <i data-lucide="trash-2"></i> Remover
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="muted">Sem itens cadastrados.</td></tr>
              @endforelse
            </tbody>
          </table>
        @endif

      </div>
    </div>

  </div>
</div>

{{-- Lucide + Script do repeater --}}
@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();

    const rep   = document.getElementById('repLocalidades');
    const btnAdd = document.getElementById('btnAddLoc');

    // Template vazio
    const modelo = (idx) => `
      <div class="repeater-row" data-row="\${idx}">
        <div class="grid g-3">
          <div>
            <label class="tag">Localidade</label>
            <input type="text" name="locais[\${idx}][local]" class="input"
                   placeholder="Digite o nome da localidade" />
          </div>

          <div>
            <label class="tag">Qtd. total de vagas</label>
            <input type="number" name="locais[\${idx}][qtd_total]" value="0" min="0" class="input" />
          </div>

          <div style="display:flex; align-items:flex-end">
            <label class="tag" style="width:100%">
              <input type="checkbox" name="locais[\${idx}][cr]" value="1" onchange="toggleCR(this)" />
              &nbsp;Cadastro de Reserva (CR)
            </label>
          </div>
        </div>

        @if($tipos->count())
        <div class="mt-2">
          <div class="tag" style="display:block; margin-bottom:6px">Cotas por localidade (opcional)</div>
          <div class="grid g-4">
            @foreach($tipos as $t)
              <div>
                <label class="tag">{{ $t->nome }}</label>
                <input type="number" name="locais[\${idx}][cotas][{{ $t->id }}]" value="0" min="0" class="input" />
              </div>
            @endforeach
          </div>
          <div class="inline-help">A soma das cotas não pode exceder a quantidade total (quando não for CR).</div>
        </div>
        @endif

        <div class="mt-2" style="display:flex; gap:8px; justify-content:flex-end">
          <button type="button" class="btn danger" onclick="this.closest('.repeater-row').remove()">
            <i data-lucide="x"></i> Remover localidade
          </button>
        </div>
      </div>
    `;

    let idx = rep.querySelectorAll('.repeater-row').length;

    const addRow = () => {
      rep.insertAdjacentHTML('beforeend', modelo(idx++));
      window.lucide?.createIcons();
    };

    btnAdd?.addEventListener('click', addRow);

    // Se não vierem linhas do servidor (novo cargo), cria a primeira
    if (idx === 0) addRow();

    // Se houver CR marcado inicialmente (edição), aplica bloqueios
    rep.querySelectorAll('input[type="checkbox"][name*="[cr]"]:checked').forEach(chk => toggleCR(chk));
  });

  // Quando marcar CR, desabilita qtd_total e zera cotas
  function toggleCR(checkbox) {
    const row = checkbox.closest('.repeater-row');
    if (!row) return;
    const isCR = checkbox.checked;

    const qtd = row.querySelector('input[name^="locais"][name$="[qtd_total]"]');
    if (qtd) {
      qtd.value = isCR ? 0 : (qtd.value || 0);
      qtd.readOnly = isCR;
    }
    row.querySelectorAll('input[name^="locais"][name*="[cotas]"]').forEach(inp => {
      if (isCR) {
        inp.value = 0;
        inp.readOnly = true;
      } else {
        inp.readOnly = false;
      }
    });
  }
</script>
@endsection
