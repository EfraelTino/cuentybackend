<?php

class Product
{

    private $db;


    public function __construct($db)
    {
        $this->db = $db;
    }

    // Obtener todos los productos
    public function mostrarProductos()
    {
        $stmt = $this->db->prepare("SELECT * FROM users");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        var_dump($productos) ;
    }
}
