<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('products');
$filterUrl = $baseUrl . '?' . http_build_query(array_filter([
    'search'      => $search ?? '',
    'category_id' => $categoryId ?? '',
    'brand_id'    => $brandId ?? '',
    'status'      => $status ?? '',
    'sort'        => $sort ?? 'created_at',
    'dir'         => $dir ?? 'desc',
]));
$canCreate = Auth::can('products', 'create');
$canUpdate = Auth::can('products', 'update');
$canDelete = Auth::can('products', 'delete');
$showPurchase = Auth::canSeePurchasePrice();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0">Products</h4>
        <small class="text-muted"><?= (int) $products['total'] ?> total products</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (Auth::can('categories', 'read')): ?>
        <a href="<?= Auth::baseUrl('categories') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-tags"></i> Categories</a>
        <?php endif; ?>
        <?php if (Auth::can('brands', 'read')): ?>
        <a href="<?= Auth::baseUrl('brands') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bookmark"></i> Brands</a>
        <?php endif; ?>
        <?php if (Auth::can('attributes', 'read')): ?>
        <a href="<?= Auth::baseUrl('attributes') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-sliders"></i> Attributes</a>
        <?php endif; ?>
        <?php if ($canCreate): ?>
        <a href="<?= Auth::baseUrl('products/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= $baseUrl ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, code, barcode..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= ($categoryId ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int) $brand['id'] ?>" <?= ($brandId ?? 0) == $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i> Filter</button>
                <a href="<?= $baseUrl ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px"></th>
                    <th><?= Helpers::sortLink('name', 'Name', $sort ?? 'created_at', $dir ?? 'desc', $filterUrl) ?></th>
                    <th><?= Helpers::sortLink('product_code', 'Code', $sort ?? 'created_at', $dir ?? 'desc', $filterUrl) ?></th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th><?= Helpers::sortLink('sale_price', 'Sale Price', $sort ?? 'created_at', $dir ?? 'desc', $filterUrl) ?></th>
                    <?php if ($showPurchase): ?>
                    <th><?= Helpers::sortLink('purchase_price', 'Purchase', $sort ?? 'created_at', $dir ?? 'desc', $filterUrl) ?></th>
                    <?php endif; ?>
                    <th><?= Helpers::sortLink('qty_in_stock', 'Stock', $sort ?? 'created_at', $dir ?? 'desc', $filterUrl) ?></th>
                    <th><?= Helpers::sortLink('status', 'Status', $sort ?? 'created_at', $dir ?? 'desc', $filterUrl) ?></th>
                    <th style="width:140px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products['data'])): ?>
                <tr><td colspan="<?= $showPurchase ? 10 : 9 ?>" class="text-center text-muted py-4">No products found.</td></tr>
                <?php else: ?>
                <?php foreach ($products['data'] as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['image']): ?>
                        <img src="<?= \Core\Helpers::productImageUrl((int) $p['id']) ?>" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover">
                        <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center bg-light rounded text-muted" style="width:40px;height:40px"><i class="bi bi-image"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= Auth::baseUrl('products/view?id=' . $p['id']) ?>" class="text-decoration-none fw-medium"><?= htmlspecialchars($p['name']) ?></a>
                        <?php if ($p['barcode']): ?><br><small class="text-muted"><?= htmlspecialchars($p['barcode']) ?></small><?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($p['product_code']) ?></code></td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['brand_name'] ?? '—') ?></td>
                    <td><?= Helpers::formatMoney($p['sale_price']) ?></td>
                    <?php if ($showPurchase): ?>
                    <td><?= Helpers::formatMoney($p['purchase_price']) ?></td>
                    <?php endif; ?>
                    <td>
                        <?php $stock = (int) $p['qty_in_stock']; ?>
                        <span class="badge bg-<?= $stock <= 0 ? 'danger' : ($stock <= 5 ? 'warning text-dark' : 'success') ?>"><?= $stock ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($p['status']) ?></span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= Auth::baseUrl('products/view?id=' . $p['id']) ?>" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            <?php if ($canUpdate): ?>
                            <a href="<?= Auth::baseUrl('products/edit?id=' . $p['id']) ?>" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <form method="post" action="<?= Auth::baseUrl('products/delete') ?>" class="d-inline" data-confirm="Delete this product?">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($products['data'])): ?>
    <div class="card-footer">
        <?= Helpers::paginationHtml($products, $filterUrl) ?>
    </div>
    <?php endif; ?>
</div>
