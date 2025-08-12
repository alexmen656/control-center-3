<?php
include 'helper/BaseAPI.php';

echo 1;

class ProductsAPI extends BaseAPI
{
    public function handleRequest()
    {
        $this->authenticate();
        $this->checkRateLimit();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        // Produkte statisch (ohne DB)
        $products = $this->getProducts();

        $this->sendSuccess($products);
    }

    private function getProducts()
    {
        // Beispiel-Daten, statisch
        $products = [
            ['id' => 1, 'name' => 'Produkt A', 'price' => 9.99],
            ['id' => 2, 'name' => 'Produkt B', 'price' => 19.99],
            ['id' => 3, 'name' => 'Produkt C', 'price' => 29.99],
        ];

        // Optional: Sanitizing, falls Daten aus unsicherer Quelle
        return array_map(function($p) {
            return [
                'id' => (int)$p['id'],
                'name' => $this->sanitize($p['name']),
                'price' => (float)$p['price'],
            ];
        }, $products);
    }
}

$api = new ProductsAPI('1');
$api->handleRequest();
