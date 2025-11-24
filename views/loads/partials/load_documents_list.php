<?php if (empty($docs)): ?>
    <p class="text-muted">No documents uploaded.</p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($docs as $doc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= htmlspecialchars($doc['original_name']) ?></strong>
                    <br>
                    <small class="text-muted"><?= $doc['uploaded_at'] ?></small>
                </div>

                <div class="btn-group">
                    <a href="/uploads/<?= $doc['file_name'] ?>" target="_blank" class="btn btn-sm btn-primary">
                        View
                    </a>
                    <a href="/includes/loads/delete_document.php?id=<?= $doc['id'] ?>&load_id=<?= $loadId ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this document?')">
                        Delete
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
