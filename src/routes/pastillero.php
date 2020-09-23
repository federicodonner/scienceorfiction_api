<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve todas las drogas y sus medicinas
$app->get('/api/pastillero/{id}', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');
    // Verifica que el usuario tenga permisos de lectura del pastillero
    $pastillero_id = $request->getAttribute('id');
    $permisos_usuario = verificarPermisosUsuarioPastillero($usuario_id, $pastillero_id);
    // Como es un GET sólo verifica permisos de lectura
    if (!$permisos_usuario->acceso_lectura_pastillero) {
        $db = null;
        return messageResponse($response, 'No tiene permisos para acceder al pastillero seleccionado', 403);
    }
    try {
        $id = $request->getAttribute('id');
        $sql = "SELECT * FROM pastillero WHERE id = $id";
        $db = new db();
        $db = $db->connect();

        // FALTA VERIFICAR QUE EL PASTILLERO EXISTA
        $stmt = $db->query($sql);
        $pastilleros = $stmt->fetchAll(PDO::FETCH_OBJ);
        $pastillero = $pastilleros[0];

        // Obtiene las dosis ingresadas para el pastillero
        $sql = "SELECT * FROM dosis WHERE pastillero_id = $id";

        $stmt = $db->query($sql);
        $dosis = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Por cada dosis, va a buscar qué droga le corresponde
        foreach ($dosis as $dosi) {
            $dosi_id = $dosi->id;
            $sql = "SELECT * FROM droga_x_dosis WHERE dosis_id = $dosi_id";
            $stmt = $db->query($sql);
            $drogas = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Por cada droga, va a buscar los detalles
            foreach ($drogas as $droga) {
                $droga_id = $droga->droga_id;
                $sql = "SELECT * FROM droga WHERE id = $droga_id";
                $stmt = $db->query($sql);
                $drogas_de_dosis = $stmt->fetchAll(PDO::FETCH_OBJ);

                $droga->nombre = $drogas_de_dosis[0]->nombre;
            }

            $dosi->drogas = $drogas;
        }

        $pastillero->dosis = $dosis;

        $db = null;
        return dataResponse($response, $pastillero, 200);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 503);
    }
})->add($authenticate);


// Agrega un pastillero
$app->post('/api/pastillero', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');

    // Obtiene los detalles del pastillero del request
    $dia_actualizacion = $request->getParam('dia_actualizacion');
    $dosis = $request->getParam('dosis');

    // Verify that the information is present
    if (!$dia_actualizacion) {
        return messageResponse($response, 'Campos incorrectos', 400);
    }
    try {

            // Genera el pastillero en la base de datos
        $sql = "INSERT INTO pastillero (dia_actualizacion, paciente_id) VALUES (:dia_actualizacion, :paciente_id)";
        $db = new db();
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->bindparam(':dia_actualizacion', $dia_actualizacion);
        $stmt->bindparam(':paciente_id', $usuario_id);
        $stmt->execute();

        // Obtiene el id del pastillero recién creado para asignarle el usuario
        $sql="SELECT * FROM pastillero WHERE id = LAST_INSERT_ID()";
        $stmt = $db->query($sql);
        $pastilleros = $stmt->fetchAll(PDO::FETCH_OBJ);

        $pastillero = $pastilleros[0];
        $pastillero_id = $pastillero->id;

        // Agrega al usuario como administrados y paciente del pastillero
        $sql = "INSERT INTO usuario_x_pastillero (usuario_id, pastillero_id, admin, activo) VALUES (:usuario_id, :pastillero_id, :admin, :activo)";

        $uno = 1;

        $stmt = $db->prepare($sql);
        $stmt->bindparam(':usuario_id', $usuario_id);
        $stmt->bindparam(':pastillero_id', $pastillero_id);
        $stmt->bindparam(':admin', $uno);
        $stmt->bindparam(':activo', $uno);
        $stmt->execute();

        // Verifica que el request tenga dosis para el pastillero
        if ($dosis) {
            foreach ($dosis as $dosi) {
                // Convierte el string a un JSON
                $dosi_objeto = json_decode($dosi);

                $sql = "INSERT INTO dosis (horario, pastillero_id) VALUES (:horario, :pastillero_id)";

                $horario = $dosi_objeto->horario;

                $stmt = $db->prepare($sql);
                $stmt->bindparam(':horario', $horario);
                $stmt->bindparam(':pastillero_id', $pastillero_id);
                $stmt->execute();
            }
        }

        // Busca los detalles del usuario para devolverlo
        $sql = "SELECT uxp.*, u.* FROM usuario_x_pastillero uxp LEFT JOIN usuario u ON uxp.usuario_id = u.id WHERE usuario_id = $usuario_id AND pastillero_id = $pastillero_id";

        $stmt = $db->query($sql);
        $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

        unset($usuarios[0]->pass_hash);
        unset($usuarios[0]->pendiente_cambio_pass);

        $pastillero->usuarios = $usuarios;

        // Busca las dosis del pastillero para devolverlo
        $sql="SELECT * FROM dosis WHERE pastillero_id = $pastillero_id";

        $stmt = $db->query($sql);
        $dosis_pastillero = $stmt->fetchAll(PDO::FETCH_OBJ);

        $pastillero->dosis = $dosis_pastillero;

        $db = null;
        return dataResponse($response, $pastillero, 201);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
})->add($authenticate);
