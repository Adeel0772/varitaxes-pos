<?php use Core\Auth; use Core\Helpers; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-upc-scan"></i> Barcode Labels</h4>
        <small class="text-muted">Select products and print CODE128 barcode labels</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= Auth::baseUrl('barcodes') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, code, barcode..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= ($categoryId ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                <a href="<?= Auth::baseUrl('barcodes') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<form method="post" action="<?= Auth::baseUrl('barcodes/print') ?>" target="_blank">
    <?= Auth::csrfField() ?>
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Products (<?= (int) $products['total'] ?>)</span>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <label class="mb-0 me-1">Label size:</label>
                <select name="label_size" class="form-select form-select-sm" style="width:auto;">
                    <option value="small">Small</option>
                    <option value="medium" selected>Medium</option>
                    <option value="large">Large</option>
                </select>
                <div class="form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="show_price" value="1" id="showPrice" checked>
                    <label class="form-check-label" for="showPrice">Show price</label>
                </div>
                <?php if (Auth::can('barcodes', 'print')): ?>
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-printer"></i> Print Selected</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Code</th>
                        <th>Product</th>
                        <th>Barcode</th>
                        <th class="text-end">Price</th>
                        <th style="width:90px;">Labels</th>
                        <th>Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products['data'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($products['data'] as $p):
                        $barcode = $p['barcode'] ?: \Core\Helpers::generateBarcode((int) $p['id'], (int) Auth::tenantId());
                    ?>
                    <tr>
                        <td><input type="checkbox" name="product_ids[]" value="<?= (int) $p['id'] ?>" class="product-check"></td>
                        <td><?= htmlspecialchars($p['product_code']) ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><code><?= htmlspecialchars($barcode) ?></code></td>
                        <td class="text-end"><?= Helpers::formatMoney($p['sale_price']) ?></td>
                        <td><input type="number" name="quantities[<?= (int) $p['id'] ?>]" class="form-control form-control-sm" value="1" min="1" max="99"></td>
                        <td>
                            <img src="<?= Auth::baseUrl('barcodes/image?code=' . urlencode($barcode)) ?>" alt="Barcode" style="max-height:40px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($products['last_page'] > 1): ?>
        <div class="card-footer">
            <?= Helpers::paginationHtml($products, Auth::baseUrl('barcodes') . '?' . http_build_query(array_filter(['search' => $search ?? '', 'category_id' => $categoryId ?? '']))) ?>
        </div>
        <?php endif; ?>
    </div>
</form>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.product-check').forEach(cb => cb.checked = this.checked);
});
</script>
