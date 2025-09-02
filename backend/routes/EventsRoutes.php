<?php
require_once __DIR__ . '/../services/EventsService.php';

/**
 * @OA\Get(
 *     path="/events/category/{category}",
 *     summary="Get events by category",
 *     security={{"JWT":{}}},
 *     operationId="getEventsByCategory",
 *     tags={"Events"},
 *     @OA\Parameter(
 *         name="category",
 *         in="path",
 *         required=true,
 *         description="Category of the event",
 *         @OA\Schema(type="string", example="music")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of events in the specified category",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="event_id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Concert in the Park"),
 *                 @OA\Property(property="category", type="string", example="music"),
 *                 @OA\Property(property="date", type="string", example="2025-05-01")
 *             )
 *         )
 *     )
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
 *     security={{"JWT":{}}},
 *     operationId="searchEventsByName",
 *     tags={"Events"},
 *     @OA\Parameter(
 *         name="name",
 *         in="path",
 *         required=true,
 *         description="Name or partial name of the event",
 *         @OA\Schema(type="string", example="Concert")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of events that match the search term",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="event_id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Concert in the Park"),
 *                 @OA\Property(property="category", type="string", example="music"),
 *                 @OA\Property(property="date", type="string", example="2025-05-01")
 *             )
 *         )
 *     )
 * )
 */
Flight::route('GET /events/search/@name', function($name) {
         Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);
    Flight::json(Flight::events_service()->search_by_name($name));
});


/**
 * @OA\Post(
 *   path="/events",
 *   summary="Create a new event",
 *   security={{"JWT":{}}},
 *   tags={"Events"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *       mediaType="multipart/form-data",
 *       @OA\Schema(
 *         required={"title","category","event_date","event_time","location","price","image"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="category", type="string"),
 *         @OA\Property(property="event_date", type="string", example="2025-05-01"),
 *         @OA\Property(property="event_time", type="string", example="19:30"),
 *         @OA\Property(property="location", type="string"),
 *         @OA\Property(property="price", type="number", format="float"),
 *         @OA\Property(property="image", type="string", format="binary")
 *       )
 *     )
 *   ),
 *   @OA\Response(response=201, description="Event created successfully")
 * )
 */
Flight::route('POST /events', function () {
  // Roles (expects user already set by your token middleware)
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  // Minimal required fields (match EventsService expectations)
  $required = ['title','category','event_date','event_time','location','price'];
  foreach ($required as $f) {
    if (!isset($_POST[$f]) || $_POST[$f] === '') {
      Flight::json(["message" => "Missing field: $f"], 400);
      return;
    }
  }

  // Ensure a file arrived
  if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    Flight::json(["message" => "Image upload failed."], 400);
    return;
  }

  // Build a unique filename (no heavy validation by your choice)
  $origName  = $_FILES['image']['name'];
  $baseName  = pathinfo($origName, PATHINFO_FILENAME);
  $ext       = pathinfo($origName, PATHINFO_EXTENSION);
  $safeBase  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
  $filename  = $safeBase . '_' . uniqid('', true) . ($ext ? '.' . strtolower($ext) : '');

  // Absolute FS path to the REAL frontend/assets/img folder
  // __DIR__ = .../WebProject/backend
  $uploadDir = __DIR__ . '/../../frontend/assets/img/';

  if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
  }

  $target = $uploadDir . $filename;

  if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    Flight::json(["message" => "Failed to save image."], 500);
    return;
  }

  // user_id from JWT (set by middleware)
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

  // Payload to service/DAO (store only filename for image)
  $payload = [
    'title'       => $_POST['title'],
    'description' => $_POST['description'] ?? '',
    'category'    => $_POST['category'],
    'event_date'  => $_POST['event_date'],
    'event_time'  => $_POST['event_time'],
    'location'    => $_POST['location'],
    'price'       => (float)$_POST['price'],
    'image'       => $filename,
    'user_id'     => $userId,
  ];

  $service = new EventsService();
  $created = $service->add_event($payload);

  Flight::json([
    'message' => 'Event posted successfully.',
    'category' => $_POST['category'], 
    'data'    => $created
  ], 201);
});

/**
 * @OA\Put(
 *     path="/events/{id}",
 *     summary="Update an existing event by ID",
 *     security={{"JWT":{}}},
 *     operationId="updateEvent",
 *     tags={"Events"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Event ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "category", "date"},
 *             @OA\Property(property="name", type="string", example="Updated Concert in the Park"),
 *             @OA\Property(property="category", type="string", example="music"),
 *             @OA\Property(property="date", type="string", example="2025-06-01")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Event updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Event updated successfully."),
 *             @OA\Property(property="data", type="object", example={"event_id": 1, "name": "Updated Concert in the Park", "category": "music", "date": "2025-06-01"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized: Admins only",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Unauthorized: Admins only")
 *         )
 *     )
 * )
 */
Flight::route('PUT /events/@id', function($id) {
   
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
   
     $data = Flight::request()->data->getData();
    $service = new EventsService();
    Flight::json([
        'message' => 'Event updated successfully',
        'data' => $service->update($data, $id)
    ]);
});

/**
 * @OA\Delete(
 *     path="/events/{id}",
 *     summary="Delete an event by ID",
 *     security={{"JWT":{}}},
 *     operationId="deleteEvent",
 *     tags={"Events"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Event ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Event deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Event deleted successfully."),
 *             @OA\Property(property="data", type="object", example={"event_id": 1})
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized: Admins only",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Unauthorized: Admins only")
 *         )
 *     )
 * )
 */
Flight::route('DELETE /events/@id', function($id) {

    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $data = Flight::request()->data->getData();

    $service = new EventsService();
    $service->delete($id);

    Flight::json(['message' => 'Event deleted successfully']);
});