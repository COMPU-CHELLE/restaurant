import { Link, router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';

const MAX_VISIBLE = 8;

export function CompanySwitcher() {
    const { company, companies, role } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!company) {
        return null;
    }

    const companyList = Array.isArray(companies) ? companies : [];

    // La empresa actual siempre primero; el resto en el orden recibido.
    const ordered = [
        company,
        ...companyList.filter((item) => item.id !== company.id),
    ];

    const visible = ordered.slice(0, MAX_VISIBLE);

    const switchTo = (id: number) => {
        if (id === company.id) {
            return;
        }

        router.post(`/company/select/${id}`, {}, { preserveScroll: true });
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <Building2 className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {company.name}
                                </span>
                                <span className="truncate text-xs">
                                    {company.code}
                                    {role ? ` · ${role.name}` : ''}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        className="max-h-80 w-(--radix-dropdown-menu-trigger-width) min-w-56 overflow-y-auto rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'right'
                                  : 'bottom'
                        }
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Empresas
                        </DropdownMenuLabel>

                        {visible.map((item) => (
                            <DropdownMenuItem
                                key={item.id}
                                onSelect={() => switchTo(item.id)}
                                className="gap-2 p-2"
                            >
                                <div className="flex size-6 items-center justify-center rounded-md border">
                                    <Building2 className="size-3.5 shrink-0" />
                                </div>
                                <div className="grid flex-1 leading-tight">
                                    <span className="truncate">{item.name}</span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {item.code}
                                    </span>
                                </div>
                                {item.id === company.id && (
                                    <Check className="size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}

                        {ordered.length > MAX_VISIBLE && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/company/select">
                                        Ver todas las empresas (
                                        {ordered.length})
                                    </Link>
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
