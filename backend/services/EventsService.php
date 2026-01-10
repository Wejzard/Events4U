<?php
require_once 'BaseService.php';
require_once __DIR__ . "/../dao/EventsDao.php";

class EventsService extends BaseService
{
  public function __construct()
  {
    parent::__construct(new EventsDao());
  }

  public function get_by_category($category) {
    return $this->dao->get_by_category($category);
  }

  public function search_by_name($name) {
    return $this->dao->search_by_name($name);
  }

  public function mine_with_stats($user_id) {
    return $this->dao->get_mine_with_stats((int)$user_id);
  }

  public function add_event($data) {

    if (strlen(trim($data['title'] ?? '')) < 3) {
      Flight::halt(400, json_encode(['message' => 'Title is too short.']));
    }

    if (empty($data['event_date']) || empty($data['event_time'])) {
      Flight::halt(400, json_encode(['message' => 'Date and time are required.']));
    }

    if (!is_numeric($data['price']) || $data['price'] < 0) {
      Flight::halt(400, json_encode(['message' => 'Price must be a non-negative number.']));
    }

    if (!isset($data['ticket_limit']) || !is_numeric($data['ticket_limit']) || (int)$data['ticket_limit'] < 0) {
      Flight::halt(400, json_encode(['message' => 'ticket_limit must be 0 or a positive integer.']));
    }
    $data['ticket_limit'] = (int)$data['ticket_limit'];

    $data['created_at'] = date('Y-m-d H:i:s');

    return $this->dao->add($data);
  }

  public function get_all_paginated($page = 1, $page_size = 9) {
    if ($page < 1) $page = 1;
    if ($page_size < 1) $page_size = 9;
    if ($page_size > 50) $page_size = 50;

    $offset = ($page - 1) * $page_size;

    $dao = new EventsDao();

    $data = $dao->get_all_paginated($offset, $page_size);
    $total = (int)$dao->count_all();

    return [
      "data" => $data,
      "page" => $page,
      "page_size" => $page_size,
      "total" => $total,
      "total_pages" => (int)ceil($total / $page_size)
    ];
  }
}
