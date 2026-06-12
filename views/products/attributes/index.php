<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('attributes');
$filterUrl = $baseUrl . ($search ? '?search=' . urlencode($search) : '');
$canCreate = Auth::can('attributes', 'create');
$canUpdate = Auth::can('attributes', 'update');
$canDelete = Auth::can('attributes', 'delete');
$showEditModal = !empty($editAttribute);
$showValuesModal = !empty($valuesAttribute);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0">Product Attributes</h4>
        <small class="text-muted"><?= (int) $attributes['total'] ?> attributes</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= Auth::baseUrl('products') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Products</a>
        <?php if ($canCreate): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttributeModal"><i class="bi bi-plus-lg"></i> Add Attribute</button>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= $baseUrl ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search attribute name..." value="<?= htmlspecialchars($search ?? '') ?>">
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
                    <th>Attribute Name</th>
                    <th>Values</th>
                    <th>Created</th>
                    <th style="width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attributes['data'])): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No attributes found.</td></tr>
                <?php else: ?>
                <?php foreach ($attributes['data'] as $attr): ?>
                <tr>
                    <td><?= htmlspecialchars($attr['attribute_name']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= (int) $attr['value_count'] ?></span></td>
                    <td><?= Helpers::formatDate($attr['created_at']) ?></td>
                    <td>
                        <a href="<?= $baseUrl ?>?values=<?= (int) $attr['id'] ?>" class="btn btn-sm btn-outline-info" title="Manage values"><i class="bi bi-list-ul"></i></a>
                        <?php if ($canUpdate): ?>
                        <a href="<?= $baseUrl ?>?edit=<?= (int) $attr['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                        <form method="post" action="<?= Auth::baseUrl('attributes/delete') ?>" class="d-inline" data-confirm="Delete this attribute and its values?">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $attr['id'] ?>">
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
    <?php if (!empty($attributes['data'])): ?>
    <div class="card-footer"><?= Helpers::paginationHtml($attributes, $filterUrl) ?></div>
    <?php endif; ?>
</div>

<?php if ($canCreate): ?>
<div class="modal fade" id="addAttributeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= Auth::baseUrl('attributes/store') ?>" class="modal-content">
            <?= Auth::csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title">Add Attribute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Attribute Name <span class="text-danger">*</span></label>
                    <input type="text" name="attribute_name" class="form-control" required placeholder="e.g. Size, Color">
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
<div class="modal fade show" id="editAttributeModal" tabindex="-1" style="display:block;background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
        <form method="post" action="<?= Auth::baseUrl('attributes/update') ?>" class="modal-content">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $editAttribute['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">Edit Attribute</h5>
                <a href="<?= $baseUrl ?>" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Attribute Name <span class="text-danger">*</span></label>
                    <input type="text" name="attribute_name" class="form-control" required value="<?= htmlspecialchars($editAttribute['attribute_name']) ?>">
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

<?php if ($showValuesModal): ?>
<div class="modal fade show" id="valuesModal" tabindex="-1" style="display:block;background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Values for: <?= htmlspecialchars($valuesAttribute['attribute_name']) ?></h5>
                <a href="<?= $baseUrl ?>" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <?php if ($canUpdate): ?>
                <form method="post" action="<?= Auth::baseUrl('attributes/add-value') ?>" class="row g-2 mb-4">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="attribute_id" value="<?= (int) $valuesAttribute['id'] ?>">
                    <div class="col">
                        <input type="text" name="value" class="form-control" placeholder="New value..." required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Add</button>
                    </div>
                </form>
                <?php endif; ?>

                <?php if (empty($attributeValues)): ?>
                <p class="text-muted text-center py-3">No values yet. Add values above.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr><th>Value</th><th>Added</th><?php if ($canDelete): ?><th></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attributeValues as $val): ?>
                            <tr>
                                <td><?= htmlspecialchars($val['value']) ?></td>
                                <td><?= Helpers::formatDate($val['created_at']) ?></td>
                                <?php if ($canDelete): ?>
                                <td class="text-end">
                                    <form method="post" action="<?= Auth::baseUrl('attributes/delete-value') ?>" class="d-inline" data-confirm="Delete this value?">
                                        <?= Auth::csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $val['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a href="<?= $baseUrl ?>" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
