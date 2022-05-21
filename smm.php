<?php
// QUICK CONFIG //
$address = 'https://10.0.0.2'; // Address web server is accessible from without trailing /. https://example.com

/* 
    SIMPLE MONEY MANAGER PHP SCRIPT. Tested and developed in PHP 7.4.3
    This PHP script in it's current form is not intended for use on public servers.
    This script includes no input sanitation and no security checks.
    Use the unmodified script on a public server at your own risk.

    CREDIT: This file includes modified and unmodified HTML and CSS code for a tabbed user interface written by mikestreety: https://codepen.io/mikestreety | https://codepen.io/mikestreety/pen/yVNNNm


 ******************************************************************************   
    
    Copyright 2022 "Diablo7806" https://github.com/diablo7806
    
    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
    
    http://www.apache.org/licenses/LICENSE-2.0
    
    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
    
 ******************************************************************************


*/
// CONFIG //
$database = './smm.json'; // JSON Database location.
error_reporting(-1); // Error reporting level. -1 for debug.
// Thats it. The rest is done on site.


// FUNCTIONS //
function save($json){
    $f = fopen($GLOBALS['database'], 'w'); // Open database.
    $data = json_encode($GLOBALS['json']); // Encode SMM array.
    if(isset($json)){
        $data = json_encode($json); // Encode passed JSON.
    }
    if($GLOBALS['compress'] == 1){
        $data = bzcompress($data, 9); // Compress JSON if enabled.
    }
    fwrite($f, $data); // Write database.
    fclose($f); // Close database.
    return;
}
function update($loc, $amt, $type){
    $entry = ['time' => time(),                 // Timestamp
              'loc' => $loc,                    // Location.
              'amt' => $amt,                    // Amount.
              'type' => $type,                  // Type, Income or Purchase.
              'uid' => $GLOBALS['json']['nextid']]; // Unique Identifier.
    array_push($GLOBALS['json']['log'], $entry); // Update log entry.
    if($type == 'p'){
        $GLOBALS['json']['funds'] = number_format($GLOBALS['json']['funds'] - number_format($amt, 2, '.', ''), 2, '.', ''); // Subtract purchase amount from funds.
    }
    if($type == 'i'){
        $GLOBALS['json']['funds'] = number_format($GLOBALS['json']['funds'] + number_format($amt, 2, '.', ''), 2, '.', ''); // Add income amount to funds.
    }
    $GLOBALS['json']['nextid'] = $GLOBALS['json']['nextid'] + 1; // Increment unique id.
}
function isjson($string) {
    return $string === null || json_decode($string) !== null;
}
function byte($bytes){
    $si_prefix = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $base = 1024;
    $class = min((int)log($bytes, $base), count($si_prefix) - 1);
    return number_format($bytes / pow($base,$class), 2).' '.$si_prefix[$class];
}


// READ DATABASE
$default = ['funds' => 0.00, // Define default configuration.
            'show' => 10,
            'nextid' => 1,
            'notes' => 1,
            'pages' => 1,
            'currency' => '$',
            'date' => 'Y-m-d',
            'time' => 'h:i A',
            'compress' => 1,
            'debug' => 0,
            'log' => []];
if(!file_exists($database)){
    $json = $default; // Set default configuration.
    $compress = $json['compress']; // Set compression according to config.
    save($json); // If no database exists, save default template.
}
$f = fopen($database, 'r'); // Open database.
$data = fread($f, filesize($database)); // Read database.
$flag = false; // Set flag false.
if(isjson($data) == null){
    $flag = true; // Set flag true.
    $data = bzdecompress($data); // Decompress database
    $chk = isjson($data); // Check if valid JSON.
    if($chk == false){
        $json = $default; // Failsafe to defaults.
        echo '<center>[ERROR]: DATABASE ERROR</center><br>'."\n"; // Report Error
        $compress = 0; // Set compression off.
    }
    if($chk == true){
        $json = json_decode($data, true); // Decode JSON.
        $compress = $json['compress']; // Set compression per config.
    }
}
if($flag != true){
    $json = json_decode($data, true); // Decode JSON.
    $compress = $json['compress']; // Set compression per config.
}

