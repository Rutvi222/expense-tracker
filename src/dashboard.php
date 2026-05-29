<?php
include 'includes/header.php'; 
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$curr_month = date('m');
$curr_year = date('Y');

// 1. Today's Spent
$sql_today = "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'Expense' AND is_deleted = '0' AND transaction_date = CURRENT_DATE";
$stmt = $pdo->prepare($sql_today); $stmt->execute([$user_id]);
$today_spent = $stmt->fetch()['total'] ?? 0;

// 2. Current Month Stats
$sql_stats = "SELECT 
    COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END), 0) AS total_income,
    COALESCE(SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END), 0) AS total_expense
    FROM transactions
    WHERE user_id = ? AND is_deleted = '0'
    AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    AND transaction_date < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')";

$stmt = $pdo->prepare($sql_stats); 
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$total_income = $stats['total_income'] ?? 0;
$total_expense = $stats['total_expense'] ?? 0;
$balance = $total_income - $total_expense;

// 3. Daily Average 
$day_of_month = date('d'); 
$daily_avg = $total_expense / $day_of_month;

// --- Bar Chart (Current Month Expense) ---
$sql_bar = "SELECT 
        DATE(transaction_date) AS day,
        DATE_FORMAT(DATE(transaction_date), '%d %b') AS day_label,
        SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
        AND type = 'Expense'
        AND is_deleted = '0'
        AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        AND transaction_date < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
    GROUP BY DATE(transaction_date), DATE_FORMAT(DATE(transaction_date), '%d %b')
    ORDER BY DATE(transaction_date) ASC";

$stmt = $pdo->prepare($sql_bar);
$stmt->execute([$user_id]);
$bar_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$bar_labels = array_column($bar_results, 'day_label');
$bar_data = array_map('floatval', array_column($bar_results, 'total'));

// --- Pie Chart (Category wise Expense for Current Month) ---
$sql_pie = "SELECT c.name, SUM(t.amount) as total 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? 
            AND t.type = 'Expense' 
            AND t.is_deleted = '0' 
            AND t.transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') 
            AND t.transaction_date < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01') 
            GROUP BY c.name";

$stmt_pie = $pdo->prepare($sql_pie);
$stmt_pie->execute([$user_id]);
$pie_results = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);
$pie_labels = array_column($pie_results, 'name');
$pie_data = array_map('floatval', array_column($pie_results, 'total'));

// ---  RECENT 5 TRANSACTIONS ---
$sql_recent = "SELECT `trans`.`note` AS `title`, `cate`.`name`  AS `category`, `trans`.`type`  AS `type`, `trans`.`amount`  AS `amount`, `trans`.`transaction_date` AS `date`
                FROM transactions trans
                JOIN categories cate ON trans.category_id = cate.id 
                WHERE `trans`.`user_id` = ? AND `trans`.`is_deleted` = '0'
                ORDER BY `trans`.`transaction_date` DESC LIMIT 5";

