import { TicketCenter } from '@/components/ticket-center';

export default function Tickets(props: Parameters<typeof TicketCenter>[0]) {
    return <TicketCenter {...props} />;
}

Tickets.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/tickets' },
        { title: 'Tickets', href: '/admin/tickets' },
    ],
};