// GET HANDLERS
$full = false; // Full history default off.
if(isset($_GET['full']) or $json['show'] == 0){
    $full = true; // Full history if enabled in JSON or address bar.
}
if(isset($_GET['s'])){
    $json['show'] = $_POST['show'];         // Update notification limit.
    $json['notes'] = $_POST['notes'];       // Update notifications status.
    $json['date'] = $_POST['date'];         // Update date display setting.
    $json['pages'] = $_POST['pages'];       // Update page support status.
    $json['currency'] = $_POST['currency']; // Update currency type.
    $json['compress'] = $_POST['compress']; // Update compression status.
    $json['time'] = $_POST['time'];         // Update time display setting.
    $json['debug'] = $_POST['debugmode'];   // Update debug mode setting.
    $compress = $json['compress'];          // Set compression per config.
    save(NULL); // Save
    if($json['notes'] == 1){
        setcookie('note', base64_encode('Settings Saved'), time()+3600); // Set notification cookie if enabled.
    }
    header('Location: '.$address.$_SERVER['PHP_SELF'].'?tab=4'); // Reload to Settings tab.
}
if(isset($_GET['t'])){
    if($_GET['t'] == 'p'){ // Check for 't' transaction variable.
        $amt = 0.00; // Default to 0.
        $loc = 'Undefined'; // Default to Undefined.
        $old = $json['funds']; // Record previous funds amount.
        if(isset($_POST['amt'])){
            $amt = $_POST['amt']; // Read 'amt' address variable.
        }
        if(isset($_POST['loc'])){
            $loc = $_POST['loc']; // Read 'loc' address variable.
        }
        update($loc, $amt, 'p'); // Update array.
        save(NULL); // Save database.
        if($json['notes'] == 1){
            setcookie('note', base64_encode('&nbsp;Purchase made for '.$json['currency'].number_format($amt, 2).' from '.$loc.'.<br>&nbsp;'.$json['currency'].number_format($old, 2).' --> '.$json['currency'].number_format($json['funds'], 2)), time()+3600); // Set cookie for notifications.
        }
        header('Location: '.$address.$_SERVER['PHP_SELF'].'?tab=1'); // Reload to Purchase tab.
    }
    if($_GET['t'] == 'i'){
        $amt = 0.00; // Default to 0.
        $loc = 'Undefined'; // Default to Undefined.
        $old = $json['funds']; // Record previous funds amount.
        if(isset($_POST['amt'])){
            $amt = $_POST['amt']; // Read 'amt' address variable.
        }
        if(isset($_POST['loc'])){
            $loc = $_POST['loc']; // Read 'loc' address variable.
        }
        update($loc, $amt, 'i'); // Update array.
        save(NULL); // Save database.
        if($json['notes'] == 1){
            setcookie('note', base64_encode('&nbsp;Income made for '.$json['currency'].number_format($amt, 2).' from '.$loc.'.<br>&nbsp;'.$json['currency'].number_format($old, 2).' --> '.$json['currency'].number_format($json['funds'], 2)), time()+3600); // Set cookie for notifications.
        }
        header('Location: '.$address.$_SERVER['PHP_SELF'].'?tab=2'); // Reload to Income tab.
    }
    if($_GET['t'] == 'd'){
        if(isset($_GET['uid'])){
            $i = 0; // Counter.
            $key = false; // Default to false.
            foreach($json['log'] as $entry){ // Iterate through log entries.
                if($entry['uid'] == $_GET['uid']){
                    $find = true; // If uid matches, set $find to true.
                }
                if($find == true){
                    $key = $i; // If uid found, set $key to iteration count. and break loop.
                    break;
                }
                $i++; // Iterate.
            }
            if($key != false){ // If key found.
                $old = $json['funds']; // Record previous funds.
                $uid = $json['log'][$key]['uid']; // Record uid.
                $loc = $json['log'][$key]['loc']; // Record loc.
                $amt = $json['log'][$key]['amt']; // Record amt.
                if($json['log'][$key]['type'] == 'p'){
                    $json['funds'] = number_format($json['funds'] + number_format($json['log'][$key]['amt'], 2, '.', ''), 2, '.', ''); // Add deleted purchase transaction amount back to funds.
                }
                if($json['log'][$key]['type'] == 'i'){
                    $json['funds'] = number_format($json['funds'] - number_format($json['log'][$key]['amt'], 2, '.', ''), 2, '.', ''); // Subtract deleted income transaction amount from funds.
                }
                unset($json['log'][$key]); // Remove log entry.
                $json['log'] = array_values($json['log']); // Make sure log is still an array and not an object.
                save(NULL); // Save database.
                if($json['notes'] == 1){
                    setcookie('note', base64_encode('&nbsp;Deleted transaction (#'.$uid.') from '.$loc.' for '.$json['currency'].number_format($amt, 2).'<br>&nbsp;'.$json['currency'].number_format($old, 2).' --> '.$json['currency'].number_format($json['funds'], 2)), time()+3600); // Set cookie for notifications.
                }
            }
            header('Location: '.$address.$_SERVER['PHP_SELF'].'?tab=3'); // Reload to Settings tab.
        }
    }
}

