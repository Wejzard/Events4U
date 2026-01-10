<?php
require_once __DIR__ . '/BaseDao.php';

class OrdersDao extends BaseDao {

  protected $table_name;

  public function __construct()
  {
    $this->table_name = "orders";
    parent::__construct($this->table_name);
  }

  public function get_connection() {
    return $this->connection;
  }

  public function get_all() {
    return $this->query('SELECT * FROM ' . $this->table_name, []);
  }

  public function get_by_id($id) {
    return $this->query_unique(
      'SELECT * FROM ' . $this->table_name . ' WHERE order_id=:id',
      ['id' => $id]
    );
  }

  // ✅ FIX: delete must use order_id (not id)
  public function delete($id) {
    $stmt = $this->connection->prepare("DELETE FROM orders WHERE order_id = :id");
    $stmt->execute(["id" => (int)$id]);
    return $stmt->rowCount();
  }

  public function cancel_order($order_id) {
    $stmt = $this->connection->prepare("
      UPDATE orders
      SET status = 'cancelled'
      WHERE order_id = :id
    ");
    $stmt->execute(["id" => (int)$order_id]);
    return $stmt->rowCount();
  }

  public function create_order_ticket($order_id, $ticket_id, $quantity) {
    $query = "INSERT INTO order_tickets (order_id, ticket_id, quantity) VALUES (:order_id, :ticket_id, :quantity)";
    $stmt = $this->connection->prepare($query);
    $stmt->execute([
      'order_id' => (int)$order_id,
      'ticket_id' => (int)$ticket_id,
      'quantity' => (int)$quantity
    ]);
    return $this->connection->lastInsertId();
  }

  public function update($entity, $id, $id_column = "order_id") {
    return parent::update($entity, $id, $id_column);
  }

  public function create_order_flow_tx(
    $user_id,
    $event_id,
    $quantity,
    $status,
    $ticket_type,
    $unit_price,
    $total_price,
    $currency,
    $action
  ) {
    $pdo = $this->connection;

    try {
      $pdo->beginTransaction();

      $stmtTicket = $pdo->prepare("
        INSERT INTO tickets (event_id, ticket_type, price)
        VALUES (:event_id, :ticket_type, :price)
      ");
      $stmtTicket->execute([
        "event_id" => (int)$event_id,
        "ticket_type" => $ticket_type,
        "price" => (float)$unit_price
      ]);
      $ticket_id = (int)$pdo->lastInsertId();
      if (!$ticket_id) throw new Exception("Ticket insert failed.");

      $stmtOrder = $pdo->prepare("
        INSERT INTO orders (user_id, total_price, status)
        VALUES (:user_id, :total_price, :status)
      ");
      $stmtOrder->execute([
        "user_id" => (int)$user_id,
        "total_price" => (float)$total_price,
        "status" => $status
      ]);
      $order_id = (int)$pdo->lastInsertId();
      if (!$order_id) throw new Exception("Order insert failed.");

      $stmtLink = $pdo->prepare("
        INSERT INTO order_tickets (order_id, ticket_id, quantity)
        VALUES (:order_id, :ticket_id, :quantity)
      ");
      $stmtLink->execute([
        "order_id" => $order_id,
        "ticket_id" => $ticket_id,
        "quantity" => (int)$quantity
      ]);

      if ($action === "BUY") {
        $stmtPay = $pdo->prepare("
          INSERT INTO payments (order_id, currency, amount)
          VALUES (:order_id, :currency, :amount)
        ");
        $stmtPay->execute([
          "order_id" => $order_id,
          "currency" => $currency,
          "amount" => (float)$total_price
        ]);
      }

      $pdo->commit();

      return [
        "order_id" => $order_id,
        "ticket_id" => $ticket_id,
        "status" => $status,
        "total_price" => (float)$total_price,
        "currency" => $currency
      ];
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      Flight::halt(500, json_encode([
        "message" => "Order flow failed.",
        "error" => $e->getMessage()
      ]));
    }
  }

  public function get_my_orders($user_id) {
    $sql = "
      SELECT
        o.order_id,
        o.total_price,
        o.status,
        o.order_date,
        ot.quantity,
        t.ticket_type,
        t.price AS ticket_price,
        e.event_id,
        e.title,
        e.category,
        e.event_date,
        e.event_time,
        e.location
      FROM orders o
      JOIN order_tickets ot ON ot.order_id = o.order_id
      JOIN tickets t ON t.ticket_id = ot.ticket_id
      JOIN events e ON e.event_id = t.event_id
      WHERE o.user_id = :uid
      ORDER BY o.order_date DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute(["uid" => (int)$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function used_qty_for_event($event_id) {
    $sql = "
      SELECT COALESCE(SUM(ot.quantity), 0) AS used_qty
      FROM orders o
      JOIN order_tickets ot ON ot.order_id = o.order_id
      JOIN tickets t ON t.ticket_id = ot.ticket_id
      WHERE t.event_id = :eid
        AND o.status IN ('pending', 'paid')
    ";
    $stmt = $this->connection->prepare($sql);
    $stmt->execute(["eid" => (int)$event_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($row["used_qty"] ?? 0);
  }
}
