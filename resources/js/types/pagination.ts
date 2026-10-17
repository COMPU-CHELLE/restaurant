export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Forma del JSON de un paginador de Laravel. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type TableFilters = {
    search?: string | null;
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
    state?: string | null;
    per_page?: number;
};