// RESET DEFAULTS
if(isset($_GET['rst'])){
    $json = $default; // Set database to defaults.
    $compress = $json['compress']; // Enable compression per config.
    save(NULL); // Save
    if($notes == 1){
        setcookie('note', base64_encode('Database erased and defaults set.'), time()+3600); // Set notification cookie if enabled.
    }
    header('Location: '.$address.$_SERVER['PHP_SELF']); // Reload to main.
}

// JSON EDIT - DEBUG
if(isset($_GET['e'])){
    $json = json_decode($_POST['textjson'], true); // Copy database from debug.
    if(isset($_POST['jsoncompress'])){
        if($_POST['jsoncompress'] == 1){ // Set compression per settings value.
            $compress = 1; 
        }
        else{
            $compress = 0;
        }
    }
    save($json); // Save
    if($json['notes'] == 1){
        setcookie('note', base64_encode('JSON Database debug edited.'), time()+3600); // Set notification cookie if enabled.
    }
    header('Location: '.$address.$_SERVER['PHP_SELF'].'?tab=5'); // Redirect to main.
}

// ACTIVE TAB
$active = [0 => 0, 1 => '', 2 => '', 3 => '', 4 => '', 5 => '']; // Active tab array.
if(isset($_GET['tab'])){
    $active[$_GET['tab']] = ' checked="checked"';
    $active[0] = 1;
}
if($active[0] == 0){
    $active[1] = ' checked="checked"';
}
?>

