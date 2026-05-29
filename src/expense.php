<?php include 'includes/header.php'; ?>

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Layout Demo -->
    <div class="">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Add New Transaction</div>
                    <div class="card-body">
                        <form id="expenseForm">
                            <div class="row mb-6">
                                <label class="col-sm-2 col-form-label">Transaction Type</label>
                                <div class="col-sm-10">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="transection-type" id="transection-income" value="Income" />
                                        <label class="form-check-label" for="transection-income">Income</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="transection-type" id="transection-expense" value="Expense" />
                                        <label class="form-check-label" for="transection-expense">Expense</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label for="transection-date" class="col-sm-2 col-form-label">Date</label>
                                <div class="col-sm-10">
                                    <input class="form-control" type="date" value="" id="transection-date" />
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-sm-2 col-form-label" for="transection-amt">Amount</label>
                                <div class="col-sm-10">
                                    <input id="transection-amt" class="form-control" type="number" />
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-sm-2 col-form-label" for="transection-cat">Category</label>
                                <div class="col-sm-10">
                                    <select id="transection-cat" class="form-select">
                                        <option>Select</option>
                                        <option value="1">One</option>
                                        <option value="2">Two</option>
                                        <option value="3">Three</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-sm-2 col-form-label" for="transection-desc">Description</label>
                                <div class="col-sm-10">
                                    <textarea id="transection-desc" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="row justify-content-end">
                                <div class="col-sm-10">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Layout Demo -->
</div>
<!-- / Content -->
<?php include 'includes/footer.php'; ?>