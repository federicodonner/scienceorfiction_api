<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Crete a new game for an existing episode
$app->post('/api/game', function (Request $request, Response $response) {

  // Verifies if the request has game information
    $gameHostId = $request->getParam('gameHostId');
    $gameTheme = $request->getParam('gameTheme');
    $gameItems = $request->getParam('gameItems');
    $episodeId = $request->getParam('episodeId');

    // Verify that the episode information is present
    if (!$gameHostId || !$gameTheme || !$gameItems || !$episodeId) {
        return messageResponse($response, 'Missing fields', 400);
    }

    try {
        //  Verify that the episode exists
        $sql = "SELECT * FROM episode WHERE id = $episodeId";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $episodes= $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($episodes)==0) {
            $db = null;
            return messageResponse($response, 'Episode not found.', 404);
        }

        // Verify that the items are correct
        // Verifies that at least two items were submitted
        if (count($gameItems)<2) {
            $db = null;
            return messageResponse($response, 'Only one item was submitted, at least two need to be created. Game not created, plase verify the information and add it afterwards.', 400);
        }

        // Variables for verifying items
        $allHaveText = true;
        $haveOneScience = false;
        $haveOneFiction = false;

        foreach ($gameItems as $gameItem) {
            // create object for processing
            $itemObject = json_decode($gameItem);

            if ($itemObject->fiction == 1) {
                $haveOneFiction = true;
            }

            if ($itemObject->fiction == 0) {
                $haveOneScience = true;
            }

            $itemText = $itemObject->itemText;
            if (!$itemText) {
                $allHaveText = false;
            }
        }

        if (!$allHaveText || !$haveOneFiction || !$haveOneScience) {
            $db = null;
            return messageResponse($response, 'There was a problem with the items, please verify them and try again.', 400);
        }

        // Verifies that the host exists
        $sql = "SELECT * FROM rogue WHERE id = $gameHostId";
        $stmt = $db->query($sql);
        $hosts = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($hosts)==0) {
            $db = null;
            return messageResponse($response, 'Host not found, please verify the information and try again.', 400);
        }


        // If everything is ok, insert the episode and the items
        $sql="INSERT INTO game (hostId, episodeId, theme) VALUES (:hostId, :episodeId, :theme)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':hostId', $gameHostId);
        $stmt->bindParam(':episodeId', $episodeId);
        $stmt->bindParam(':theme', $gameTheme);
        $stmt->execute();


        $sql="SELECT * FROM game WHERE id = LAST_INSERT_ID()";
        $stmt = $db->query($sql);
        $games = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Finds the id of the inserted game
        $game = $games[0];

        $gameId = $game->id;

        foreach ($gameItems as $gameItem) {
            // create object for processing
            $itemObject = json_decode($gameItem);
            $itemText = $itemObject->itemText;
            $link = $itemObject->link;
            $sortOrder = $itemObject->sortOrder;
            $fiction = $itemObject->fiction;

            $sql="INSERT INTO item (itemText, link, sortOrder, fiction, gameId) VALUES (:itemText, :link, :sortOrder, :fiction, :gameId)";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':itemText', $itemText);
            $stmt->bindParam(':link', $link);
            $stmt->bindParam(':sortOrder', $sortOrder);
            $stmt->bindParam(':fiction', $fiction);
            $stmt->bindParam(':gameId', $gameId);
            $stmt->execute();
        }

        $sql="SELECT * FROM item WHERE gameId = $gameId";
        $stmt = $db->query($sql);
        $items = $stmt->fetchAll(PDO::FETCH_OBJ);

        $game->items = $items;

        $episode->game = $game;

        return dataResponse($response, $game, 201);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
});
