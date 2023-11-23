<?php

namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChirpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('chirps.index', [
            'chirps' => Chirp::with('user')->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('chirps.create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:255'
        ]);

        $chirp = $request->user()->chirps()->create($validated);

        $chirp->broadcastPrependTo('chirps')
            ->target('chirps')
            ->partial('chirps._chirp', [
                'chirp' => $chirp
            ])
            ->toOthers();

        return redirect(route('chirps.index'))->with('status', __('Chirp created.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Chirp $chirp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chirp $chirp): View
    {
        $this->authorize('update', $chirp);

        return view('chirps.edit', ['chirp' => $chirp]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chirp $chirp)
    {
        $this->authorize('update', $chirp);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255']
        ]);

        $chirp->update($validated);

        $chirp->broadcastReplaceTo('chirps')
            ->target(dom_id($chirp))
            ->partial('chirps._chirp', [
                'chirp' => $chirp
            ])
            ->toOthers();

        if ($request->wantsTurboStream()) {
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->flash(__('Chirp updated.')),
            ]);
        }

        return redirect(route('chirps.index'))->with('status', __('Chirp updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();

        $chirp->broadcastRemoveTo('chirps')
            ->target(dom_id($chirp))
            ->toOthers();

        if ($request->wantsTurboStream()) {
            return turbo_stream([
                turbo_stream($chirp),
                turbo_stream()->flash(__('Chirp deleted.')),
            ]);
        }

        return redirect(route('chirps.index'))->with('status', __('Chirp deleted.'));
    }
}
