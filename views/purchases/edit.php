<?php
use Core\Auth;
use Core\Helpers;

$canAddSupplier = Auth::can('suppliers', 'create');
$items = $purchase['items'] ?? [];
if (empty($items)) {
    $items = [['product_id' => '', 'qty' => 1, 'purchase_price' => 0, 'product_name' => '', 'product_code' => '']];
}
?>
<div class="mb-4">
    <a href="<?= Auth::baseUrl('purchases/view?id=' . (int) $purchase['id']) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Purchase</a>
</div>

<h4 class="mb-4"><i class="bi bi-pencil"></i> Edit Purchase #<?= (int) $purchase['id'] ?></h4>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Purchases can only be edited on the same day they were recorded. Stock levels will be recalculated.
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= Auth::baseUrl('purchases/update') ?>" id="purchaseForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $purchase['id'] ?>">

    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>Purchase Details</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Supplier *</label>
                    <div class="input-group">
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">Select supplier...</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (int) $purchase['supplier_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?><?= $s['city'] ? ' — ' . htmlspecialchars($s['city']) : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($canAddSupplier): ?>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Purchase Date *</label>
                    <input type="date" name="purchase_date" class="form-control" required
                           value="<?= htmlspecialchars($purchase['purchase_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control"
                           value="<?= htmlspecialchars($purchase['notes'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Products</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addPurchaseRow">
                <i class="bi bi-plus-lg"></i> Add Line
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:45%">Product</th>
                            <th style="width:12%">Qty</th>
                            <th style="width:18%">Purchase Price</th>
                            <th style="width:18%">Line Total</th>
                            <th style="width:7%"></th>
                        </tr>
                    </thead>
                    <tbody id="purchaseItemsBody">
                        <?php foreach ($items as $i => $item): ?>
                        <tr data-row="<?= $i ?>">
                            <td class="position-relative">
                                <input type="hidden" name="items[<?= $i ?>][product_id]" class="item-product-id" value="<?= (int) $item['product_id'] ?>">
                                <input type="hidden" name="items[<?= $i ?>][product_name]" class="item-product-name" value="<?= htmlspecialchars($item['product_name'] ?? '') ?>">
                                <input type="hidden" name="items[<?= $i ?>][product_code]" class="item-product-code" value="<?= htmlspecialchars($item['product_code'] ?? '') ?>">
                                <input type="text" class="form-control form-control-sm product-search" placeholder="Search product..."
                                       value="<?= htmlspecialchars(($item['product_name'] ?? '') . ' (' . ($item['product_code'] ?? '') . ')') ?>" autocomplete="off">
                                <div class="list-group position-absolute w-100 shadow-sm product-results d-none" style="z-index:1050;max-height:200px;overflow-y:auto;"></div>
                            </td>
                            <td><input type="number" name="items[<?= $i ?>][qty]" class="form-control form-control-sm item-qty" min="1" value="<?= (int) $item['qty'] ?>" required></td>
                            <td><input type="number" name="items[<?= $i ?>][purchase_price]" class="form-control form-control-sm item-price" min="0" step="0.01" value="<?= htmlspecialchars((string) $item['purchase_price']) ?>" required></td>
                            <td class="line-total align-middle"><?= Helpers::formatMoney((int) $item['qty'] * (float) $item['purchase_price']) ?></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Grand Total</td>
                            <td class="fw-bold" id="purchaseGrandTotal"><?= Helpers::formatMoney($purchase['total_amount']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Purchase</button>
        <a href="<?= Auth::baseUrl('purchases/view?id=' . (int) $purchase['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php if ($canAddSupplier): ?>
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="inlineSupplierForm">
                <?= Auth::csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $formMode = 'edit'; require __DIR__ . '/_form_scripts.php'; ?>
