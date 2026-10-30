<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = (int) session('company_id');
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $query = Ticket::query()->where('company_id', $companyId)
            ->with('creator:id,name,email')
            ->when($search !== '', fn ($builder) => $builder->where(fn ($inner) => $inner
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%")))
            ->when(is_string($status) && $status !== '', fn ($builder) => $builder->where('status', $status));

        return Inertia::render('admin/tickets', [
            'items' => $query->latest()->paginate(20)->withQueryString()->through(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'response' => $ticket->response,
                'company' => null,
                'creator' => $ticket->creator ? ['name' => $ticket->creator->name, 'email' => $ticket->creator->email] : null,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'updated_at' => $ticket->updated_at?->toIso8601String(),
            ]),
            'filters' => ['search' => $search, 'status' => $status],
            'isSupport' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::in(['general', 'technical', 'billing', 'factus', 'access'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ]);

        Ticket::query()->create($validated + [
            'company_id' => (int) session('company_id'),
            'created_by' => (int) $request->user()->getAuthIdentifier(),
            'status' => 'open',
        ]);

        return back()->with('success', 'Ticket creado correctamente.');
    }
}