$stmt = $pdo->prepare($sql_recent);
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Layout page -->
    <div class="">
        <!-- Greeting Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h4 class="fw-bold py-1 mb-0">Hello <?php echo $_SESSION['username']; ?>! 👋</h4>
                <p class="text-muted">You have spent <span class="text-danger fw-bold">₹<?= number_format($today_spent, 2) ?></span> today.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="add_expense.php" class="btn btn-primary shadow-sm"><i class="bx bx-plus me-1"></i> Add New</a>
                <a href="export.php" class="btn btn-outline-secondary shadow-sm"><i class="bx bx-export me-1"></i> Export</a>
            </div>
        </div>

        <!-- 1. Top Stat Cards -->
        <div class="row mb-4">
            <!-- Total Income Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body pb-2">
                        <span class="d-block fw-semibold mb-1">Total Income</span>
                        <h3 class="card-title mb-1">₹<?= number_format($total_income, 0) ?></h3>
                        <small class="text-success fw-semibold"><i class='bx bx-chevron-up'></i> This Month</small>
                        <!-- Mini Chart Container -->
                        <div id="incomeChart"></div>
                    </div>
                </div>
            </div>

            <!-- Total Expense Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body pb-2">
                        <span class="d-block fw-semibold mb-1">Total Expense</span>
                        <!-- Dynamic Amount -->
                        <h3 class="card-title mb-1">₹<?= number_format($total_expense, 0) ?></h3>
                        <small class="text-danger fw-semibold"><i class='bx bx-chevron-down'></i> This Month</small>
                        <div id="expenseChart"></div>
                    </div>
                </div>
            </div>

            <!-- Balance Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body pb-2">
                        <span class="d-block fw-semibold mb-1">Balance</span>
                        <!-- Dynamic Amount -->
                        <h3 class="card-title mb-1 <?= ($balance < 0) ? 'text-danger' : '' ?>">₹<?= number_format($balance, 0) ?></h3>
                        <small class="text-muted">Current Month</small>
                        <div id="balanceChart"></div>
                    </div>
                </div>
            </div>

            <!-- Daily Average Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body pb-2">
                        <span class="d-block fw-semibold mb-1">Daily Average</span>
                        <!-- Dynamic Amount -->
                        <h3 class="card-title mb-1">₹<?= number_format($daily_avg, 0) ?></h3>
                        <small class="text-info fw-semibold">Stable</small>
                        <div id="avgChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Charts Row -->
        <div class="row">
            <!-- Date wise Bar Graph -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2">Expense Trends</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">This Month</button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="javascript:void(0);">Last Month</a>
                                <a class="dropdown-item" href="javascript:void(0);">Last 3 Months</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="dateWiseBarChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
            <!-- Category Pie Chart -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2">By Category</h5>
                    </div>
                    <div class="card-body">
                        <div id="categoryPieChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Row -->
        <div class="row align-items-start">
            <!-- Recent Transactions Table -->
            <div class="col-md-8 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2">Recent Expenses</h5>
                        <a href="history.php" class="btn btn-xs btn-outline-primary">View All</a>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <?php if (!empty($recent_transactions)): ?>
                                    <?php foreach ($recent_transactions as $row): ?>
                                        <?php $cat_badge = ($row['type'] == 'Income') ? 'bg-label-primary' : 'bg-label-warning' ; ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                            <td><span class="badge me-1 <?= $cat_badge; ?>"><?= htmlspecialchars($row['category']) ?></span></td>
                                            <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                                            <td class="<?= ($row['type'] == 'Expense') ? 'text-danger' : 'text-success' ?> fw-bold">
                                                <?= ($row['type'] == 'Expense') ? '- ' : '+ ' ?>
                                                ₹<?= number_format($row['amount'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No transactions</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Comparison Card -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 bg-primary text-white">
                    <div class="card-body">
                        <h5 class="text-white mb-4">Monthly Comparison</h5>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar flex-shrink-0 me-3"><span class="badge bg-white p-2"><i class="bx bx-trending-down text-primary"></i></span></div>
                            <div>
                                <h4 class="mb-0 text-white">12% Less</h4>
                                <small>than last month</small>
                            </div>
                        </div>
                        <p class="small opacity-75">Great job! You saved ₹2,500 more this month compared to September.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/vendor/libs/apex-charts/apexcharts.js"></script>

<script>
$(document).ready(function() {
    // Common configuration for sparklines
    const sparklineOptions = {
        chart: { type: 'area', height: 50, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { opacity: 0.15 },
        tooltip: { enabled: false }
    };

    // 1. Income Chart (Green)
    new ApexCharts(document.querySelector("#incomeChart"), {
        ...sparklineOptions,
        series: [{ data: [30, 45, 35, 50, 40, 60, 55] }],
        colors: ['#71dd37']
    }).render();

    // 2. Expense Chart (Red)
    new ApexCharts(document.querySelector("#expenseChart"), {
        ...sparklineOptions,
        series: [{ data: [50, 40, 60, 45, 55, 30, 40] }],
        colors: ['#ff3e1d']
    }).render();

    // 3. Balance Chart (Blue)
    new ApexCharts(document.querySelector("#balanceChart"), {
        ...sparklineOptions,
        series: [{ data: [20, 40, 30, 50, 40, 60, 70] }],
        colors: ['#696cff']
    }).render();

    // 4. Avg Chart (Info/Cyan)
    new ApexCharts(document.querySelector("#avgChart"), {
        ...sparklineOptions,
        chart: { ...sparklineOptions.chart, type: 'line' }, // Only line, no area
        series: [{ data: [40, 40, 45, 40, 42, 40, 40] }],
        colors: ['#03c3ec']
    }).render();

    // 1. Date Wise Bar Chart (Mock Data)
    var barOptions = {
        series: [{ 
            name: 'Daily Expense', 
            data: <?php echo json_encode($bar_data); ?> 
        }],
        chart: { 
            type: 'bar', 
            height: 300, 
            toolbar: { show: false } 
        },
        plotOptions: { 
            bar: { 
                borderRadius: 4, 
                columnWidth: '45%', // બારની પહોળાઈ
                dataLabels: { position: 'top' } 
            } 
        },
        dataLabels: { 
            enabled: false // બારની ઉપરના આંકડા હટાવ્યા
        },
        xaxis: { 
            categories: <?php echo json_encode($bar_labels); ?>,
            labels: { rotate: -45 } // તારીખ ત્રાંસી દેખાશે જેથી બધું વંચાય
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return "₹" + Math.round(val); // આંકડા રાઉન્ડ ઓફ થશે
                }
            }
        },
        colors: ['#696cff'], // Sneat નું સિગ્નેચર પર્પલ કલર
        tooltip: {
            y: { formatter: (val) => "₹ " + val.toLocaleString() }
        }
    };

    var barChart = new ApexCharts(document.querySelector("#dateWiseBarChart"), barOptions);
    barChart.render();

    // 2. Category Pie Chart (Mock Data)
    var pieOptions = {
        series: <?php echo json_encode($pie_data); ?>,
        chart: { 
            type: 'donut', 
            height: 350 
        },
        labels: <?php echo json_encode($pie_labels); ?>,
        colors: ['#696cff', '#8592a3', '#71dd37', '#ff3e1d', '#03c3ec'], // Sneat Colors
        legend: { 
            position: 'bottom',
            fontSize: '14px'
        },
        dataLabels: { 
            enabled: false 
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                // બધા ખર્ચનો સરવાળો બતાવશે
                                return '₹' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                            }
                        }
                    }
                }
            }
        }
    };

    var pieChart = new ApexCharts(document.querySelector("#categoryPieChart"), pieOptions);
    pieChart.render();
});
</script>
