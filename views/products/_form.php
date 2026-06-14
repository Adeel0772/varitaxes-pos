<?php
use Core\Auth;
use Core\Helpers;

$isEdit = !empty($product);
$formAction = $isEdit ? Auth::baseUrl('products/update') : Auth::baseUrl('products/store');
$pageHeading = $isEdit ? 'Edit Product' : 'Add Product';
$old = $old ?? [];
$p = $isEdit ? $product : $old;
$canCreateCat = Auth::can('categories', 'create');
$canCreateBrand = Auth::can('brands', 'create');
$showPurchase = Auth::canSeePurchasePrice();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $pageHeading ?></h4>
    <a href="<?= Auth::baseUrl('products') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" action="<?= $formAction ?>" enctype="multipart/form-data" id="productForm"
      data-generate-code-url="<?= Auth::baseUrl('products/generate-code') ?>"
      data-category-store-url="<?= Auth::baseUrl('categories/ajax-store') ?>"
      data-brand-store-url="<?= Auth::baseUrl('brands/ajax-store') ?>">
    <?= Auth::csrfField() ?>
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Basic Information</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($p['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($p['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($p['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <div class="input-group">
                                <select name="category_id" id="category_id" class="form-select">
                                    <option value="">— Select —</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>" data-code="<?= htmlspecialchars($cat['code'] ?? '') ?>"
                                        <?= (int)($p['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?><?= $cat['code'] ? ' (' . htmlspecialchars($cat['code']) . ')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($canCreateCat): ?>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Add category"><i class="bi bi-plus"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <div class="input-group">
                                <select name="brand_id" id="brand_id" class="form-select">
                                    <option value="">— Select —</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int) $brand['id'] ?>" <?= (int)($p['brand_id'] ?? 0) === (int)$brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($canCreateBrand): ?>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addBrandModal" title="Add brand"><i class="bi bi-plus"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Code</label>
                            <div class="input-group">
                                <input type="text" name="product_code" id="product_code" class="form-control" placeholder="Auto-generated if empty" value="<?= htmlspecialchars($p['product_code'] ?? '') ?>">
                                <button type="button" class="btn btn-outline-primary" id="btnGenerateCode"><i class="bi bi-magic"></i> Generate</button>
                            </div>
                            <small class="text-muted">Format: category-type-size-origin</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control" placeholder="Auto-generated if empty" value="<?= htmlspecialchars($p['barcode'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product Type</label>
                            <input type="text" name="product_type" id="product_type" class="form-control" value="<?= htmlspecialchars($p['product_type'] ?? '') ?>" placeholder="e.g. Cricket Bat">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Size</label>
                            <input type="text" name="size" id="size" class="form-control" value="<?= htmlspecialchars($p['size'] ?? '') ?>" placeholder="e.g. Full Size">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($p['color'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Origin</label>
                            <select name="origin" id="origin" class="form-select">
                                <option value="">— Select —</option>
                                <?php foreach (['china' => 'China', 'pakistan' => 'Pakistan', 'other' => 'Other'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($p['origin'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>Pricing</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if ($showPurchase): ?>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Price</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= Helpers::getCurrencySymbol() ?></span>
                                <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars((string)($p['purchase_price'] ?? '0')) ?>">
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label">Sale Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= Helpers::getCurrencySymbol() ?></span>
                                <input type="number" name="sale_price" class="form-control" step="0.01" min="0.01" required value="<?= htmlspecialchars((string)($p['sale_price'] ?? '')) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Min Sale Price</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= Helpers::getCurrencySymbol() ?></span>
                                <input type="number" name="min_sale_price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars((string)($p['min_sale_price'] ?? '0')) ?>">
                            </div>
                            <small class="text-muted">Lowest allowed price at POS</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><strong>Product Image</strong></div>
                <div class="card-body text-center">
                    <?php if (!empty($p['image'])): ?>
                    <img src="<?= Helpers::productImageUrl((int) $p['id']) ?>" alt="" class="img-fluid rounded mb-3" style="max-height:180px">
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="text-muted d-block mt-2">Max 2MB. JPEG, PNG, WebP, GIF.</small>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>Inventory</strong></div>
                <div class="card-body">
                    <?php if (!$isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Initial Stock</label>
                        <input type="number" name="initial_stock" class="form-control" min="0" value="<?= (int)($p['initial_stock'] ?? 0) ?>">
                    </div>
                    <?php else: ?>
                    <p class="mb-2">Current stock: <strong><?= (int)($p['qty_in_stock'] ?? 0) ?></strong></p>
                    <p class="text-muted small">Use Inventory module to adjust stock.</p>
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control" min="0" value="<?= (int)($p['low_stock_threshold'] ?? 5) ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Update Product' : 'Create Product' ?>
            </button>
        </div>
    </div>
</form>

<?php if ($canCreateCat): ?>
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="catAjaxError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" id="ajax_cat_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" id="ajax_cat_code" class="form-control" maxlength="10" placeholder="e.g. 22">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveCategory">Save</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canCreateBrand): ?>
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="brandAjaxError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" id="ajax_brand_name" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveBrand">Save</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

