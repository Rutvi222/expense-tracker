<?php 
    include 'includes/header.php';
    require_once 'config/db.php';

    $user_id = $_SESSION['user_id'];

    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    $sql_total = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ? AND is_deleted = '0'";
    $stmt = $pdo->prepare($sql_total);
    $stmt->execute([$user_id]);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT trans.*, cat.name as category_name 
            FROM transactions AS trans 
            LEFT JOIN categories AS cat ON trans.category_id = cat.id 
            WHERE trans.user_id = ? AND trans.is_deleted = '0' 
            ORDER BY trans.transaction_date DESC,id DESC 
            LIMIT $limit OFFSET $offset";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Query Error: " . $e->getMessage());
    }

    $badge_classes = ['bg-label-primary', 'bg-label-warning', 'bg-label-info'];
?>

<style>
    .title-section{display: flex; justify-content: space-between; align-items: center;}
    .histroy-table tbody tr:last-child td:first-child {border-bottom-left-radius: var(--bs-card-inner-border-radius);}
    .histroy-table tbody tr:last-child td:last-child {border-bottom-right-radius: var(--bs-card-inner-border-radius);}
</style>

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Layout Demo -->
    <div class="">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-bg-dark title-section">
                        <div>
                            <h4 class="fw-bold py-1 mb-0 text-white">Transactions</h4>
                        </div>
                        <div>
                            <button class="btn btn-primary shadow-sm">Filter</button>
                            <button class="btn btn-outline-secondary shadow-sm"><i class="bx bx-export me-1"></i> Export</button>
                        </div>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table histroy-table">
                            <thead>
                                <tr class="table-primary">
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Mode</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <?php if (count($transactions) > 0): ?>
                                    <?php foreach ($transactions as $index => $row):
                                        $isIncome = ($row['type'] == 'Income');
                                        $amtClass = $isIncome ? 'text-success' : 'text-danger';
                                        $amtSign = $isIncome ? '+' : '-';
                                        $amtIcon = $isIncome ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt';
                                        $badge_class = ($isIncome) ? 'bg-label-success' : $badge_classes[crc32($row['category_name']) % count($badge_classes)];
                                        $row['id'] = base64_encode($row['id']);
                                    ?>
                                        <tr class="transaction-row <?= ($index % 2 == 0) ? 'table-active' : 'table-dark' ?>">
                                            <td><?= date('d M, Y', strtotime($row['transaction_date'])) ?></td>
                                            <td>
                                                <span class="fw-bold <?= $amtClass ?>">
                                                    <?= $amtSign ?> ₹<?= number_format($row['amount'], 2) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row['note']) ?></td>
                                            <td>
                                                <span class="badge <?= $badge_class ?> me-1">
                                                    <?= htmlspecialchars($row['category_name'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= htmlspecialchars(ucfirst($row['transaction_mode'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item edit-cat" href="javascript:void(0);" data-row="<?= json_encode($row); ?>"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                                        <a class="dropdown-item delete-cat text-danger" href="javascript:void(0);" data-row="<?= json_encode($row); ?>"><i class="icon-base bx bx-trash me-1 text-danger"></i> Delete</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="6" class="text-left py-5">
                                            <div class="btn-group">
                                                <a href="?page=<?= $page - 1 ?>" class="btn btn-outline-secondary shadow-sm prev-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                    <i class="icon-base bx bx-chevron-left scaleX-n1-rtl icon-sm"></i>
                                                </a>
                                                <a href="?page=<?= $page + 1 ?>" class="btn btn-outline-secondary shadow-sm next-btn <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                    <i class="icon-base bx bx-chevron-right scaleX-n1-rtl icon-sm"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">No transactions found. <a href="add_expense.php">Add your first expense!</a></div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Layout Demo -->
</div>
<!-- / Content -->
<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function () {

});
</script>