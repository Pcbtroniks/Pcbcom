<?php

namespace App\Services\Syscom;

use Illuminate\Support\Facades\Http;

class CategoriesService
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

    public function getCategories()
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

        // Hacer la solicitud para obtener las categorías
        $categoriesResponse = Http::withToken($accessToken)->get("{$this->apiUrl}/categorias");

        if ($categoriesResponse->failed()) {
            throw new \Exception('Error obteniendo las categorías: ' . $categoriesResponse->body());
        }

        return $categoriesResponse->json();
    }

    public function getCategoryById($id)
    {
        // Obtener el token de acceso
        $tokenResponse = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($tokenResponse->failed()) {
            throw new \Exception('Error obteniendo el token de acceso: ' . $tokenResponse->body());
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Hacer la solicitud para obtener la categoría por ID
        $categoryResponse = Http::withToken($accessToken)->get("{$this->apiUrl}/categorias/{$id}");

        if ($categoryResponse->failed()) {
            throw new \Exception('Error obteniendo la categoría: ' . $categoryResponse->body());
        }

        return $categoryResponse->json();
    }
}