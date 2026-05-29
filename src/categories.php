<?php 
include 'includes/header.php'; 
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

?>

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Layout Demo -->
    <div class="">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header form-title">Add Category</div>
                    <div class="card-body">
                        <form id="categoryForm">
                            <input type="hidden" name="cat-id" value="">
                            <input type="hidden" name="cat-action" value="add">
                            <div class="row mb-6">
                                <label for="cat-name" class="col-sm-2 col-form-label">Name</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="text" value="" name="cat-name" id="cat-name" />
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-sm-2 col-form-label" style="padding-top: unset !important;">Type</label>
                                <div class="col-sm-10">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="cat-type" id="cat-income" value="Income" />
                                        <label class="form-check-label" for="cat-income">Income</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="cat-type" id="cat-expense" value="Expense" checked/>
                                        <label class="form-check-label" for="cat-expense">Expense</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-6 limitGroup">
                                <label class="col-sm-2 col-form-label" for="transection-amt">Limit</label>
                                <div class="col-sm-10">
                                    <input id="transection-amt" name="monthly_limit" class="form-control" type="text" />
                                </div>
                            </div>
                            <div class="row justify-content-end">
                                <div class="col-sm-10">
                                    <button type="submit" class="btn btn-primary">Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">Categories</div>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Monthly Limit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <?php if(empty($categories)): ?>
                                    <tr><td colspan="4" class="text-center">No categories found.</td></tr>
                                <?php else: ?>
                                    <?php 
                                        foreach($categories as $cat): 
                                        $row['id'] = base64_encode($row['id']);    
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                        <td><span class="badge bg-label-<?= ($cat['type'] == 'Income') ? 'success' : 'danger' ?>"><?= $cat['type'] ?></span></td>
                                        <td><?= ($cat['monthly_limit'] > 0) ? '₹' . number_format($cat['monthly_limit'], 2) : '<span class="text-muted small">No Limit</span>' ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item edit-cat" href="javascript:void(0);" data-row="<?= json_encode($cat); ?>"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                                    <a class="dropdown-item delete-cat text-danger" href="javascript:void(0);" data-row="<?= json_encode($cat); ?>"><i class="icon-base bx bx-trash me-1 text-danger"></i> Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
$(document).ready(function() {
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'process/ajax.php', 
            type: 'POST',
            data: $(this).serialize() + "&action=save_category",
            success: function(res) {
                if(res.status === 'success') {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Something went wrong. Please try again.');
            }
        });
    });

    $('.edit-cat').on('click', function() {
        var rowData = $(this).data('row');

        $('#cat-id').val(rowData.id);
        $('#cat-name').val(rowData.name);
        $('#cat-action').val('edit');
        $('#transection-amt').val(rowData.monthly_limit);
        $('input[name="cat-type"][value="' + rowData.type + '"]').prop('checked', true);
        $('#categoryForm button[type="submit"]').text('Update');
        $('.form-title').text('Update Category');

        if(rowData.type === 'Income') {
            $('.limitGroup').hide();
        } else {
            $('.limitGroup').show();
        }
    });

    $(document).on('click', '.delete-cat', function() {
        var rowData = $(this).data('row');
        var catId = rowData.id;

        if (confirm('Are you sure you want to delete "' + rowData.name + '"? Related transactions might be affected.')) {
            $.ajax({
                url: 'process/ajax.php',
                type: 'POST',
                data: { 
                    id: catId, 
                    action: 'delete_category' 
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Something went wrong. Please try again.');
                }
            });
        }
    });

    $('input[name="type"]').change(function() {
        if (this.value == 'Income') {
            $('.limitGroup').fadeOut();
        } else {
            $('.limitGroup').fadeIn();
        }
    });
});
</script>