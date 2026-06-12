<?php
use Core\Auth;

$searchUrl = Auth::baseUrl('products/search');
$supplierAjaxUrl = Auth::baseUrl('suppliers/ajax-store');
$canAddSupplier = Auth::can('suppliers', 'create');
?>
<div id="purchaseFormConfig" class="d-none"
     data-search-url="<?= htmlspecialchars($searchUrl) ?>"
     data-supplier-ajax-url="<?= htmlspecialchars($supplierAjaxUrl) ?>"
     data-can-add-supplier="<?= $canAddSupplier ? '1' : '0' ?>"></div>
