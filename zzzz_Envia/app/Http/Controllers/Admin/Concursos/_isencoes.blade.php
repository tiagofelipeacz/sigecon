{{-- Quadro: Pedidos de Isenção (lê seus tipos do menu Configurações) --}}
@php
    use Illuminate\Support\Facades\DB;

    // Tipos cadastrados em /admin/config/pedidos-isencao
    $tiposDisponiveis = DB::table('tipos_isencao')
        ->orderBy('titulo')
        ->get();

    // Tipos já vinculados a este concurso
    $selecionados = DB::table('concurso_tipo_isencao')
        ->where('concurso_id', $concurso->id)
        ->pluck('tipo_isencao_id')
        ->toArray();
@endphp

<div class="bg-white border rounded-lg p-4">
    <h2 class="text-lg font-semibold mb-3">Pedidos de Isenção</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.concursos.config.isencoes.salvar', $concurso->id) }}">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($tiposDisponiveis as $tipo)
                <label class="border rounded-lg p-3 flex items-start gap-2">
                    <input type="checkbox" name="tipos_isencao[]" value="{{ $tipo->id }}"
                        {{ in_array($tipo->id, $selecionados) ? 'checked' : '' }}>
                    <span>
                        <span class="font-medium">{{ $tipo->titulo }}</span>
                        @if(!empty($tipo->descricao))
                            <div class="text-sm text-slate-600">{{ $tipo->descricao }}</div>
                        @endif
                        @if(isset($tipo->ativo) && !$tipo->ativo)
                            <div class="text-xs text-red-600 mt-1">Inativo</div>
                        @endif
                    </span>
                </label>
            @empty
                <div class="col-span-1 text-slate-500">
                    Nenhum tipo de isenção cadastrado em <em>Configurações &gt; Pedidos de Isenção</em>.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            <button class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">
                Salvar seleção
            </button>
        </div>
    </form>
</div>
