@extends('layouts.sigecon')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold mb-4">
        Vagas — Concurso #{{ $concurso->id }}
    </h1>

    @if (session('success'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid md:grid-cols-12 gap-6">
        <div class="md:col-span-7">
            <div class="rounded border bg-white p-4">
                <h2 class="font-semibold mb-3">Lista de vagas</h2>
                @if($vagas->isEmpty())
                    <p class="text-gray-600 text-sm">Nenhuma vaga cadastrada.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600">
                                    <th class="px-3 py-2 text-left">Cargo</th>
                                    <th class="px-3 py-2 text-left">Nível</th>
                                    <th class="px-3 py-2 text-right">Vagas</th>
                                    <th class="px-3 py-2 text-right">Salário</th>
                                    <th class="px-3 py-2 text-right">Taxa</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vagas as $v)
                                    @php
                                        $total = (int)($v->vagas_ac ?? 0) + (int)($v->vagas_pcd ?? 0) + (int)($v->vagas_negros ?? 0);
                                    @endphp
                                    <tr class="border-t">
                                        <td class="px-3 py-2">{{ $v->titulo }}</td>
                                        <td class="px-3 py-2">{{ $v->nivel }}</td>
                                        <td class="px-3 py-2 text-right">{{ $total }}</td>
                                        <td class="px-3 py-2 text-right">
                                            {{ $v->salario !== null ? 'R$ '.number_format($v->salario,2,',','.') : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            {{ $v->taxa_formatada ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('admin.concursos.vagas.edit', [$concurso, $v]) }}" class="text-primary-700 hover:underline">Editar</a>
                                            <form method="POST" action="{{ route('admin.concursos.vagas.destroy', [$concurso, $v]) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:underline ml-2" onclick="return confirm('Excluir vaga?')">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="md:col-span-5">
            <div class="rounded border bg-white p-4">
                <h2 class="font-semibold mb-3">Nova vaga</h2>
                <form method="POST" action="{{ route('admin.concursos.vagas.store', $concurso) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Cargo *</label>
                        <input type="text" name="titulo" class="w-full rounded border-slate-300" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Nível</label>
                            <input type="text" name="nivel" class="w-full rounded border-slate-300" placeholder="ex.: Superior">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Lotação</label>
                            <input type="text" name="lotacao" class="w-full rounded border-slate-300">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Jornada</label>
                            <input type="text" name="jornada" class="w-full rounded border-slate-300" placeholder="ex.: 40h">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Salário</label>
                            <input type="text" name="salario" class="w-full rounded border-slate-300" placeholder="ex.: 4500,00">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Vagas AC</label>
                            <input type="number" name="vagas_ac" min="0" class="w-full rounded border-slate-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">PcD</label>
                            <input type="number" name="vagas_pcd" min="0" class="w-full rounded border-slate-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Negros</label>
                            <input type="number" name="vagas_negros" min="0" class="w-full rounded border-slate-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Taxa de inscrição</label>
                        <input type="text" name="taxa" class="w-full rounded border-slate-300" placeholder="ex.: 85,00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Requisitos</label>
                        <textarea name="requisitos" rows="3" class="w-full rounded border-slate-300"></textarea>
                    </div>

                    <div class="pt-2">
                        <button class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">Salvar</button>
                        <a href="{{ route('admin.concursos.config', $concurso) }}" class="px-4 py-2 rounded border ml-2">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
