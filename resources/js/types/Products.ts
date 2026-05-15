import { Price } from "./Prices";
import { Category } from "./Categories";

export interface Product {
    producto_id: number;
    titulo: string;
    modelo: string;
    marca: string;
    img_portada: string;
    total_existencia: number;
    nombre: string;
    etiqueta: string;
    categorias: Category[];
    garantia: string;
    precios: Price;
}