<?php


require_once './app/Models/Product.php';
require_once './config/Utils.php';

class   ProductController
{
    private $productModel;
    private $utils;
    public function __construct($db)
    {
        $this->productModel = new  Product($db);
        $this->utils = new Utils();
    }
    public function mostrarProductos()
    {
        try {
            $productos = $this->productModel->mostrarProductos();

            // Aquí se envía un array con un key "data" que contiene los productos
            if(empty($productos)){
                return $this->utils->jsonResponse(200, ['message' => 'No se encontraron productos', 'success'=>false]);
            }
            return $this->utils->jsonResponse(200, ['message' => $productos, 'success'=>true]);
        } catch (\Throwable $th) {
            $this->utils->jsonResponse(200, ['message' => $th->getMessage(), 'success'=>false]);
        }
    }
}
