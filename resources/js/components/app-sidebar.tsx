import { Link, usePage } from '@inertiajs/react';
import { Building2, LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { CompanySwitcher } from '@/components/company-switcher';
import { NavMain } from '@/components/nav-main';
import { NavMenu } from '@/components/nav-menu';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    const { company, companies, role, menu } = usePage().props;

    // Soporte y quien pertenece a varias empresas pueden cambiar sin volver a iniciar sesión.
    const canSwitch =
        company !== null && (companies.length > 1 || role?.code === 'SOPORTE');

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                {canSwitch ? (
                    <CompanySwitcher />
                ) : (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={dashboard()} prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                )}
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMenu modules={menu} />
            </SidebarContent>

            <SidebarFooter>
                {company && !canSwitch && (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                tooltip={{ children: company.name }}
                            >
                                <Building2 />
                                <span>{company.name}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                )}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
