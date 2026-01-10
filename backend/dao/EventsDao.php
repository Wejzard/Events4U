<?php
require_once __DIR__ . '/BaseDao.php';

class EventsDao extends BaseDao {

    protected $table_name;

    public function __construct() {
        $this->table_name = "events";
        parent::__construct($this->table_name);
    }

    public function get_all($page = 1, $limit = 4) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT * FROM {$this->table_name} LIMIT :limit OFFSET :offset";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_by_id($id) {
        return $this->query_unique("SELECT * FROM {$this->table_name} WHERE event_id = :id", ['id' => $id]);
    }

    public function get_by_category($category) {
        $stmt = $this->connection->prepare("SELECT * FROM {$this->table_name} WHERE category = :category");
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search_by_name($name) {
        $stmt = $this->connection->prepare("SELECT * FROM {$this->table_name} WHERE title LIKE :name");
        $stmt->execute(['name' => "%$name%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add_event($data) {
        return $this->add($data);
    }

    public function delete($id) {
        $stmt = $this->connection->prepare("DELETE FROM {$this->table_name} WHERE event_id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function update($entity, $id, $id_column = "event_id") {
        return parent::update($entity, $id, $id_column);
    }

    public function get_all_paginated($offset, $limit) {
        $query = "
            SELECT *
            FROM {$this->table_name}
            ORDER BY event_date DESC, event_time DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count_all() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) AS total FROM {$this->table_name}");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    // ✅ FIXED: use ticket_limit and correct joins for sold/reserved
    public function get_mine_with_stats($user_id) {
  $sql = "
    SELECT
      e.event_id,
      e.title,
      e.category,
      e.event_date,
      e.event_time,
      e.location,
      e.ticket_limit,

      COALESCE(SUM(CASE WHEN o.status = 'paid' THEN ot.quantity ELSE 0 END), 0) AS sold_qty,
      COALESCE(SUM(CASE WHEN o.status = 'pending' THEN ot.quantity ELSE 0 END), 0) AS reserved_qty,

      CASE
        WHEN e.ticket_limit <= 0 THEN NULL
        ELSE (e.ticket_limit - COALESCE(SUM(CASE WHEN o.status IN ('paid','pending') THEN ot.quantity ELSE 0 END), 0))
      END AS remaining_qty

    FROM events e
    LEFT JOIN tickets t ON t.event_id = e.event_id
    LEFT JOIN order_tickets ot ON ot.ticket_id = t.ticket_id
    LEFT JOIN orders o ON o.order_id = ot.order_id

    WHERE e.user_id = :uid
    GROUP BY e.event_id
    ORDER BY e.event_date DESC, e.event_time DESC
  ";

  $stmt = $this->connection->prepare($sql);
  $stmt->execute(["uid" => (int)$user_id]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
