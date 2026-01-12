<?php
require_once __DIR__ . '/../services/TicketsService.php';
/*
 * @OA\Get(
 *     path="/tickets",
 *     summary="Get all tickets (Admin only)",
 *     security={{"ApiKey": {}}},
 *     operationId="getAllTickets",
 *     tags={"Tickets"},
 *     @OA\Response(
 *         response=200,
 *         description="List of all tickets",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="title", type="string", example="Concert Ticket"),
 *                 @OA\Property(property="price", type="number", format="float", example=100.50)
 *             )
 *         )
 *     )
 * )
 */
Flight::route('GET /tickets', function () {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $service = new TicketsService();

    Flight::json($service->fetch_all_tickets());
});
/*
 * @OA\Get(
 *     path="/tickets/{id}",
 *     summary="Get ticket by ID (Admin only)",
 *     security={{"ApiKey": {}}},
 *     operationId="getTicketById",
 *     tags={"Tickets"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ticket to retrieve",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ticket details",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="title", type="string", example="Concert Ticket"),
 *             @OA\Property(property="price", type="number", format="float", example=100.50)
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ticket not found"
 *     )
 * )
 */
Flight::route('GET /tickets/@id', function ($id) {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $service = new TicketsService();

    Flight::json($service->fetch_ticket_by_id($id));
});
/*
 * @OA\Post(
 *     path="/tickets",
 *     summary="Create a new ticket (Admin only)",
 *     security={{"ApiKey": {}}},
 *     operationId="createTicket",
 *     tags={"Tickets"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "price"},
 *             @OA\Property(property="title", type="string", example="Concert Ticket"),
 *             @OA\Property(property="price", type="number", format="float", example=100.50)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Ticket created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ticket created successfully"),
 *             @OA\Property(property="data", type="object", example={"id": 1, "title": "Concert Ticket", "price": 100.50})
 *         )
 *     )
 * )
 */
Flight::route('POST /tickets', function () {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    $service = new TicketsService();

    Flight::json([
        'message' => 'Ticket created successfully',
        'data' => $service->create_ticket($data)
    ]);
});
/*
 * @OA\Put(
 *     path="/tickets/{id}",
 *     summary="Update an existing ticket by ID (Admin only)",
 *     security={{"ApiKey": {}}},
 *     operationId="updateTicket",
 *     tags={"Tickets"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Ticket ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "price"},
 *             @OA\Property(property="title", type="string", example="Updated Concert Ticket"),
 *             @OA\Property(property="price", type="number", format="float", example=120.00)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ticket updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ticket updated successfully"),
 *             @OA\Property(property="data", type="object", example={"id": 1, "title": "Updated Concert Ticket", "price": 120.00})
 *         )
 *     )
 * )
 */
Flight::route('PUT /tickets/@id', function ($id) {
     Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    $service = new TicketsService();

    Flight::json([
        'message' => 'Ticket updated successfully',
        'data' => $service->modify_ticket($data, $id)
    ]);
});
/*
 * @OA\Delete(
 *     path="/tickets/{id}",
 *     summary="Delete a ticket by ID (Admin only)",
 *     security={{"ApiKey": {}}},
 *     operationId="deleteTicket",
 *     tags={"Tickets"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Ticket ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ticket deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ticket deleted successfully."),
 *             @OA\Property(property="data", type="object", example={"id": 1})
 *         )
 *     )
 * )
 */
Flight::route('DELETE /tickets/@id', function ($id) {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $service = new TicketsService();

    Flight::json([
        'message' => 'Ticket deleted successfully',
        'data' => $service->remove_ticket($id)
    ]);
});
