<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $clients = Client::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('cliente', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('cidade', 'like', "%{$q}%")
                        ->orWhere('estado', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Compat: expõe nas duas chaves
        return view('admin.clientes.index', [
            'clients'  => $clients,
            'clientes' => $clients,
            'q'        => $q,
        ]);
    }

    public function create()
    {
        $client = new Client();
        $ufs    = $this->ufs();

        // Compat: 'client' e 'cliente'
        return view('admin.clientes.create', [
            'client'  => $client,
            'cliente' => $client,
            'ufs'     => $ufs,
        ]);
    }

    /**
     * IMPORTANTE: usar o mesmo nome do parâmetro da rota {clientes}
     * para o binding funcionar (edit/ update/ destroy).
     */
    public function edit(Client $clientes)
    {
        $client = $clientes; // alias p/ blades
        $ufs    = $this->ufs();

        return view('admin.clientes.edit', compact('client', 'ufs'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($data['action']);

        $client = Client::create($data);

        return $this->redirectAfterAction($request->input('action'), $client);
    }

    public function update(UpdateClientRequest $request, Client $clientes): RedirectResponse
    {
        $client = $clientes;
        $data   = $request->validated();

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                Storage::disk('public')->delete($client->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($data['action']);

        $client->update($data);

        return $this->redirectAfterAction($request->input('action'), $client, true);
    }

    public function destroy(Client $clientes): RedirectResponse
    {
        $client = $clientes;

        if ($client->logo_path) {
            Storage::disk('public')->delete($client->logo_path);
        }

        $client->delete();

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente excluído com sucesso.');
    }

    private function redirectAfterAction(?string $action, Client $client, bool $updated = false): RedirectResponse
    {
        $msg = $updated ? 'Cliente atualizado com sucesso.' : 'Cliente criado com sucesso.';

        switch ($action) {
            case 'save_close':
                return redirect()->route('admin.clientes.index')->with('success', $msg);

            case 'save_new':
                return redirect()->route('admin.clientes.create')->with('success', $msg);

            case 'save':
            default:
                // Passa explicitamente a chave nomeada {clientes} (pode ser id ou o model)
                return redirect()
                    ->route('admin.clientes.edit', ['clientes' => $client->getKey()])
                    ->with('success', $msg);
        }
    }

    private function ufs(): array
    {
        return [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
            'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'
        ];
    }
}
