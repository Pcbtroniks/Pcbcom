<?php

namespace App\Services\Syscom;

use Illuminate\Support\Facades\Http;

class ProductsService
{
    protected $apiUrl;
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->apiUrl = config('services.syscom.api_url');
        $this->baseUrl = config('services.syscom.base_url');
        $this->clientId = config('services.syscom.client_id');
        $this->clientSecret = config('services.syscom.client_secret');
    }

    // Métodos para obtener productos, detalles, etc.

    public function getProducts($params)
    {
        // Obtener el token de acceso
        $tokenResponse = Http::post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($tokenResponse->failed()) {
            throw new \Exception('Error obteniendo el token de acceso: ' . $tokenResponse->body());
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Hacer la solicitud para obtener los productos
        $productsResponse = Http::withToken($accessToken)->get("{$this->apiUrl}/productos", $this->ParseParams($params));

        if ($productsResponse->failed()) {
            throw new \Exception('Error obteniendo los productos: ' . $productsResponse->body());
        }

        return $productsResponse->json();
    }

    private function ParseParams($params)
    {
        $queryParams = [];
        if (isset($params['marca'])) {
            $queryParams['marca'] = $params['marca'];
        }
        if (isset($params['categoria']) && is_numeric($params['categoria'])) {
            $queryParams['categoria'] = $params['categoria'];
        }
        if(isset($params['stock'])) {
            $queryParams['stock'] = $params['stock'];
        }
        if(isset($params['busqueda'])) {
            $queryParams['busqueda'] = $params['busqueda'];
        }
        if(isset($params['pagina']) && is_numeric($params['pagina'])) {
            $queryParams['pagina'] = $params['pagina'];
        }
        return $queryParams;
    }
}