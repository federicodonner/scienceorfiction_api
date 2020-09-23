<?php

// Middleware que verifica la autenticación del cliente
$authenticate = function ($request, $response, $next) {

  // Verifica que haya un cabezal de autenticación en el request
    $requestHeaders = $request->getHeaders()['HTTP_AUTHORIZATION'];
    if (!$requestHeaders) {
        return messageResponse($response, 'Error de encabezado HTTP', 401);
    }

    // Si el cabezal está disponible, obtiene el token
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Si no hay access token, devuelve error
    if (empty($access_token)) {
        return messageResponse($response, 'Error de login, falta access token', 401);
    }

    // Si hay token, verifica que sea de un usuario logueado
    $user_found = verifyToken($access_token);
    if (empty($user_found)) {
        return messageResponse($response, 'Error de login, usuario no encontrado. Por favor vuelva a ingresar.', 401);
    }

    // Si encuentra todo el login ok, pasa el request a la aplicación
    // adjuntándole el id del usuario logueado
    $usuario_id = $user_found[0]->usuario_id;
    $request = $request->withAttribute('usuario_id', $usuario_id);
    $response = $next($request, $response);

    return $response;
};
