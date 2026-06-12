export interface Category {
    id: number;
    nombre: string;
    nivel: number;
    parent_id: number | null;
    children: Category[];
    children_ids: number[];
    product_count: number;
}

export interface CategoryBreadcrumb {
    id: number;
    nombre: string;
    nivel: number;
}
