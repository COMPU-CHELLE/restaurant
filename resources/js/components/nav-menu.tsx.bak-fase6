import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { menuIcon } from '@/lib/menu-icons';
import type { MenuModule } from '@/types';

export function NavMenu({ modules }: { modules: MenuModule[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <>
            {modules.map((module) => (
                <SidebarGroup key={module.title} className="px-2 py-0">
                    <SidebarGroupLabel>{module.title}</SidebarGroupLabel>
                    <SidebarMenu>
                        {module.items.map((item) => {
                            const Icon = menuIcon(item.icon);

                            return (
                                <SidebarMenuItem key={item.href}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentUrl(
                                            item.href,
                                            undefined,
                                            true,
                                        )}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            <Icon />
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
