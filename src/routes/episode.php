<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// // Returns all episodes
  $app->get('/api/episode', function (Request $request, Response $response) {
      try {
          $sql = "SELECT * FROM episode ORDER BY broadcastDate DESC";

          $db = new db();
          $db = $db->connect();

          // Select all rogues
          $stmt = $db->query($sql);
          $episodes = $stmt->fetchAll(PDO::FETCH_OBJ);

          // Create object for response
          $episodesResponse->episodes = $episodes;
          $db = null;
          return dataResponse($response, $episodesResponse, 200);
      } catch (PDOException $e) {
          $db = null;
          return messageResponse($response, $e->getMessage(), 503);
      }
  });


// Returns a specific episode and its details
$app->get('/api/episode/{id}', function (Request $request, Response $response) {
    $episodeId = $request->getAttribute('id');
    try {
        $sql = "SELECT * FROM episode WHERE id = $episodeId";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $episodes = $stmt->fetchAll(PDO::FETCH_OBJ);

        $episode = $episodes[0];

        // Finds the games that were played in that episode
        $sql = "SELECT g.id as gameId, g.theme, CONCAT(r.firstName, ' ', r.lastName) as hostName FROM game g LEFT JOIN rogue r on g.hostId = r.id WHERE episodeId = $episodeId";

        $stmt = $db->query($sql);
        $games = $stmt->fetchAll(PDO::FETCH_OBJ);


        // Finds the items in the game
        foreach ($games as $game) {
            $gameId = $game->gameId;

            $sql = "SELECT id, text, fact FROM item WHERE gameId = $gameId ORDER BY sortOrder";

            $stmt = $db->query($sql);
            $items = $stmt->fetchAll(PDO::FETCH_OBJ);

            $game->items = $items;
        }
        $episode->games = $games;
        if (count($episodes)==0) {
            $db = null;
            return messageResponse($response, 'Episode not found', 404);
        }

        $db = null;
        return dataResponse($response, $episodes[0], 200);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 503);
    }
});


// Crete a new episode
$app->post('/api/episode', function (Request $request, Response $response) {
    // Get the data from the request
    $episodeNumber = $request->getParam('episodeNumber');
    $broadcastDate = $request->getParam('broadcastDate');

    // Verify that the episode information is present
    if (!$episodeNumber || !$broadcastDate) {
        return messageResponse($response, 'Missing episode fields', 400);
    }

    // Verify that the
    $sql = "SELECT * FROM episode WHERE episodeNumber = $episodeNumber OR broadcastDate = $broadcastDate";

    $db = new db();
    $db = $db->connect();

    $stmt = $db->query($sql);
    $episodes= $stmt->fetchAll(PDO::FETCH_OBJ);

    if (count($episodes)>0) {
        $db = null;
        return messageResponse($response, 'Episode already exists.', 409);
    }

    try {
        // Insert information into the db
        $sql="INSERT INTO episode (episodeNumber, broadcastDate) VALUES (:episodeNumber, :broadcastDate)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':episodeNumber', $episodeNumber);
        $stmt->bindParam(':broadcastDate', $broadcastDate);
        $stmt->execute();

        $sql="SELECT * FROM episode WHERE id = LAST_INSERT_ID()";
        $stmt = $db->query($sql);
        $episodes= $stmt->fetchAll(PDO::FETCH_OBJ);

        // Finds the id of the inserted episode
        $episode = $episodes[0];

        // Verifies if the request has game information
        $gameHostId = $request->getParam('gameHostId');
        $gameTheme = $request->getParam('gameTheme');
        $gameItems = $request->getParam('gameItems');
        if ($gameHostId && $gameTheme && $gameItems) {

            // Verifies that the host exists
            $sql = "SELECT * FROM rogue WHERE id = $gameHostId";
            $stmt = $db->query($sql);
            $hosts = $stmt->fetchAll(PDO::FETCH_OBJ);

            if (count($hosts)==0) {
                $db = null;
                return messageResponse($response, 'Episode created. Host not found, game not added. Please verify the information and add it afterwards.', 201);
            }
            // Verifies that at least two items were submitted
            if (count($gameItems)<2) {
                $db = null;
                return messageResponse($response, 'Episode created. Only one item was submitted, at least two need to be created. Game not created, plase verify the information and add it afterwards.', 201);
            }

            // Gets the new episode id and inserts the new game
            $episodeId = $episode->id;

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
                return messageResponse($response, 'Episode created. There was a problem with the items, please verify them and enter them later.', 201);
            }


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
        }

        return dataResponse($response, $episode, 201);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
});
