<?php
require_once 'BaseService.php';
require_once __DIR__ . "/../dao/OrdersDao.php";
require_once __DIR__ . "/../dao/EventsDao.php";

class OrdersService extends BaseService
{
  private $eventsDao;

  public function __construct()
  {
    parent::__construct(new OrdersDao());
    $this->eventsDao = new EventsDao();
  }

  public function place_order($data)
  {
    if (!isset($data['user_id']) || !isset($data['total_price']) || !isset($data['status'])) {
      Flight::halt(400, json_encode(["message" => "Missing required order data."]));
    }
    return $this->dao->add($data);
  }

  public function create_order_flow($user_id, $event_id, $quantity, $action, $currency = "KM")
  {
    $event_id = (int)$event_id;
    $quantity = (int)$quantity;
    $action = strtoupper(trim((string)$action));
    $currency = strtoupper(trim((string)$currency));

    if ($user_id <= 0) Flight::halt(401, json_encode(["message" => "Invalid user."]));
    if ($event_id <= 0) Flight::halt(400, json_encode(["message" => "Invalid event_id."]));
    if ($quantity < 1 || $quantity > 20) Flight::halt(400, json_encode(["message" => "Quantity must be 1-20."]));
    if (!in_array($action, ["BUY", "RESERVE"])) Flight::halt(400, json_encode(["message" => "Action must be BUY or RESERVE."]));
    if ($currency === "") $currency = "KM";

    $event = $this->eventsDao->get_by_id($event_id);
    if (!$event) Flight::halt(404, json_encode(["message" => "Event not found."]));

    $unit_price = (float)($event["price"] ?? 0);
    if ($unit_price < 0) $unit_price = 0;

    $ticket_limit = (int)($event["ticket_limit"] ?? 0);

    // ✅ SOLD OUT enforcement (only if ticket_limit > 0)
    if ($ticket_limit > 0) {
      $used = (int)$this->dao->used_qty_for_event($event_id);
      $available = $ticket_limit - $used;
      if ($available <= 0) {
        Flight::halt(400, json_encode(["message" => "Sold out. No tickets available."]));
      }
      if ($quantity > $available) {
        Flight::halt(400, json_encode(["message" => "Not enough tickets available. Only {$available} left."]));
      }
    }

    $total = $unit_price * $quantity;

    $status = ($action === "BUY") ? "paid" : "pending";
    $ticket_type = ($action === "BUY") ? "BUY" : "RESERVE";

    return $this->dao->create_order_flow_tx(
      (int)$user_id,
      (int)$event_id,
      (int)$quantity,
      $status,
      $ticket_type,
      (float)$unit_price,
      (float)$total,
      $currency,
      $action
    );
  }

  public function my_orders($user_id)
  {
    return $this->dao->get_my_orders((int)$user_id);
  }

  public function admin_get_all()
  {
    return $this->dao->get_all();
  }

  public function admin_update($data, $id)
  {
    return $this->dao->update($data, $id);
  }

  public function admin_delete($id)
  {
    return $this->dao->delete($id);
  }

  public function get_by_id($id)
  {
    return $this->dao->get_by_id($id);
  }

  public function cancel_my_reservation($user_id, $order_id)
  {
    $order = $this->dao->get_by_id($order_id);
    if (!$order) Flight::halt(404, json_encode(["message" => "Order not found."]));

    if ((int)$order["user_id"] !== (int)$user_id) {
      Flight::halt(403, json_encode(["message" => "Not allowed."]));
    }

    if (($order["status"] ?? "") !== "pending") {
      Flight::halt(400, json_encode(["message" => "Only reservations (pending) can be cancelled."]));
    }

    $this->dao->cancel_order($order_id);
    return true;
  }
}
