<?php use Core\Auth; ?>

<h4 class="mb-4"><i class="bi bi-gear"></i> Shop Settings</h4>



<?php if (!empty($errors)): ?>

<div class="alert alert-danger">

    <ul class="mb-0">

        <?php foreach ($errors as $e): ?>

        <li><?= htmlspecialchars($e) ?></li>

        <?php endforeach; ?>

    </ul>

</div>

<?php endif; ?>



<div class="card shadow-sm">

    <div class="card-body">

        <form method="POST" action="<?= Auth::baseUrl('settings/update') ?>" enctype="multipart/form-data">

            <?= Auth::csrfField() ?>



            <h5 class="border-bottom pb-2 mb-3">Branding</h5>

            <div class="row mb-4">

                <div class="col-md-8">

                    <div class="mb-3">

                        <label class="form-label">Shop Logo</label>

                        <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">

                        <small class="text-muted">JPEG, PNG, WebP or GIF. Max 2MB.</small>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Invoice Header *</label>

                        <input type="text" name="invoice_header" class="form-control" required

                               value="<?= htmlspecialchars($settings['invoice_header'] ?? '') ?>"

                               placeholder="Shop name and tagline for invoices">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Invoice Footer</label>

                        <textarea name="invoice_footer" class="form-control" rows="2"

                                  placeholder="Thank you message, return policy, etc."><?= htmlspecialchars($settings['invoice_footer'] ?? '') ?></textarea>

                    </div>

                </div>

                <div class="col-md-4">

                    <?php if (!empty($shop['logo'])): ?>

                    <label class="form-label">Current Logo</label>

                    <div class="border rounded p-2 text-center bg-light">

                        <img src="<?= Auth::baseUrl('settings/logo') ?>" alt="Shop logo"

                             class="img-fluid" style="max-height: 120px;">

                    </div>

                    <?php else: ?>

                    <div class="border rounded p-4 text-center text-muted bg-light">

                        <i class="bi bi-image fs-1"></i>

                        <p class="mb-0 small">No logo uploaded</p>

                    </div>

                    <?php endif; ?>

                </div>

            </div>



            <h5 class="border-bottom pb-2 mb-3">General</h5>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">Shop Phone</label>

                    <input type="text" name="shop_phone" class="form-control"

                           value="<?= htmlspecialchars($settings['shop_phone'] ?? '') ?>">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">Currency Symbol</label>

                    <input type="text" name="currency_symbol" class="form-control"

                           value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'Rs.') ?>">

                </div>

            </div>



            <h5 class="border-bottom pb-2 mb-3 mt-2">POS &amp; Invoices</h5>

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">Default Payment Method</label>

                    <select name="default_payment_method" class="form-select">

                        <?php foreach ($paymentMethods as $method): ?>

                        <option value="<?= $method ?>"

                            <?= ($settings['default_payment_method'] ?? 'cash') === $method ? 'selected' : '' ?>>

                            <?= ucfirst($method) ?>

                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">Invoice Format</label>

                    <select name="invoice_format" class="form-select">

                        <option value="a4" <?= ($settings['invoice_format'] ?? 'a4') === 'a4' ? 'selected' : '' ?>>A4</option>

                        <option value="carbon" <?= ($settings['invoice_format'] ?? '') === 'carbon' ? 'selected' : '' ?>>Carbon / Thermal</option>

                    </select>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">Receipt Copies</label>

                    <input type="number" name="receipt_copies" class="form-control" min="1" max="5"

                           value="<?= htmlspecialchars($settings['receipt_copies'] ?? '1') ?>">

                </div>

            </div>

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">Low Stock Alert (days)</label>

                    <input type="number" name="low_stock_days" class="form-control" min="1"

                           value="<?= htmlspecialchars($settings['low_stock_days'] ?? '7') ?>">

                    <small class="text-muted">Days of stock remaining before low-stock alert</small>

                </div>

                <div class="col-md-4 mb-3 d-flex align-items-end">

                    <div class="form-check mb-3">

                        <input type="checkbox" name="show_barcode_on_invoice" class="form-check-input" value="1"

                               id="show_barcode" <?= !empty($settings['show_barcode_on_invoice']) && $settings['show_barcode_on_invoice'] !== '0' ? 'checked' : '' ?>>

                        <label class="form-check-label" for="show_barcode">Show barcode on invoice</label>

                    </div>

                </div>

            </div>



            <div class="mt-3">

                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Settings</button>

            </div>

        </form>

    </div>

</div>


