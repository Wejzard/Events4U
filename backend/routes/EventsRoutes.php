<?php
require_once __DIR__ . '/../services/EventsService.php';
require_once __DIR__ . '/../dao/EventsDao.php';
require_once __DIR__ . '/../dao/OrdersDao.php';

  /**
   * @OA\Get(
   *     path="/events/category/{category}",
   *     summary="Get events by category",
   *     operationId="getEventsByCategory",
   *     tags={"Events"},
   *     security={{"ApiKey": {}}},
   *     @OA\Parameter(
   *         name="category",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="string", example="rock")
   *     ),
   *     @OA\Response(response=200, description="Events list"),
   *     @OA\Response(response=401, description="Unauthorized"),
   *     @OA\Response(response=403, description="Forbidden")
   * )
   */
Flight::route('GET /events/category/@category', function($category) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);
  Flight::json(Flight::events_service()->get_by_category($category));
});

  /**
   * @OA\Get(
   *     path="/events/search/{name}",
   *     summary="Search events by name",
   *     operationId="searchEventsByName",
   *     tags={"Events"},
   *     security={{"ApiKey": {}}},
   *     @OA\Parameter(
   *         name="name",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="string", example="concert")
   *     ),
   *     @OA\Response(response=200, description="Events list"),
   *     @OA\Response(response=401, description="Unauthorized"),
   *     @OA\Response(response=403, description="Forbidden")
   * )
   */
Flight::route('GET /events/search/@name', function($name) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);
  Flight::json(Flight::events_service()->search_by_name($name));
});