<html>
    <head>
        <!-- To ensure browser doesn't cache the page and give outdated information -->
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <title>Simple Money Manager</title>
        <style>
            /* Tabs mikestreety */
            .tabs {
                display: flex;
                flex-wrap: wrap; /* make sure it wraps */
            }
            .tabs label {
                order: 1; /* Put the labels first */
                display: block;
                padding: 1rem 2rem;
                margin-right: 0.2rem;
                cursor: pointer;
                background: #88a;
                font-weight: bold;
                transition: background ease 0.2s;
            }
            .tabs .tab {
                order: 99; /* Put the tabs last */
                flex-grow: 1;
                width: 100%;
                display: none;
                padding: 1rem;
                background: #ddd;
            }
            .tabs input[type="radio"] {
                display: none;
            }
            .tabs input[type="radio"]:checked + label {
                background: #88f;
            }
            .tabs input[type="radio"]:checked + label + .tab {
                display: block;
            }
            
            @media (max-width: 45em) {
                .tabs .tab,
                .tabs label {
                    /*order: initial;*/
                }
                .tabs label {
                    width: 100%;
                    margin-right: 0;
                    margin-top: 0.2rem;
                }
            }
            
            /* Tabs Generic Styling mikestreety */
            body {
                background: #777;
                min-height: 100vh;
                box-sizing: border-box;
                padding-top: 1vh;
                font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; 
                font-weight: 300;
                line-height: 1.5;
                max-width: 60rem;
                margin: 0 auto;
                font-size: 112%;
            }
            
            /* DIV Table Styling */
            .table{
                margin: 0 0 40px 0;
                width: 100%;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                display: table;
                font-weight:bold;
            }
            .row{
                display: table-row;
                background: #eee;
                font-weight:bold;
            }
            .head{
                display: table-row;
                background: #26b;
                font-weight:bold;
            }
            .cell{
                padding: 3px 3px;
                display: table-cell;
                border:1px dotted;
                font-weight:bold;
                font-size:16px;
            }
            
            /* Delete link styled like a button */
            .delete {
              font: bold 14px Arial;
              text-decoration: none;
              background-color: #e44;
              color: #000;
              padding: 2px 6px 2px 6px;
              border-top: 1px solid #ccc;
              border-right: 1px solid #333;
              border-bottom: 1px solid #333;
              border-left: 1px solid #ccc;
            }
            
            /* Styling to remove arrow buttons on number inputs */
            /* Chrome, Safari, Edge, Opera */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            /* Firefox */
            input[type=number] {
                -moz-appearance: textfield;
            }
            
            /* Submit Button */
            .submit {
                background: #06a;
                color: white;
                border-style: outset;
                border-color: #06a;
                height: 30px;
                width: 100px;
                text-shadow: none;
            }
            
            /* Notifications */
            .note_red {
                overflow: hidden;
                background-color: #d33;
                color: #fff;
                margin-bottom:10px;
            }
            
            #nextback{
                text-decoration:none;
                font-weight:bold;
                font-size:16px;
            }
        </style>
    </head>
    <body>
        <datalist id="loc">
            <?php
            $names = []; // Setup array for known locations.
            foreach($json['log'] as $entry){ // Iterate logs.
                if(in_array($entry['loc'], $names) == false){
                    array_push($names, $entry['loc']); // If location is not in the array, add it to the array.
                } 
            }
            sort($names); // Sort list alphabetically.
            $ph1 = rand(0, count($names));
            $ph2 = rand(0, count($names));
            foreach($names as $name){
                echo '<option value="'.$name.'">'; // Add to datalist for predefined locations.
            }
            echo "\n";
            if(!isset($names[$ph1])){
                $names[$ph1] = 'Location';
            }
            if(!isset($names[$ph2])){
                $names[$ph2] = 'Location';
            }
            ?>
        </datalist>
        <!-- Available balance notification -->
        <div class="note_red">
            <span style="font-weight:bold;">&nbsp;Available Balance: <?php echo $json['currency'].number_format($json['funds'], 2); ?></span><br>
        </div>
        <?php
        if($json['notes'] == 1 && isset($_COOKIE['note'])){ // Check if notifications are enabled.
            if(isset($_COOKIE['note'])){ // Check if notification cookie exists,
                echo '<a href="#" onClick="document.getElementById(\'note\').style.display = \'none\';" style="text-decoration:none;color:#fff;">
            <div class="note_red" id="note">
                <span style="font-weight:bold;">'.base64_decode($_COOKIE['note']).'</span><br>
            </div>
        </a>'; // Print notification, click to dismiss.
                setcookie('note', NULL, 1); // Set cookie to expire immediately.
            }
        }
        ?>
        <div class="tabs">
            <input type="radio" name="tabs" id="tabone"<?php echo $active[1]; ?>>
            <label for="tabone">Purchase</label>
            <div class="tab">
                <form method="post" action="<?php echo $address.$_SERVER['PHP_SELF']; ?>?t=p">
                    <table>
                        <colgroup>
                            <col span="1" style="width:35%;">
                            <col span="2" style="width:65%;">
                        </colgroup>
                        <thead>
                            <th style="text-align:left;">Purchase:</th>
                            <th></th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Amount:</td>
                                <td><input name="amt" type="number" placeholder="<?php echo $json['currency']; ?>0.00" cols="20" step="0.01"></input></td>
                            </tr>
                            <tr>
                                <td>Location:</td>
                                <td><input name="loc" type="text" placeholder="<?php echo $names[$ph1]; ?>" cols="20" list="loc"></input></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><input type="submit" value="&nbsp;Purchase&nbsp;" class="submit"></input></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <input type="radio" name="tabs" id="tabtwo"<?php echo $active[2]; ?>>
            <label for="tabtwo">Income</label>
            <div class="tab">
                <form method="post" action="<?php echo $address.$_SERVER['PHP_SELF']; ?>?t=i">
                    <table>
                        <colgroup>
                            <col span="1" style="width:35%;">
                            <col span="2" style="width:65%;">
                        </colgroup>
                        <thead>
                            <th style="text-align:left;">Income:</th>
                            <th></th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Amount:</td>
                                <td><input name="amt" type="number" placeholder="<?php echo $json['currency']; ?>0.00" cols="20" step="0.01"></input></td>
                            </tr>
                            <tr>
                                <td>Location:</td>
                                <td><input name="loc" type="text" placeholder="<?php echo $names[$ph2]; ?>" cols="20" list="loc"></input></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><input type="submit" value="&nbsp;Income&nbsp;" class="submit"></input></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <input type="radio" name="tabs" id="tabthree"<?php echo $active[3]; ?>>
            <label for="tabthree">History</label>
            <div class="tab">
                <?php
                if($full == false){ // Check if full history is false.
                    echo '<table style="width:100%;bottom-margin:0px;"><tr><td style="text-align:left;font-weight:bold;">Most recent '.$json['show'].' transactions: </td><td style="text-align:right;">(<a href="'.$address.$_SERVER['PHP_SELF'].'?full&tab=3">Show Full</a>)</td></tr></table>'; // Print beginning of heading with link to show all.
                }
                if($full == true){ // Check if full history is true.
                    echo '<table style="width:100%;bottom-margin:0px;"><tr><td style="text-align:left;font-weight:bold;">All transactions: </td>'; // Print heading.
                    if($json['show'] != 0){ // If configuration is not set to 0.
                        echo '<td style="text-align:right;">(<a href="'.$address.$_SERVER['PHP_SELF'].'?tab=3">Show Fewer</a>)</td></tr></table>'; // Print link to not show full history.
                    }
                    else {
                        echo '<td></td></tr></table>'; // Finish heading with no link
                    }
                }
                echo '<span style="font-size:14px;font-weight:bold;">Total number of transactions: '.count($json['log']).'</span><br>'; // Subheading with total number of transactions logged.
                ?>
                <div class="table">
                    <div class="head">
                        <div class="cell">Date</div>
                        <!--<div class="cell">Time</div>-->
                        <div class="cell">Location</div>
                        <div class="cell">Amount</div>
                        <!--<div class="cell">Type</div>-->
                        <div class="cell">Delete</div>
                    </div>
                    <?php
                    $i = 0; // Counter
                    if($json['pages'] == 1){ // If page navigation is enabled in configuration.
                        if($full == false){ // If full history is not enabled.
                            if(isset($_GET['page'])){ // If page is set in address bar.
                                $page = $_GET['page'] - 1; // Page minus 1 for math. No check for invalid arguments yet.
                            }
                            else{
                                $page = 0; // Page is 0 for math.
                            }
                            $start = $page * $json['show']; // Number representing starting position in log array.
                            $i = $i - $start; // Iteration count will be negative by the specified page argument multiplied by the number of entries to be shown.
                        }
                    }
                    foreach(array_reverse($json['log']) as $entry){ // Loop through logs in reverse order.
                        if(isset($_GET['page'])){ // If a page argument is defined.
                            if($i <= -1){ // If I counter is negative.
                                $i++; // Increment I.
                                continue; // Skip to next loop.
                            }
                        }
                        if($entry['type'] == 'p'){ // If log type is a purchase.
                            $type = '-'; // Set sign to negative.
                        }
                        if($entry['type'] == 'i'){ // If log type is income.
                            $type = '+'; // Set sign to positive.
                        }
                        echo '
                    <div class="row">
                        <div class="cell">'.date($json['date'], $entry['time']).' '.date($json['time'], $entry['time']).'</div>
                        <!--<div class="cell">'.date($json['time'], $entry['time']).'</div>-->
                        <div class="cell">'.$entry['loc'].'</div>
                        <div class="cell">'.$type.' '.$json['currency'].number_format($entry['amt'], 2).'</div>
                        <!--<div class="cell">'.$type.'</div>-->
                        <div class="cell" style="text-align:center;"><a href="'.$address.$_SERVER['PHP_SELF'].'?t=d&uid='.$entry['uid'].'" class="delete">Delete</a></div>
                    </div>'; // Print row with log entry data.
                        $i++; // Increment I.
                        if($i >= $json['show'] && $full == false){ // If I counter exceeds or equals the number of entries configured to be shown and full history is disabled.
                            break; // Break log loop.
                        }
                    }
                    ?>
                </div>
                <?php
                if($json['pages'] == 1 && $full == false){ // If pages are enabled and full history is disabled.
                    if(isset($_GET['page'])){ // If page argument is set.
                        $page = $_GET['page']; // Set var page to argument.
                    }
                    else {
                        $page = 1; // Otherwise set vsr page to 1.
                    }
                    if($page == 1){ // If var page is 1.
                        echo '<div style="width:100%;text-align:right;"><a href="#"><span id="nextback">&lt; Previous</span></a>&nbsp;&nbsp;<a href="'.$address.$_SERVER['PHP_SELF'].'?tab=3&page=2"><span id="nextback">Next &gt;</span></a></div>'; // Print navigation links with previous linking nowhere.
                    }
                    elseif($page == 2){ // If var page is 2.
                        echo '<div style="width:100%;text-align:right;"><a href="'.$address.$_SERVER['PHP_SELF'].'?tab=3"><span id="nextback">&lt; Previous</span></a>&nbsp;&nbsp;<a href="'.$address.$_SERVER['PHP_SELF'].'?tab=3&page=3"><span id="nextback">Next &gt;</span></a></div>'; // Print navigation links with previous linking back with no page specified.
                    }
                    else{
                        echo '<div style="width:100%;text-align:right;"><a href="'.$address.$_SERVER['PHP_SELF'].'?tab=3&page='.($page - 1).'"><span id="nextback">&lt; Previous</span></a>&nbsp;&nbsp;<a href="'.$address.$_SERVER['PHP_SELF'].'?tab=3&page='.($page + 1).'"><span id="nextback">Next &gt;</span></a></div>'; // Otherwise print navigation with previous as current page minus 1, and next as current page plus one.
                    }
                }
                ?>
            </div>
            <input type="radio" name="tabs" id="tabfour"<?php echo $active[4]; ?>>
            <label for="tabfour">Settings</label>
            <div class="tab">
                <form method="post" action="<?php echo $address.$_SERVER['PHP_SELF']; ?>?s">
                    <table>
                        <colgroup>
                            <col span="1" style="width:55%;">
                            <col span="2" style="width:45%;">
                        </colgroup>
                        <thead>
                            <th></th>
                            <th></th>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align:left;">Number of log entries to show:<br><span style="font-size:12px;">(To always show all enter "0")</span></td>
                                <td><input name="show" type="text" placeholder="10" cols="20" value="<?php echo $json['show']; ?>"></input></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Currency:</td>
                                <td><input name="currency" type="text" placeholder="$" cols="20" value="<?php echo $json['currency']; ?>"></input></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Notifications:<br><span style="font-size:12px;">(Requires cookies)</span></td>
                                <td>
                                    <select name="notes" id="notes">
                                        <?php
                                        if($json['notes'] == 1){ // If notifications are enabled in configuration.
                                            echo '<option value="1" selected>Enabled</option>
                                        <option value="0">Disabled</option>'; // Set selection to Enabled
                                        }
                                        else {
                                            echo '<option value="1">Enabled</option>
                                        <option value="0" selected>Disabled</option>'; // Otherwise set selection to Disabled
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Compression:<br><span style="font-size:12px;">(BZ2)</span></td>
                                <td>
                                    <select name="compress" id="compress">
                                        <?php
                                        if($json['compress'] == 1 or $compress == 1){ // If compression is enabled.
                                            echo '<option value="1" selected>Enabled</option>
                                        <option value="0">Disabled</option>'; // Set selection to Enabled
                                        }
                                        else {
                                            echo '<option value="1">Enabled</option>
                                        <option value="0" selected>Disabled</option>'; // Otherwise set selection to Disabled
                                        }
                                        ?>
                                    </select>
                                    <?php echo '('.byte(filesize($database)).')'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">History Pages:</td>
                                <td>
                                    <select name="pages" id="pages">
                                        <?php
                                        if($json['pages'] == 1){ // If pages are enabled.
                                            echo '<option value="1" selected>Enabled</option>
                                        <option value="0">Disabled</option>'; // Set selection to Enabled
                                        }
                                        else {
                                            echo '<option value="1">Enabled</option>
                                        <option value="0" selected>Disabled</option>'; // Otherwise set selection to Disabled
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Date Format:</td>
                                <td><input name="date" type="text" placeholder="Y-m-d" cols="20" value="<?php echo $json['date']; ?>"></input></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Time Format:</td>
                                <td><input name="time" type="text" placeholder="h:i A" cols="20" value="<?php echo $json['time']; ?>"></input></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Debug Mode:</td>
                                <td>
                                    <select name="debugmode" id="debugmode">
                                        <?php
                                        if($json['debug'] == 1){ // If debug mode is enabled.
                                            echo '<option value="1" selected>Enabled</option>
                                        <option value="0">Disabled</option>'; // Set selection to Enabled
                                        }
                                        else {
                                            echo '<option value="1">Enabled</option>
                                        <option value="0" selected>Disabled</option>'; // Otherwise set selection to Disabled
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><input type="submit" value="&nbsp;Save Settings&nbsp;" class="submit"></input></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <?php
            if($json['debug'] == 1){ // If debug is enabled in configuration.
                echo '<input type="radio" name="tabs" id="tabfive"'.$active[5].'>
            <label for="tabfive">Debug</label>
            <div class="tab">
                <form action="'.$address.$_SERVER['PHP_SELF'].'?e" method="post">
                    <textarea rows="20" style="width:100%;resize:none;border:none;" name="textjson">'.json_encode($json).'</textarea><br>
                    <select name="jsoncompress" id="jsoncompress">
                        '; // Print additional tab and print text box with configuration JSON displayed. Start form for editing JSON directly.
                if($compress){ // If compression is enabled.
                    echo '<option value="0">Uncompressed</option>
                        <option value="1" selected>Compressed</option>
                    '; // Set selection to Compressed.
                }
                else {
                    echo '<option value="0" selected>Uncompressed</option>
                        <option value="1">Compressed</option>
                    '; // Otherwise set selection to Uncompressed
                }
                    echo '</select>
                    <input type="submit" value="&nbsp;Save JSON&nbsp;" class="submit"></input>
                </form>
                <a class="delete" href="'.$address.$_SERVER['PHP_SELF'].'?rst">Reset Defaults</a>
            </div>'; // Print submission button to edit form and finish form. Also print a link button for resetting defaults.
            }
            ?>
        </div>
        <div style="width:100%;text-align:center;font-weight:bold;font-size:13px;margin-top:5px;">
            &copy;&nbsp;<a href="https://github.com/diablo7806" style="text-decoration:none;color:#00f">Diablo7806</a>. Released under <a href="https://www.apache.org/licenses/LICENSE-2.0.txt" style="text-decoration:none;color:#00f;">Apache-2.0</a> license.<?php // Footer with credit. Remove if you like. // ?>
        </div>
    </body>
</html>
