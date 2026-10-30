import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
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
                            const children = item.children ?? [];

                            // Con subitems el nivel solo despliega (no navega) y se abre solo si uno está activo.
                            if (children.length > 0) {
                                return (
                                    <Collapsible
                                        key={item.href}
                                        asChild
                                        defaultOpen={children.some((child) =>
                                            isCurrentUrl(child.href),
                                        )}
                                        className="group/collapsible"
                                    >
                                        <SidebarMenuItem>
                                            <CollapsibleTrigger asChild>
                                                <SidebarMenuButton
                                                    tooltip={{ children: item.title }}
                                                >
                                                    <Icon />
                                                    <span>{item.title}</span>
                                                    <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                                </SidebarMenuButton>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <SidebarMenuSub>
                                                    {children.map((child) => (
                                                        <SidebarMenuSubItem key={child.href}>
                                                            <SidebarMenuSubButton
                                                                asChild
                                                                isActive={isCurrentUrl(child.href)}
                                                            >
                                                                <Link href={child.href}>
                                                                    <span>{child.title}</span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    ))}
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuItem>
                                    </Collapsible>
                                );
                            }

                            return (
                                <SidebarMenuItem key={item.href}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentUrl(item.href, undefined, true)}
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
