<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Add product
$app->post('/api/oauth', function (Request $request, Response $response) {
    $params = $request->getBody();
    $grant_type = $request->getParam('grant_type');
    $access = $request->getParam('access');

    if ($grant_type == 'password') {
        $username = $request->getParam('username');
        // $username = json_decode($params)->user;
        //$pass = $request->getParam('pass');

        $sql = "SELECT * FROM usuario WHERE email = '$username'";
        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->query($sql);
            $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Si no hay ningún usuario con ese nombre
            if ($usuarios == null) {
                //cambio el estatus del mensaje e incluyo el mensaje de error
                $db = null;
                return messageResponse($response, 'Nombre de usuario o password incorrecto.', 403);
            } else {
                // Verifica el password contra el hash
                if (password_verify($access, $usuarios[0]->pass_hash)) {
                    // Si el password coincide genera el token y lo responde
                    $access_token = random_str(32);
                    $now = time();
                    $user_id = $usuarios[0]->id;

                    // SQL statement
                    $sql = "INSERT INTO login (usuario_id,token,login_dttm) VALUES (:user_id,:token,:now)";

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':token', $access_token);
                    $stmt->bindParam(':now', $now);

                    $stmt->execute();

                    $authRespuesta->token = $access_token;
                    $authRespuesta->grant_type = $grant_type;
                    $authRespuesta->user_id = $user_id;

                    $db = null;
                    return dataResponse($response, $authRespuesta, 200);
                } else { //   if (password_verify($access, $profesores[0]->password)) {
                    // Si no coincide, devuelve error
                    return messageResponse($response, 'Nombre de usuario o password incorrecto.', 403);
                }
            }
        } catch (PDOException $e) {
            $db = null;
            return messageResponse($response, $e->getMessage(), 500);
        }
    } elseif ($grant_type == 'token') {
        try {
            // Si el grant_type es token, voy a buscarlo a la base
            $sql = "SELECT * FROM login WHERE token = '$access'";

            $db = new db();
            $db = $db->connect();

            $stmt = $db->query($sql);
            $tokens = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Si no devuelve ningún token, devuelvo error
            if ($tokens == null) {
                //cambio el estatus del mensaje e incluyo el mensaje de error
                return messageResponse($response, 'Token incorrecto.', 400);
            } else {  // if ($tokens == null) {

                // Si encuentra uno, devuelve los detalles del usuario
                $usuario->usuario_id = $tokens[0]->usuario_id;
                return dataResponse($response, $usuario, 200);
            }
        } catch (PDOException $e) {
            $db = null;
            return messageResponse($response, $e->getMessage(), 500);
        }
    } else {
        $db = null;
        return messageResponse($response, 'Grant type incorrecto.', 400);
    }
});
