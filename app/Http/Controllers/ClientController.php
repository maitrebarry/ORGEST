<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', [
            'clients' => Client::orderBy('nom')->get(),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('clients.index')->with('status', 'Client créé avec succès.');
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('status', 'Client mis à jour avec succès.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return back()->with('status', 'Client supprimé avec succès.');
    }
}
