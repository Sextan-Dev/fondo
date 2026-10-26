// Figures of the donors balance report (donorsBalanceReport.vue), from the sums that
// ReportController@donorsBalance returns for one donor and source.
//
// Every formula keeps the order of operations the report has always used: floating
// point results depend on it, and the balances must match the account statements to
// the cent.

// Earlier months show no previous balance, as they always have (their closing balance still counts it)
export const FIRST_OPENING_MONTH = '2021-04';

// Balance at the end of the previous month
export function previousBalance(row) {
    return Number(row.incomesPrev) +
        Number(row.sumTransfersProjectsReceiptPrev) +
        Number(row.sumTransfersReceiptPrev) -
        (Number(row.sumTransfersSendingsPrev) - Number(row.sumRemnantsPrev) - Number(row.sumReleasesPrev) - Number(row.sumMonitoringPrev)) -
        Number(row.sumReleasesPrev) -
        Number(row.sumRemnantsPrev) -
        Number(row.sumMonitoringPrev) -
        Number(row.sumOutcomesPrev);
}

// Transfers to other funds, without the remnants, releases and operating cost that have their own columns
export function transfersGiven(row) {
    return Number(row.sumTransfersSendings) - Number(row.sumRemnants) - Number(row.sumReleases) - Number(row.sumMonitoring);
}

// Balance at the end of the month
export function closingBalance(row) {
    return previousBalance(row) + Number(row.sumIncomesFee) +
        Number(row.sumTransfersProjectsReceipt) +
        Number(row.sumTransfersReceipt) -
        transfersGiven(row) -
        Number(row.sumReleases) -
        Number(row.sumRemnants) -
        Number(row.sumOutcomes) -
        Number(row.sumMonitoring);
}

// Every amount the report shows for a source, by column key
export function figures(row, month) {
    return {
        previous: month >= FIRST_OPENING_MONTH ? previousBalance(row) : 0,
        incomes: Number(row.sumIncomes),
        fee: Number(row.sumFee),
        incomesNet: Number(row.sumIncomesFee),
        withheld: Number(row.val_operating_cost) || 0,
        received: Number(row.sumTransfersReceipt),
        projects: Number(row.sumTransfersProjectsReceipt),
        given: transfersGiven(row),
        outcomes: Number(row.sumOutcomes),
        operatingCost: Number(row.sumMonitoring),
        releases: Number(row.sumReleases),
        remnants: Number(row.sumRemnants),
        balance: closingBalance(row)
    };
}

// Amount in whole cents, rounded the way the report prints it (toFixed), so totals add up to the rows shown
export function cents(value) {
    return Math.round(Number((Number(value) || 0).toFixed(2)) * 100) || 0;
}
