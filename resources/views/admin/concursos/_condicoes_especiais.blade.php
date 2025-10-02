@section('content')
{{-- Pedidos de condições especiais --}}
@php
    use Illuminate\Support\Facades\DB;
    $flagCond = (int) old('configs.flag_condicoes_especiais', data_get($concurso, 'configs.flag_condicoes_especiais', 0));
    $tiposCE = DB::table('tipos_condicao_especial')->orderBy('titulo')->get();
    $selecionadosCE = DB::table('concurso_tipo_condicao_especial')
        ->where('concurso_id', $concurso->id)
        ->pluck('tipo_condicao_especial_id')->toArray();
@endphp

<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Pedidos de condições especiais</h2>

    <div class="mb-3">
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Permitir Solicitar Condições Especiais para Realização da Prova:
        </label>
        <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2">
                <input type="radio" name="configs[flag_condicoes_especiais]" value="1" {{ $flagCond === 1 ? 'checked' : '' }}>
                <span class="text-sm">Sim</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="radio" name="configs[flag_condicoes_especiais]" value="0" {{ $flagCond === 0 ? 'checked' : '' }}>
                <span class="text-sm">Não</span>
            </label>
        </div>
    </div>

    <div id="condicoesFields">
        <label for="condicoesEspeciaisSelect" class="block text-sm font-medium text-slate-700 mb-1">Condições Especiais a serem listadas:</label>
        <select id="condicoesEspeciaisSelect" name="condicoes_especiais[]" multiple placeholder="Selecione ..." class="w-full rounded-md border-slate-300">
            @foreach($tiposCE as $t)
                <option value="{{ $t->id }}" {{ in_array($t->id, $selecionadosCE) ? 'selected' : '' }}>
                    {{ $t->titulo }}{{ $t->grupo ? ' — ' . $t->grupo : '' }}
                </option>
            @endforeach
        </select>
    </div>
</div>

@once
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@endonce

<script>
(function () {
  var ts = null;
  function mountTS(){
    var el = document.getElementById('condicoesEspeciaisSelect');
    if (!el) return;
    ts = new TomSelect(el, { plugins: ['remove_button'], maxItems: null, create: false, persist: false, placeholder: 'Selecione ...' });
  }
  function toggleFields(){
    var on = document.querySelector('input[name="configs[flag_condicoes_especiais]"]:checked')?.value === '1';
    var wrap = document.getElementById('condicoesFields');
    if (!wrap) return;
    wrap.querySelectorAll('select').forEach(function(el){ el.disabled = !on; });
    if (ts) on ? ts.enable() : ts.disable();
  }
  document.addEventListener('DOMContentLoaded', function(){ mountTS(); toggleFields(); });
  document.addEventListener('change', function(e){
    if (e.target && e.target.name === 'configs[flag_condicoes_especiais]') toggleFields();
  });
})();
</script>

@endsection
