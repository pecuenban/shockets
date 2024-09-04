<?php
namespace App;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
  protected $clients;
  public function __construct() {
    $this->clients = new \SplObjectStorage;
}
    public function onOpen(ConnectionInterface $conn) {
      // Store the new connection to send messages to later
      $this->clients->attach($conn);
      $respuesta = array();
      $respuesta['msg'] = "Hello";
      $respuesta['date'] = date('Y-m-d H:i:s');
      $respuesta['user'] = $conn->resourceId;
      $respuesta['type'] = "system";
      $res = json_encode($respuesta);
        $conn->send($res);
      echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
      $numRecv = count($this->clients) - 1;
      echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
          , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

      foreach ($this->clients as $client) {
          if ($from !== $client) {
              // The sender is not the receiver, send to each client connected
              $respuesta = array();
              $respuesta['msg'] = $msg;
              $respuesta['date'] = date('Y-m-d H:i:s');
              $respuesta['user'] = $from->resourceId;
              $respuesta['type'] = "user";
              $res = json_encode($respuesta);
              $client->send($res);
          }
      }
  }

  public function onClose(ConnectionInterface $conn) {
      // The connection is closed, remove it, as we can no longer send it messages
      $this->clients->detach($conn);

      echo "Connection {$conn->resourceId} has disconnected\n";
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
      echo "An error has occurred: {$e->getMessage()}\n";

      $conn->close();
  }
}