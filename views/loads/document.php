<?php
$pageTitle = strtoupper($type) . ' for Load ' . htmlspecialchars($load['reference_number']);
require __DIR__ . '/../layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">

                <h1 class="h5 mb-3">
                    <?= strtoupper($type) ?> – Load <?= htmlspecialchars($load['reference_number']) ?>
                </h1>

                <form method="post" action="/loads/document">

                    <input type="hidden" name="load_id" value="<?= (int)$load['load_id'] ?>">
                    <input type="hidden" name="document_type" value="<?= htmlspecialchars($type) ?>">
                    <input type="hidden" name="signature_data" id="signature_data">

                    <div class="mb-2">
                        <label class="form-label">Signature</label>
                        <canvas id="signature-pad"
                                class="border rounded w-100"
                                height="150"></canvas>
                        <div class="mt-2">
                            <button type="button"
                                    id="clear-signature"
                                    class="btn btn-sm btn-outline-secondary">
                                Clear Signature
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/loads/view?id=<?= (int)$load['load_id'] ?>"
                           class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Generate <?= strtoupper($type) ?>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('signature-pad');
const ctx = canvas.getContext('2d');
let drawing = false;

canvas.addEventListener('mousedown', () => drawing = true);
canvas.addEventListener('mouseup', () => {
    drawing = false;
    ctx.beginPath();
});
canvas.addEventListener('mousemove', e => {
    if (!drawing) return;
    const r = canvas.getBoundingClientRect();
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';
    ctx.lineTo(e.clientX - r.left, e.clientY - r.top);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(e.clientX - r.left, e.clientY - r.top);
});

document.getElementById('clear-signature').onclick = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
};

document.querySelector('form').addEventListener('submit', () => {
    document.getElementById('signature_data').value =
        canvas.toDataURL('image/png');
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
