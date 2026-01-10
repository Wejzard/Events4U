/*
$(document).ready(function () {
  restClient.get("/users", function (users) {
    let tableBody = $("#users-table-body");
    tableBody.empty();

    users.forEach(user => {
      tableBody.append(`
        <tr>
          <td>${user.id}</td>
          <td>${user.name}</td>
          <td>${user.email}</td>
          <td>${user.role}</td>
          <td>
            <button class="btn btn-sm btn-warning edit-user" data-id="${user.id}">Edit</button>
            <button class="btn btn-sm btn-danger delete-user" data-id="${user.id}">Delete</button>
          </td>
        </tr>
      `);
    });
  });
});
// Delete
$(document).on("click", ".delete-user", function () {
  const userId = $(this).data("id");
  if (confirm("Are you sure you want to delete this user?")) {
    restClient.delete(`/users/${userId}`, function () {
      toastr.success("User deleted.");
      location.reload();
    });
  }
});

// Edit — can be wired to open modal with user info
*/