/**
 * ✅ NEW: events posted by me + sold/reserved/remaining stats (Settings -> My Event Sales)
 *
 * @OA\Get(
 *     path="/events/mine",
 *     summary="Get my events with sales/reservation stats",
 *     operationId="getMyEventsWithStats",
 *     tags={"Events"},
 *     security={{"ApiKey": {}}},
 *     @OA\Response(response=200, description="My events with stats"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('GET /events/mine', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $user = Flight::get('user');
  $userId = (int)($user->user_id ?? $user->id ?? 0);
  if (!$userId) {
    Flight::halt(401, json_encode(["message" => "Invalid token: missing user id."]));
  }

  // returns [] if no events, which is perfect for frontend
  Flight::json(Flight::events_service()->mine_with_stats($userId));
});

/**
 * @OA\Post(
 *     path="/events",
 *     summary="Create an event (multipart/form-data with image upload)",
 *     operationId="createEvent",
 *     tags={"Events"},
 *     security={{"ApiKey": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"title","category","ticket_limit","event_date","event_time","location","price","image"},
 *                 @OA\Property(property="title", type="string", example="My Event"),
 *                 @OA\Property(property="description", type="string", example="Event description"),
 *                 @OA\Property(property="category", type="string", example="rock"),
 *                 @OA\Property(property="ticket_limit", type="integer", example=100),
 *                 @OA\Property(property="event_date", type="string", example="2026-02-01"),
 *                 @OA\Property(property="event_time", type="string", example="20:00"),
 *                 @OA\Property(property="location", type="string", example="Sarajevo"),
 *                 @OA\Property(property="price", type="number", format="float", example=20.0),
 *                 @OA\Property(
 *                     property="image",
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Event created"),
 *     @OA\Response(response=400, description="Validation error"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('POST /events', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $required = ['title','category','ticket_limit','event_date','event_time','location','price'];
  foreach ($required as $f) {
    if (!isset($_POST[$f]) || $_POST[$f] === '') {
      Flight::json(["message" => "Missing field: $f"], 400);
      return;
    }
  }

  if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    Flight::json(["message" => "Image upload failed."], 400);
    return;
  }

  $origName  = $_FILES['image']['name'];
  $baseName  = pathinfo($origName, PATHINFO_FILENAME);
  $ext       = pathinfo($origName, PATHINFO_EXTENSION);
  $safeBase  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
  $filename  = $safeBase . '_' . uniqid('', true) . ($ext ? '.' . strtolower($ext) : '');

  $uploadDir = __DIR__ . '/../../frontend/assets/img/';
  if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

  $target = $uploadDir . $filename;

  if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    Flight::json(["message" => "Failed to save image."], 500);
    return;
  }

  $user = Flight::get('user');
  if (!$user) {
    Flight::json(["message" => "Invalid token: user not found"], 401);
    return;
  }
  $userId = isset($user->user_id) ? (int)$user->user_id : (int)($user->id ?? 0);
  if (!$userId) {
    Flight::json(["message" => "Invalid token: missing user_id"], 401);
    return;
  }

  $payload = [
    'title'        => $_POST['title'],
    'description'  => $_POST['description'] ?? '',
    'category'     => $_POST['category'],
    'ticket_limit' => (int)$_POST['ticket_limit'],
    'event_date'   => $_POST['event_date'],
    'event_time'   => $_POST['event_time'],
    'location'     => $_POST['location'],
    'price'        => (float)$_POST['price'],
    'image'        => $filename,
    'user_id'      => $userId,
  ];

  $service = new EventsService();
  $created = $service->add_event($payload);

  Flight::json([
    'message'  => 'Event posted successfully.',
    'category' => $_POST['category'],
    'data'     => $created
  ], 201);
});

/**
 * @OA\Get(
 *     path="/events",
 *     summary="Get events (paginated)",
 *     operationId="getEventsPaginated",
 *     tags={"Events"},
 *     security={{"ApiKey": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="page_size", in="query", required=false, @OA\Schema(type="integer", example=9)),
 *     @OA\Response(response=200, description="Paginated events"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('GET /events', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $page = (int)(Flight::request()->query['page'] ?? 1);
  $page_size = (int)(Flight::request()->query['page_size'] ?? 9);

  Flight::json(Flight::events_service()->get_all_paginated($page, $page_size));
});

Flight::route('PUT /events/@id', function($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);

  $data = Flight::request()->data->getData();
  $service = new EventsService();
  Flight::json([
    'message' => 'Event updated successfully',
    'data' => $service->update($data, $id)
  ]);
});

Flight::route('DELETE /events/@id', function($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);

  $service = new EventsService();
  $service->delete($id);

  Flight::json(['message' => 'Event deleted successfully']);
});

/**
 * @OA\Get(
 *     path="/events/{id}",
 *     summary="Get event by ID",
 *     operationId="getEventById",
 *     tags={"Events"},
 *     security={{"ApiKey": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(response=200, description="Event details"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden"),
 *     @OA\Response(response=404, description="Not found")
 * )
 */
Flight::route('GET /events/@id', function($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);
  Flight::json(Flight::events_service()->get_by_id($id));
});

/**
 * ✅ Availability endpoint for SOLD OUT UI
 *
 * @OA\Get(
 *     path="/events/{id}/availability",
 *     summary="Get ticket availability for an event",
 *     operationId="getEventAvailability",
 *     tags={"Events"},
 *     security={{"ApiKey": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(response=200, description="Availability"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden"),
 *     @OA\Response(response=404, description="Event not found")
 * )
 */
Flight::route('GET /events/@id/availability', function($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $eventId = (int)$id;

  $eventsDao = new EventsDao();
  $ordersDao = new OrdersDao();

  $event = $eventsDao->get_by_id($eventId);
  if (!$event) {
    Flight::halt(404, json_encode(["message" => "Event not found."]));
  }

  $limit = (int)($event['ticket_limit'] ?? 0);

  if ($limit <= 0) {
    Flight::json([
      "event_id" => $eventId,
      "ticket_limit" => 0,
      "used_qty" => 0,
      "remaining_qty" => null,
      "sold_out" => false
    ]);
    return;
  }

  $used = (int)$ordersDao->used_qty_for_event($eventId);
  $remaining = $limit - $used;
  if ($remaining < 0) $remaining = 0;

  Flight::json([
    "event_id" => $eventId,
    "ticket_limit" => $limit,
    "used_qty" => $used,
    "remaining_qty" => $remaining,
    "sold_out" => ($remaining <= 0)
  ]);
});
