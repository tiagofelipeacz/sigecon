{{-- Tabela básica de listagem (com partial para ações por linha) --}}
@php
  // $cols: [['key'=>'titulo','label'=>'Título'], ...]
  // $rows: Collection|Paginator|array
  // $actions_view: string com o caminho do partial para ações (recebe ['row'=>$row])
  $cols = $cols ?? [];
  $rows = $rows ?? [];
  $actions_view = $actions_view ?? null;
@endphp

<div class="card">
  <div class="card-body" style="padding:0;">
    <table class="table" style="width:100%;">
      <thead>
        <tr>
          @foreach($cols as $c)
            <th style="white-space:nowrap;">{{ $c['label'] }}</th>
          @endforeach
          <th style="width:1%; white-space:nowrap; text-align:right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            @foreach($cols as $c)
              <td>{{ data_get($row, $c['key']) }}</td>
            @endforeach
            <td style="text-align:right;">
              @if($actions_view)
                @include($actions_view, ['row' => $row])
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="{{ count($cols)+1 }}">
              <div class="empty">Nenhum registro encontrado.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Paginação, se vier paginator --}}
@if(is_object($rows) && method_exists($rows, 'links'))
  <div class="pagination">{{ $rows->links() }}</div>
@endif
