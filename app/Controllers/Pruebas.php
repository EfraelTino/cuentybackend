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
        $stmt = $this->db->prepare("SELECT * FROM products INNER JOIN categories ON products.category_id = categories.id_category ORDER BY products.updated_at DESC, products.id DESC;");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $productos;
    }
    //Obtener metodos de pago
    public function obtenerMetodosPago()
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_method");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //Obtener producto x id
    public function buscarProductoPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : false;
    }
    //obtener categorias

    public function mostrarCategoria()
    {
        $stmt = $this->db->prepare("SELECT * FROM categories");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $productos;
    }
    //obtener marcas
    public function mostrarMarca()
    {
        $stmt = $this->db->prepare("SELECT * FROM brands");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $productos;
    }
    //crear marca
    public function crearMarca($data)
    {
        $stmt = $this->db->prepare("INSERT INTO brands (name_brand) VALUES (?)");
        $stmt->execute([$data['name_brand']]);
        return $stmt->rowCount();
    }
    //buscar marca por id
    public function buscarMarcaPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM brands WHERE id_brand = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //actualizar marca
    public function actualizarMarca($data)
    {
        $stmt = $this->db->prepare("UPDATE brands SET name_brand = ? WHERE id_brand = ?");
        $stmt->execute([$data['name_brand'], $data['id_brand']]);
        return $stmt->rowCount();
    }
    //eliminar marca
    public function eliminarMarca($id)
    {
        $stmt = $this->db->prepare("DELETE FROM brands WHERE id_brand = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
    // filtro de productos
    public function actualizarProducto($data)
    {
        // Verificar si se envió una nueva imagen
        $updatePhoto = isset($data['photo']) ? "photo=?" : "";

        // Construir consulta SQL dinámicamente
        $sql = "UPDATE products SET name=?, description=?, category_id=?, price=?, price_offert=?, stock=?,  price_compra=? "
            . ($updatePhoto ? ", $updatePhoto " : "")
            . "WHERE id=?";

        // Preparar consulta
        $stmt = $this->db->prepare($sql);

        // Construir parámetros dinámicamente
        $params = [
            $data['name'],
            $data['description'],
            $data['id_category'],
            $data['price'],
            $data['price_offert'],
            $data['stock'],
            $data['price_compra']
        ];

        // Agregar `photo` solo si fue enviado
        if ($updatePhoto) {
            $params[] = $data['photo'];
        }

        // Agregar ID al final
        $params[] = $data['idProduct'];

        // Ejecutar consulta
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    // Obtener detalle de un producto
    public function detalleProducto($data)
    {
        $stmt = $this->db->prepare("SELECT 
        ps.id_producto AS id_producto, 
        ps.imagen, 
        ps.nombre, 
        ps.descripcion, 
        ps.calidad, 
        ps.unidad_medida, 
        ps.id_proveedor, 
        ps.id_subcategoria,
        ps.precio_base, 
        ps.fecha_creacion,
        ps.contacto,
        CONCAT('[', 
            (SELECT 
                GROUP_CONCAT(
                    JSON_OBJECT(
                        'precio', pr.precio, 
                        'contiene', pr.contiene, 
                        'desde_cantidad', pr.desde_cantidad, 
                        'hasta_candidad', pr.hasta_candidad
                    )
                ) 
            FROM precios pr 
            WHERE ps.id_producto = pr.id_producto 
            ORDER BY pr.id
            ), 
            ']'
        ) AS precios
    FROM 
        productos ps
    JOIN 
        sub_categoria sc ON ps.id_subcategoria = sc.id
    JOIN 
        categoria c ON sc.id_categoria = c.id
    WHERE 
         ps.id_producto = ?");
        $stmt->execute([$data['id']]);

        // retorno un resultado
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // insert producto
    public function inserProduct($data)
    {
        date_default_timezone_set('America/Bogota');
        $fechaActual = date('Y-m-d H:i:s', time());
        $datepositivo = 1;
        $stmt = $this->db->prepare(
            "INSERT INTO productos (imagen, nombre, descripcion,  calidad, id_proveedor, id_subcategoria , fecha_creacion, unidad_medida, precio_base, contacto,activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $success = $stmt->execute([
            $data['foto'],
            $data['producto'],
            $data['descripcion'],
            $data['calidad'],
            $data['id_usuario'],
            $data['clasificacion'],
            $fechaActual,
            $data['medidas'],
            $data['base'],
            $data['telefono'],
            $datepositivo
        ]);

        // Verifica si la inserción fue exitosa
        if ($success) {
            // Obtén el ID del último registro insertado
            $lastInsertId = $this->db->lastInsertId();
            return $lastInsertId;
        } else {
            return false; // O maneja el error de otra manera
        }
    }
    public function insertCategoria($data)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO categories (name_cat, description_cat) VALUES (?, ?)"
        );
        $success = $stmt->execute([
            $data['name_cat'],
            $data['description_cat'] ?? null
        ]);

        // Verifica si la inserción fue exitosa
        if ($success) {
            // Obtén el ID del último registro insertado
            $lastInsertId = $this->db->lastInsertId();
            return $lastInsertId;
        } else {
            return false; // O maneja el error de otra manera
        }
    }
    //buscar categoría por id
    public function buscarCategoriaPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id_category = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //actualizar categoría
    public function actualizarCategoria($data)
    {
        $stmt = $this->db->prepare("UPDATE categories SET name_cat = ? WHERE id_category = ?");
        $stmt->execute([$data['name_cat'], $data['id_category']]);
        return $stmt->rowCount();
    }
    //eliminar categoría
    public function eliminarCategoria($id)
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id_category = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
    public function updateProduct($data, $productID)
    {
        date_default_timezone_set('America/Bogota');
        $fechaActual = date('Y-m-d H:i:s', time());

        $stmt = $this->db->prepare(
            "UPDATE productos 
         SET imagen = ?, nombre = ?, descripcion = ?, calidad = ?, 
             id_proveedor = ?, id_subcategoria = ?, fecha_creacion = ?, 
             unidad_medida = ?, precio_base = ?, contacto = ?
         WHERE id_producto = ?"
        );

        $success = $stmt->execute([
            $data['foto'],
            $data['producto'],
            $data['descripcion'],
            $data['calidad'],
            $data['id_usuario'],
            $data['clasificacion'],
            $fechaActual,
            $data['medidas'],
            $data['base'],
            $data['telefono'],
            $productID
        ]);

        return $success ? $productID : false;
    }

    public  function obtenerProductoPorCategoria($data)
    {
        $stmt = $this->db->prepare("SELECT * FROM productos JOIN sub_categoria ON productos.id_subcategoria = sub_categoria.id JOIN categoria ON sub_categoria.id_categoria = categoria.id  WHERE categoria.nombre_cat  = ? AND productos.activo = 1 ORDER BY RAND()");
        $stmt->execute([$data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insertar nuevo producto para licorería
    public function insertarProducto($data)
    {
        try {
            /**
             * $stmt = $this->db->prepare(
                "INSERT INTO products (name, description, category_id, stock_unidades, photo, brand_id) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $success = $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['categoria_id'],
                $data['stock'],
                $data['foto'],
                $data['brand_id'],
            ]);

            // Verifica si la inserción fue exitosa
            if ($success) {
                // Obtén el ID del último registro insertado
                $insertProduct = $this->db->lastInsertId();
                $stmt1 = $this->db->prepare("INSERT INTO product_variants (id_producto, presentation, quantity_per_pack, price_ant, price, price_offert) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt1->execute([$insertProduct, $data['quantity_per_pack'], $data['presentation'], $data['price_ant'], $data['price'], $data['price_offert']]);
                if ($stmt1) {
                    return $insertProduct;
                } else {
                    return false;
                }
            } else {
                return false;
            }
             */
            $stmt = $this->db->prepare(
                "INSERT INTO products (name, description, price_compra, category_id, price, price_offert, stock, photo, brand_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $success = $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['price_ant'],
                $data['categoria_id'],
                $data['precio'],
                $data['precio_oferta'],
                $data['stock'],
                $data['foto'],
                $data['brand_id'],
            ]);

            // Verifica si la inserción fue exitosa
            if ($success) {
                // Obtén el ID del último registro insertado
                return $this->db->lastInsertId();
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Error al insertar producto: " . $e->getMessage());
        }
    }

    // Buscar productos por nombre para licorería
    public function buscarProducto($termino)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as category_name, b.name as brand_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN brands b ON p.brand_id = b.id
                 WHERE p.name LIKE ? OR p.description LIKE ?"
            );

            $paramBusqueda = '%' . $termino . '%';
            $stmt->execute([$paramBusqueda, $paramBusqueda]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception("Error al buscar productos: " . $e->getMessage());
        }
    }

    // Eliminar producto (marcar como inactivo o eliminar según sea la lógica)
    public function eliminarProducto($id)
    {
        try {
            // Opción 1: Eliminar completamente el producto
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");

            // Opción 2: Si tienes campo de estado, podrías usar:
            // $stmt = $this->db->prepare("UPDATE products SET status = 'Inactivo' WHERE id = ?");

            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new \Exception("Error al eliminar producto: " . $e->getMessage());
        }
    }
}
