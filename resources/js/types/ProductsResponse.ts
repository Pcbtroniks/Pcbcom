import { Product } from "./Products";

export interface ProductResponse {
    cantidad: number;
    pagina: number;
    paginas: number;
    productos: Product[];
    todo: boolean;
}