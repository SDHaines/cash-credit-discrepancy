#!/usr/local/bin/php
<?php

/* 
 * This script calculates and outputs the discrepancy
 * between cash and credit card payments when 
 * credit card payments exceed 30% of the income for
 * a given date or date range. 
 * 
 * Called from the command line it requires at least
 * 1 argument a date in the form of yyyy-mm-dd
 * ie. ./ccd.php 2021-05-02 will calculate the totals 
 * for the logical business day. 
 * 
 * If a 2nd argument is given then the calculation is
 * performed over the range of dates. 
 * ie. ./ccd.php 2021-04-25 2021-05-01 for a complete 
 * week total. Dates are transformed from the natural 
 * date to the logical business dates and times.
 */

require_once '/usr/local/valCommon/Counterpoint.php';

$startDT = counterpointBusinessDayStart ( $argv[1] );
$endDT = isset( $argv[2] ) ? counterpointBusinessDayEnd( $argv[2] ) : counterpointBusinessDayEnd( $argv[1] );
$cashAmt = 0;
$creditAmt = 0;
$totalAmt = 0;

print "Query Dates START: $startDT END: $endDT\n";

$cbArg = (object) ['cashAmt' => 0,'creditAmt' => 0,'totalAmt' => 0];

$tsql = "SELECT
P.DESCR,
SUM(P.AMT) AS AMT
FROM dbo.VI_PS_TKT_HIST_PMT P WITH (NOLOCK) 
LEFT JOIN dbo.VI_PS_TKT_HIST H WITH(NOLOCK) ON H.TKT_NO = P.TKT_NO
WHERE H.TKT_DT >= '$startDT' and H.TKT_DT <= '$endDT'
GROUP BY P.DESCR";

$result = counterpointQuickQuery( $tsql, "tallyFunction", $cbArg ) ;
if ( $result === false ){
    die( "hmmm somethings wrong, ^^^^^ above error messages should point you in the right direction\n" );
}

$normalCreditAmt = sprintf( "%.02f", .3 * $cbArg->totalAmt ) ;
$discrapancy = $cbArg->creditAmt > $normalCreditAmt ? $cbArg->creditAmt - $normalCreditAmt : 0;

print "Cash: $cbArg->cashAmt\n";
print "Credit: $cbArg->creditAmt\n";
print "Normal Credit: $normalCreditAmt\n";
print "Credit discrapancy: $discrapancy\n";

function tallyFunction( $row, &$cbArg ) { 

    if ( $row['DESCR'] == "Cash" ) {
        $cbArg->cashAmt += sprintf ("%.02f", $row['AMT']);
    }else {
        $cbArg->creditAmt += sprintf( "%.02f", $row['AMT'] );
    }
    $cbArg->totalAmt += sprintf( "%.02f", $row['AMT'] );
}
?>