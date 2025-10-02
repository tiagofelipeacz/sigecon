<div class="actions" style="display:inline-flex; gap:.25rem;">
  <a class="btn" href="{{ route('admin.config.condicoes_especiais.edit', $row->id) }}">Editar</a>

  <form method="POST" action="{{ route('admin.config.condicoes_especiais.toggle-ativo', $row->id) }}">
    @csrf @method('PATCH')
    <button class="btn" type="submit">
      {{ (int)$row->ativo === 1 ? 'Desativar' : 'Ativar' }}
    </button>
  </form>

  <form method="POST"
        action="{{ route('admin.config.condicoes_especiais.destroy', $row->id) }}"
        onsubmit="return confirm('Excluir \"{{ $row->titulo }}\"?');">
    @csrf @method('DELETE')
    <button class="btn danger" type="submit">Excluir</button>
  </form>
</div>
