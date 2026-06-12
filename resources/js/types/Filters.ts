export type SortOption =
    | 'precio:asc'
    | 'precio:desc'
    | 'nombre:asc'
    | 'nombre:desc'
    | 'marca:asc'
    | 'relevancia'
    | 'topseller';

export interface CatalogFilters {
    categoria?: number;
    marca?: string;
    busqueda?: string;
    stock?: '' | 'disponible' | 'bajo' | 'agotado';
    precio_min?: number;
    precio_max?: number;
    orden?: SortOption;
    pagina?: number;
    max?: number;
}

export const SORT_OPTIONS: { value: SortOption; label: string }[] = [
    { value: 'relevancia', label: 'Relevancia' },
    { value: 'precio:asc', label: 'Precio: menor a mayor' },
    { value: 'precio:desc', label: 'Precio: mayor a menor' },
    { value: 'nombre:asc', label: 'Nombre: A → Z' },
    { value: 'nombre:desc', label: 'Nombre: Z → A' },
    { value: 'marca:asc', label: 'Marca: A → Z' },
    { value: 'topseller', label: 'Más vendidos' },
];
