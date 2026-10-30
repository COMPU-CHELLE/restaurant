import {
    Armchair,
    Banknote,
    Boxes,
    Building2,
    ClipboardList,
    CreditCard,
    LayoutGrid,
    LifeBuoy,
    MapPin,
    Package,
    PlugZap,
    Printer,
    ScrollText,
    Settings,
    ShieldCheck,
    SlidersHorizontal,
    Tags,
    TrendingUp,
    Truck,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/** Nombre de ícono guardado en la tabla levels/modules → componente de lucide-react. */
const icons: Record<string, LucideIcon> = {
    LayoutGrid,
    Armchair,
    ClipboardList,
    Banknote,
    Package,
    Tags,
    Boxes,
    Truck,
    Settings,
    MapPin,
    Wallet,
    CreditCard,
    Printer,
    ShieldCheck,
    Users,
    Building2,
    SlidersHorizontal,
    ScrollText,
    PlugZap,
    LifeBuoy,
    BarChart3: TrendingUp,
};

export function menuIcon(name: string | null | undefined): LucideIcon {
    return (name && icons[name]) || LayoutGrid;
}
