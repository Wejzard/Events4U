<?php
require_once __DIR__ . '/BaseDao.php';

class PaymentsDao extends BaseDao {

  protected $table_name;

  public function __construct()
  {
    $this->table_name = "payments";
    parent::__construct($this->table_name);
  }

  public function get_all(){
    return $this->query('SELECT * FROM ' . $this->table_name, []);
  }

  public function get_by_id($id){
    return $this->query_unique(
      'SELECT * FROM ' . $this->table_name . ' WHERE payment_id=:id',
      ['id' => $id]
    );
  }

  // ✅ FIX: delete must use payment_id (not id)
  public function delete($id) {
    $stmt = $this->connection->prepare("DELETE FROM payments WHERE payment_id = :id");
    $stmt->execute(["id" => (int)$id]);
    return $stmt->rowCount();
  }

  public function update($entity, $id, $id_column = "payment_id")
  {
    return parent::update($entity, $id, $id_column);
  }
}
