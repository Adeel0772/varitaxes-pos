<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('brands');
$filterUrl = $baseUrl . ($search ? '?search=' . urlencode($search) : '');
$canCreate = Auth::can('brands', 'create');
$canUpdate = Auth::can('brands', 'update');
$canDelete = Auth::can('brands', 'delete');
$showEditModal = !empty($editBrand);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0">Brands</h4>
        <small class="text-muted"><?= (int) $brands['total'] ?> brands</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= Auth::baseUrl('products') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Products</a>
        <?php if ($canCreate): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal"><i class="bi bi-plus-lg"></i> Add Brand</button>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= $baseUrl ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search brand name..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= Helpers::sortLink('name', 'Name', $sort ?? 'name', $dir ?? 'asc', $filterUrl) ?></th>
                    <th>Products</th>
                    <th><?= Helpers::sortLink('created_at', 'Created', $sort ?? 'name', $dir ?? 'asc', $filterUrl) ?></th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands['data'])): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No brands found.</td></tr>
                <?php else: ?>
                <?php foreach ($brands['data'] as $brand): ?>
                <tr>
                    <td><?= htmlspecialchars($brand['name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int) $brand['product_count'] ?></span></td>
                    <td><?= Helpers::formatDate($brand['created_at']) ?></td>
                    <td>
                        <?php if ($canUpdate): ?>
                        <a href="<?= $baseUrl ?>?edit=<?= (int) $brand['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                        <form method="post" action="<?= Auth::baseUrl('brands/delete') ?>" class="d-inline" data-confirm="Delete this brand?">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $brand['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($brands['data'])): ?>
    <div class="card-footer"><?= Helpers::paginationHtml($brands, $filterUrl) ?></div>
    <?php endif; ?>
</div>

<?php if ($canCreate): ?>
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= Auth::baseUrl('brands/store') ?>" class="modal-content">
            <?= Auth::csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title">Add Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canUpdate && $showEditModal): ?>
<div class="modal fade show" id="editBrandModal" tabindex="-1" style="display:block;background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
        <form method="post" action="<?= Auth::baseUrl('brands/update') ?>" class="modal-content">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $editBrand['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">Edit Brand</h5>
                <a href="<?= $baseUrl ?>" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editBrand['name']) ?>">
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= $baseUrl ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
