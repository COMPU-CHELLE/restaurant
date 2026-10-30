<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $query = Ticket::query()->with(['company:codmov,code,empnom', 'creator:id,name,email'])
            ->when($search !== '', fn ($builder) => $builder->where(fn ($inner) => $inner
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%")))
            ->when(is_string($status) && $status !== '', fn ($builder) => $builder->where('status', $status));

        return Inertia::render('soporte/tickets', [
            'items' => $query->latest()->paginate(20)->withQueryString()->through(fn (Ticket $ticket) => $this->toRow($ticket)),
            'filters' => ['search' => $search, 'status' => $status],
            'isSupport' => true,
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'waiting_user', 'resolved', 'closed'])],
            'response' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('mysql.users', 'id')],
        ]);

        $ticket->fill($validated);
        $ticket->resolved_at = in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null;
        $ticket->save();

        return back()->with('success', 'Ticket actualizado correctamente.');
    }

    private function toRow(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'response' => $ticket->response,
            'company' => $ticket->company ? ['code' => $ticket->company->code, 'name' => $ticket->company->empnom] : null,
            'creator' => $ticket->creator ? ['name' => $ticket->creator->name, 'email' => $ticket->creator->email] : null,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'updated_at' => $ticket->updated_at?->toIso8601String(),
        ];
    }
}
