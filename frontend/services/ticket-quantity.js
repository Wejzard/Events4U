// frontend/services/ticket-quantity.js
(function () {
  function clampQty(v) {
    let n = parseInt(v, 10);
    if (Number.isNaN(n)) n = 1;
    if (n < 1) n = 1;
    if (n > 20) n = 20;
    return n;
  }

  function setQty(n) {
    const $q = $("#ticketQuantity");
    if (!$q.length) return;
    $q.val(clampQty(n));
  }

  // Works even with SPApp (delegated handlers)
  $(document).on("click", "#qtyMinusBtn", function () {
    const cur = clampQty($("#ticketQuantity").val());
    setQty(cur - 1);
  });

  $(document).on("click", "#qtyPlusBtn", function () {
    const cur = clampQty($("#ticketQuantity").val());
    setQty(cur + 1);
  });

  $(document).on("input", "#ticketQuantity", function () {
    setQty($(this).val());
  });
})();
