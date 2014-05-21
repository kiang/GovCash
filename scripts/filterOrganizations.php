<?php

$path = dirname(__DIR__);
$accounts = array();
foreach (glob($path . '/accounts/*.csv') AS $csvFile) {
    $key = pathinfo($csvFile)['filename'];
    $accounts[$key] = array(
        'out' => array(),
        'in' => array(),
    );
    $fh = fopen($csvFile, 'r');
    fgetcsv($fh, 128);
    /*
     * Array
      (
      [0] => 交易日期
      [1] => 收支科目
      [2] => 捐贈者/支出對象
      [3] => 身份證/統一編
      [4] => 收入金額
      [5] => 支出金額
      [6] => 金錢類
      [7] => 地址
      )
     */
    while ($line = fgetcsv($fh, 2048)) {
        $line[3] = trim($line[3]);
        if (preg_match('/[0-9]{8}/', $line[3])) {
            /*
             * find records belong to companies
             */
            if (!empty($line[4])) {
                //income
                if (!isset($accounts[$key]['in'][$line[3]])) {
                    $accounts[$key]['in'][$line[3]] = array(
                        'title' => $line[2],
                        'location' => $line[7],
                        'lAccount' => array(),
                        'total' => 0,
                    );
                }

                $accounts[$key]['in'][$line[3]]['lAccount'][$line[1]] = 1;
                $accounts[$key]['in'][$line[3]]['total'] += intval($line[4]);
            } elseif (!empty($line[5])) {
                //expense
                if (!isset($accounts[$key]['out'][$line[3]])) {
                    $accounts[$key]['out'][$line[3]] = array(
                        'title' => $line[2],
                        'location' => $line[7],
                        'lAccount' => array(),
                        'total' => 0,
                    );
                }
                $accounts[$key]['out'][$line[3]]['lAccount'][$line[1]] = 1;
                $accounts[$key]['out'][$line[3]]['total'] += intval($line[5]);
            }
        }
    }
    fclose($fh);
    uasort($accounts[$key]['in'], 'cmp');
    uasort($accounts[$key]['out'], 'cmp');
    
    $fh = fopen("{$key}_in.csv", 'w');
    foreach($accounts[$key]['in'] AS $id => $data) {
        fputcsv($fh, array(
            $id,
            $data['title'],
            $data['location'],
            implode('.', array_keys($data['lAccount'])),
            $data['total'],
        ));
    }
    fclose($fh);
    
    $fh = fopen("{$key}_out.csv", 'w');
    foreach($accounts[$key]['out'] AS $id => $data) {
        fputcsv($fh, array(
            $id,
            $data['title'],
            $data['location'],
            implode('.', array_keys($data['lAccount'])),
            $data['total'],
        ));
    }
    fclose($fh);
}

//file_put_contents('result.json', json_encode($accounts));
//print_r(json_decode(file_get_contents('result.json'), true));

function cmp($a, $b) {
    if ($a['total'] == $b['total']) {
        return 0;
    }
    return ($a['total'] < $b['total']) ? 1 : -1;
}