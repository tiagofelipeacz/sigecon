{{-- resources/views/admin/concursos/anexos/form-fields.blade.php --}}
@php
  use Illuminate\Support\Facades\Storage;

  $isEdit = isset($anexo) && ($anexo->id ?? null);
  $action = $isEdit
      ? route('admin.concursos.anexos.update', ['concurso'=>$concurso->id, 'anexo'=>$anexo->id])
      : route('admin.concursos.anexos.store',  $concurso->id);

  $titulo   = old('titulo',   $anexo->titulo   ?? '');
  $grupo    = old('grupo',    $anexo->grupo    ?? '');
  $posicao  = old('posicao',  $anexo->posicao  ?? 0);
  $legenda  = old('legenda',  $anexo->legenda  ?? '');
  $tipo     = old('tipo',     $anexo->tipo     ?? 'arquivo'); // 'arquivo' | 'link'
  $linkUrl  = old('link_url', $anexo->link_url ?? '');

  $ativo    = (int) old('ativo',    isset($anexo) ? (int)($anexo->ativo ?? 1) : 1);
  $pubEm    = old('publicado_em', optional($anexo->publicado_em ?? now())->format('Y-m-d\TH:i'));

  // AJUSTE: padrão = 1 (Sim). Antes estava 0.
  $indet    = (int) old('tempo_indeterminado', isset($anexo) ? (int)($anexo->tempo_indeterminado ?? 1) : 1);

  $visDe    = old('visivel_de', optional($anexo->visivel_de)->format('Y-m-d\TH:i'));
  $visAte   = old('visivel_ate', optional($anexo->visivel_ate)->format('Y-m-d\TH:i'));

  $restrito = (int) old('restrito', isset($anexo) ? (int)($anexo->restrito ?? 0) : 0);
  $restCgs  = (array) old('restrito_cargos', isset($anexo) ? (array)($anexo->restrito_cargos ?? []) : []);

  $arquivoAtualPath = $anexo->arquivo ?? $anexo->path ?? null;
  $arquivoAtualUrl = null;
  if ($arquivoAtualPath) {
    try { $arquivoAtualUrl = Storage::url($arquivoAtualPath); } catch (\Throwable $e) { $arquivoAtualUrl = null; }
  }
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" id="formAnexo" class="grid" style="gap:12px">
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- Título (linha própria) --}}
  <div>
    <label class="tag">Título *</label>
    <input type="text" name="titulo" class="input" required maxlength="180" value="{{ $titulo }}" />
  </div>

  {{-- Grupo (abaixo do título, não na mesma linha) --}}
  <div>
    <label class="tag">Grupo (opcional)</label>
    <div style="display:grid; gap:6px">
      @if(!empty($grupos ?? []))
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
          <select id="selGrupoExistente" class="input" style="max-width:320px">
            <option value="">— Selecionar grupo —</option>
            @foreach($grupos as $g)
              @php $g = trim((string)$g); @endphp
              @if($g !== '')
                <option value="{{ $g }}" @selected($g === $grupo)>{{ $g }}</option>
              @endif
            @endforeach
            <option value="__custom__">Outro (digitar abaixo)</option>
          </select>
          <button type="button" id="btnLimparGrupo" class="btn sm" title="Limpar">Limpar</button>
        </div>
        <div class="inline-help">Escolha um grupo existente ou digite um novo abaixo.</div>
      @endif

      <input
        type="text"
        id="inpGrupo"
        name="grupo"
        class="input"
        maxlength="60"
        placeholder="Ex.: Edital, Resultado..."
        value="{{ $grupo }}"
      />
    </div>
  </div>

  <div class="grid g-3">
    <div>
      <label class="tag">Tipo de publicação</label>
      <select name="tipo" id="selTipo" class="input">
        <option value="arquivo" @selected($tipo==='arquivo')>Arquivo</option>
        <option value="link"    @selected($tipo==='link')>Link externo</option>
      </select>
    </div>
    <div>
      <label class="tag">Posição/Ordem</label>
      <input type="number" name="posicao" class="input" value="{{ (int)$posicao }}" min="0" />
    </div>
    <div>
      <label class="tag">Ativo</label>
      <select name="ativo" class="input">
        <option value="1" @selected($ativo===1)>Sim</option>
        <option value="0" @selected($ativo===0)>Não</option>
      </select>
    </div>
  </div>

  <div>
    <label class="tag">Legenda (opcional)</label>
    <input type="text" name="legenda" class="input" maxlength="255" value="{{ $legenda }}" />
  </div>

  {{-- Arquivo ou Link --}}
  <div id="boxArquivo" style="{{ $tipo==='arquivo' ? '' : 'display:none' }}">
    <label class="tag">Arquivo (PDF, DOCX, XLSX, JPG, PNG, etc.)</label>
    <input type="file" name="arquivo" class="input" @if(!$isEdit) required @endif />
    @if($arquivoAtualUrl)
      <div class="inline-help mt-2">
        Atual: <a href="{{ $arquivoAtualUrl }}" target="_blank" rel="noopener">{{ basename($arquivoAtualPath) }}</a>
      </div>
    @endif
  </div>

  <div id="boxLink" style="{{ $tipo==='link' ? '' : 'display:none' }}">
    <label class="tag">URL</label>
    <input type="url" name="link_url" class="input" placeholder="https://..." value="{{ $linkUrl }}" />
  </div>

  {{-- Período de visibilidade --}}
  <div class="grid g-3">
    <div>
      <label class="tag">Publicado em</label>
      <input type="datetime-local" name="publicado_em" value="{{ $pubEm }}" class="input" />
    </div>
    <div>
      <label class="tag">Tempo indeterminado?</label>
      <select name="tempo_indeterminado" id="selIndeterminado" class="input">
        <option value="0" @selected($indet===0)>Não</option>
        <option value="1" @selected($indet===1)>Sim</option>
      </select>
    </div>
    <div>
      {{-- espaçador --}}
    </div>
  </div>

  <div id="boxPeriodo" class="grid g-2" style="{{ $indet ? 'display:none' : '' }}">
    <div>
      <label class="tag">Visível de</label>
      {{-- AJUSTE: required quando NÃO indeterminado --}}
      <input type="datetime-local" id="inpVisDe" name="visivel_de" value="{{ $visDe }}" class="input" {{ $indet ? '' : 'required' }} />
    </div>
    <div>
      <label class="tag">Visível até</label>
      {{-- AJUSTE: required quando NÃO indeterminado --}}
      <input type="datetime-local" id="inpVisAte" name="visivel_ate" value="{{ $visAte }}" class="input" {{ $indet ? '' : 'required' }} />
    </div>
  </div>

  {{-- Restrição por cargo (opcional) --}}
  @if(!empty($cargos ?? []))
    <div>
      <label class="tag">Restrito a cargos específicos?</label>
      <div>
        <label style="display:inline-flex; gap:8px; align-items:center">
          <input type="checkbox" name="restrito" value="1" id="chkRestrito" {{ $restrito ? 'checked' : '' }} />
          <span>Sim, restringir</span>
        </label>
      </div>
      <div id="boxCargos" class="mt-2" style="{{ $restrito ? '' : 'display:none' }}">
        <div class="inline-help">Selecione os cargos que poderão ver este anexo.</div>
        <div class="grid g-3" style="max-height:220px; overflow:auto; border:1px dashed #e5e7eb; padding:10px; border-radius:10px">
          @foreach($cargos as $cg)
            <label style="display:flex; align-items:center; gap:8px">
              <input type="checkbox" name="restrito_cargos[]" value="{{ $cg->id }}"
                {{ in_array((int)$cg->id, array_map('intval', $restCgs ?? [])) ? 'checked' : '' }}>
              <span>{{ $cg->nome }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  <div>
    <button class="btn primary" type="submit">
      <i data-lucide="save"></i> {{ $isEdit ? 'Salvar alterações' : 'Publicar anexo' }}
    </button>
    <a class="btn" href="{{ route('admin.concursos.anexos.index', $concurso) }}">
      <i data-lucide="list"></i> Voltar à lista
    </a>
  </div>
</form>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();
    const selTipo = document.getElementById('selTipo');
    const boxArq  = document.getElementById('boxArquivo');
    const boxLnk  = document.getElementById('boxLink');

    const selInd  = document.getElementById('selIndeterminado');
    const boxPer  = document.getElementById('boxPeriodo');
    const inpDe   = document.getElementById('inpVisDe');
    const inpAte  = document.getElementById('inpVisAte');

    const chkRes  = document.getElementById('chkRestrito');
    const boxCg   = document.getElementById('boxCargos');

    const toggleTipo = () => {
      const v = selTipo?.value || 'arquivo';
      if (v === 'link') { boxArq.style.display='none'; boxLnk.style.display='block'; }
      else              { boxArq.style.display='block'; boxLnk.style.display='none'; }
    };
    const toggleInd = () => {
      const v = selInd?.value || '1'; // '1' = Sim (indeterminado)
      const naoIndeterminado = (v === '0');
      boxPer.style.display = naoIndeterminado ? 'grid' : 'none';
      if (inpDe)  inpDe.required  = naoIndeterminado;
      if (inpAte) inpAte.required = naoIndeterminado;
      // opcional: ao voltar para indeterminado, limpamos os campos
      if (!naoIndeterminado) {
        if (inpDe)  inpDe.value  = '';
        if (inpAte) inpAte.value = '';
      }
    };
    const toggleRes = () => {
      if (!chkRes || !boxCg) return;
      boxCg.style.display = chkRes.checked ? 'block' : 'none';
    };

    selTipo?.addEventListener('change', toggleTipo);
    selInd?.addEventListener('change', toggleInd);
    chkRes?.addEventListener('change', toggleRes);

    toggleTipo(); toggleInd(); toggleRes();

    // ===== Grupo (select + input)
    const selGrupo = document.getElementById('selGrupoExistente');
    const inpGrupo = document.getElementById('inpGrupo');
    const btnLimparGrupo = document.getElementById('btnLimparGrupo');

    if (selGrupo && inpGrupo) {
      const syncFromSelect = () => {
        const v = selGrupo.value;
        if (v === '__custom__') {
          inpGrupo.focus();
        } else if (v === '') {
          inpGrupo.value = '';
          inpGrupo.focus();
        } else {
          inpGrupo.value = v;
        }
      };
      selGrupo.addEventListener('change', syncFromSelect);

      // Pré-seleciona no select quando o valor atual do input existe na lista
      try {
        if (
          inpGrupo.value &&
          selGrupo.querySelector(`option[value="${CSS.escape(inpGrupo.value)}"]`)
        ) {
          selGrupo.value = inpGrupo.value;
        }
      } catch(e) {
        // fallback simples sem CSS.escape (navegador muito antigo)
        const opts = Array.from(selGrupo.options || []);
        const found = opts.find(o => o.value === inpGrupo.value);
        if (found) selGrupo.value = inpGrupo.value;
      }

      if (btnLimparGrupo) {
        btnLimparGrupo.addEventListener('click', () => {
          inpGrupo.value = '';
          selGrupo.value = '';
          inpGrupo.focus();
        });
      }
    }
  });
</script>
