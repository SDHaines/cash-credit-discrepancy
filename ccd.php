#!/usr/local/bin/php
<?php

#require_once '/usr/local/valCommon/SqlServer.php';
require_once '/usr/local/valCommon/Counterpoint.php';

$startDT = counterpointBusinessDayStart ( $argv[1] );
$endDT = isset( $argv[2] ) ? counterpointBusinessDayEnd( $argv[2] ) : counterpointBusinessDayEnd( $argv[1] );
#$startDT = $argv[1] ?? date( 'Y-m-d' );
#$endDT = $argv[2] ?? vcSqlServerBusinessDayEnd( $startDT );
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

$result = counterpointQuickQuery( $tsql, "tallyFunc", $cbArg ) ;


$normalCreditAmt = sprintf( "%.02f", .3 * $cbArg->totalAmt ) ; #cc sales should be 30% of total
$discrapancy = $cbArg->creditAmt > $normalCreditAmt ? $cbArg->creditAmt - $normalCreditAmt : 0;
print "Cash: $cbArg->cashAmt\n";
print "Credit: $cbArg->creditAmt\n";
print "Normal Credit: $normalCreditAmt\n";
print "Credit discrapancy: $discrapancy\n";

function tallyFunc( $row, &$cbArg ) { 
    #global $cashAmt; 
    #global $creditAmt;
    #global $totalAmt;
    if ( $row['DESCR'] == "Cash" ) {
        $cbArg->cashAmt += sprintf ("%.02f", $row['AMT']);
    }else {
        $cbArg->creditAmt += sprintf( "%.02f", $row['AMT'] );
    }
    $cbArg->totalAmt += sprintf( "%.02f", $row['AMT'] );
}
?>