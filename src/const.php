<?php

define("DEFAULT_CATEGORY", serialize([
    [
        "name" => "Food",
        "type" => "Expense",
        "monthly_limit" => 1500.00
    ],
    [
        "name" => "Transport",
        "type" => "Expense",
        "monthly_limit" => 1500.00
    ],
    [
        "name" => "Rent",
        "type" => "Expense",
        "monthly_limit" => 5000.00
    ],
    [
        "name" => "Bill",
        "type" => "Expense",
        "monthly_limit" => 1000.00
    ],
    [
        "name" => "Other",
        "type" => "Expense",
        "monthly_limit" => 500.00
    ],
    [
        "name" => "Salary",
        "type" => "Income",
        "monthly_limit" => null
    ]
]));
