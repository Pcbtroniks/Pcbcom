export interface Price {
    precio_1: number;
    precio_descuento: number;
    precio_especial: number;
    precio_lista: number;
}

export interface CategoryRef {
    id: number;
    nombre: string;
    nivel?: number;
}

export interface Product {
    producto_id: number;
    titulo: string;
    modelo: string;
    marca: string;
    img_portada: string | null;
    total_existencia: number;
    nombre: string;
    etiqueta: string | null;
    categorias: CategoryRef[];
    garantia: string | null;
    precios: Price;
}

export interface ProductsResponse {
    cantidad: number;
    pagina: number;
    paginas: number;
    productos: Product[];
    todo: boolean;
}
