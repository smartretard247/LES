<?php #$root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'); //get root folder for relative paths
    $lifetime = 60 * 60 * 3; //3 hours
    ini_set('session.use_only_cookies', true);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    session_set_cookie_params($lifetime, '/'); //all paths, must be called before session_start()
    session_save_path(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') . '/sessions'); session_start();
    date_default_timezone_set('America/New_York');
    
    if(empty($_SESSION['valid_user'])) { $_SESSION['valid_user'] = false; }
    $_SESSION['debug'] = false;
    
    #$_SESSION['rootDir'] = "/";
    $_SESSION['rootDir'] = "";
    include $_SESSION['rootDir'] . 'core/include.php';
    
    $monthOffset = filter_input(INPUT_GET, 'm');
    if(isset($monthOffset)) {
      $_SESSION['monthOffset'] = $monthOffset;
    } else {
      if(!isset($_SESSION['monthOffset'])) {
        $_SESSION['monthOffset'] = 0;
      }
    }
    
    $mmpOverride = filter_input(INPUT_GET, 'mmp');
    $month = date_create_from_format('M', date('M'));
    date_add($month, date_interval_create_from_date_string($_SESSION['monthOffset'] . " month"));
    $monthName = $month->format('F');
    
    $db = new Database('les'); //create PDO object for database
    
    //store values from db into session variables
    $lesdata = $db->SafeFetch("SELECT * FROM lesdata WHERE `ID` = :0", array(1));
    $_SESSION['Rank'] = $lesdata['Rank'];
    $_SESSION['Retired'] = ($_SESSION['Rank'] == "Retired");
    $_SESSION['TIS'] = $lesdata['TIS'];
    $_SESSION['BAH'] = $_SESSION['Retired'] ? 0 : $lesdata['BAH'];
    $_SESSION['Exemptions'] = $lesdata['Exemptions'];
    $_SESSION['ExemptionsState'] = $lesdata['ExemptionsState'];
    $_SESSION['HDP'] = $_SESSION['Retired'] ? 0 : $lesdata['HDP'];
    $_SESSION['OHA'] = $_SESSION['Retired'] ? 0 : $lesdata['OHA'];
    $_SESSION['COLADailyRate'] = $lesdata['COLADailyRate'];
    $_SESSION['COLADailyRateM'] = $lesdata['COLADailyRateM'];
    $_SESSION['FamilySep'] = $_SESSION['Retired'] ? 0 : $lesdata['FamilySep'];
    $_SESSION['MMPOffset'] = $lesdata['MMPOffset'];
    $_SESSION['LOCCODE'] = $lesdata['LOCCODE'];
    $_SESSION['Dental'] = $lesdata['Dental'];
    $_SESSION['bDental'] = filter_input(INPUT_POST, "bDental");
    $_SESSION['Clothing'] = $lesdata['Clothing'];
    
    $cLES = new CLES($_SESSION['Rank'], $_SESSION['TIS'], $_SESSION['Exemptions'], $_SESSION['ExemptionsState']);
    $cEnt = $cLES->GetEntitlements();
    $cDed = $cLES->GetDeductions();
    $cAllot = $cLES->GetAllotments();
    
    //get allotments from database
    $allotments = $db->SafeFetchAll("SELECT Name, Amount FROM allotments WHERE Name <> ''");
    $_SESSION['Allotments'] = array();
    if($allotments) {
        foreach($allotments as $row) {
            $_SESSION['Allotments'][] = array('Name' => $row['Name'], 'Amount' => (float)$row['Amount']);
            $lastElement = end($_SESSION['Allotments']);
            $cAllot->AddAllotment($lastElement['Name'], $lastElement['Amount']);
        }
    }
    
    if($_SESSION['bDental']) {
      $cAllot->AddAllotment("Tricare Dental", $_SESSION['Dental']);
    }
    
    DebugOutput($_SESSION['Allotments']);
    
    $crFwd = 0.0;
?>

<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->

<html>
    <head>
        <meta charset="UTF-8">
        <title>LES for <?php echo $monthName; ?></title>
        <link rel="stylesheet" type="text/css" href="/les/format.css" />
    </head>
    <body>
      <form action="https://www.defensetravel.dod.mil/pdcgi/cola-oha/o_cola4.cgi" method="post" target="_blank" name="ocola" id="ocola" hidden>
        <input name="year" value="<?php echo $month->format('Y'); ?>"/>
        <input name="month" value="<?php echo $month->format('n'); ?>"/>
        <input name="day" value="01"/>
        <input name="LOCCODE2" hidden/><input name="CINDEX2"/>
        <input name="LOCCODE" value="<?php echo $_SESSION['LOCCODE'] ?>"/>
        <input name="RANK" value="<?php echo str_replace("-", "", $_SESSION['Rank']); ?>"/>
        <input name="SERVICE" value="<?php echo ($_SESSION['TIS']+4)/2 ?>"/>
        <input name="DEPEND" value="4"/>
        <input name="BARRACK" value="NO"/>
        <input id="calcButton" name="submit2" value="CALCULATE" type="submit"/>
      </form>
      <form action="https://www.defensetravel.dod.mil/pdcgi/cola-oha/o_cola4.cgi" method="post" target="_blank" name="ocolaM" id="ocolaM" hidden>
        <input name="year" value="<?php echo $month->format('Y'); ?>"/>
        <input name="month" value="<?php echo $month->format('n'); ?>"/>
        <input name="day" value="16"/>
        <input name="LOCCODE2" hidden/><input name="CINDEX2"/>
        <input name="LOCCODE" value="<?php echo $_SESSION['LOCCODE'] ?>"/>
        <input name="RANK" value="<?php echo str_replace("-", "", $_SESSION['Rank']); ?>"/>
        <input name="SERVICE" value="<?php echo ($_SESSION['TIS']+4)/2 ?>"/>
        <input name="DEPEND" value="4"/>
        <input name="BARRACK" value="NO"/>
        <input id="calcButtonM" name="submit2" value="CALCULATE" type="submit"/>
      </form>
      
      
      <div id="main">
        <?php if($_SESSION['valid_user']) :
            $cEnt->SetBAH($_SESSION['BAH']); //if different from database
            $cEnt->SetHDP($_SESSION['HDP']);
            $cEnt->SetOHA($_SESSION['OHA']);
            $cEnt->SetClothing($_SESSION['Clothing']);
            $cEnt->SetCOLADailyRate($_SESSION['COLADailyRate']);
            $cEnt->SetCOLADailyRate($_SESSION['COLADailyRateM'], true);
            $cEnt->SetFamilySeparation($_SESSION['FamilySep']);
            
            $cDed->SetSGLI($_SESSION['Retired'] ? 0 : 29.00);
            $cDed->SetSGLIFam($_SESSION['Retired'] ? 0 : 5.00);
            $cDed->SetAFRH($_SESSION['Retired'] ? 0 : 0.5);
            
            $cDed->SetMMPOffset($_SESSION['MMPOffset']);
            if($mmpOverride) {
              $cDed->OverrideMidMonthPay($mmpOverride);
            } else {
              $totalEntitlements = $cEnt->GetTotalEntitlements($_SESSION['monthOffset']) - $cEnt->GetCOLADailyRate(false);
              $cDed->SetMidMonthPay($totalEntitlements - $cEnt->GetClothing($cEnt->GetMonthWithOffset($_SESSION['monthOffset'])), $cAllot->GetTotalAllotments(), $cEnt->GetDaysInNextMonth($_SESSION['monthOffset']));
            }
            
            $slotNum = 1;
            $inputSize = 8;
            $inputSizeMultiplier = 10;
            $sizeOfAllotmentTextBox = 16;
        ?>
        
        <h2><a href="https://mypay.dfas.mil/mypay.aspx" style="font-size: larger;" target="_blank">LES for <?php echo $monthName; ?></a></h2>
        
        <?php ShowError(); ShowMessage(); ?>
        
        <table id="les">
            <tr>
                <th colspan="2">Entitlements</th>
                <th colspan="2" title="~18.95%">Deductions</th>
                <th colspan="2">Allotments</th>
                <th colspan="2">Summary</th>
            </tr>
            <tr>
                <td>Base Pay</td><td>$ <?php echo number_format($cEnt->GetBasePay(),2); ?></td>
                <td title="~6%">Federal Tax</td><td>$ <?php echo number_format($cDed->GetFederalTax(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td>Total Entitlements</td><td>$ <?php echo number_format($cEnt->GetTotalEntitlements($_SESSION['monthOffset']),2); ?></td>
            </tr>
            <tr>
                <td>BAS</td><td>$ <?php echo number_format($cEnt->GetBAS(),2); ?></td>
                <td title="6.2%">Social Security</td><td>$ <?php echo number_format($cDed->GetSocialSecurity(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td>Total Deductions</td><td>$ <?php echo number_format($cDed->GetTotalDeductions(),2); ?></td>
            </tr>
            <tr>
                <td>BAH</td><td>$ <?php echo number_format($cEnt->GetBAH(),2); ?></td>
                <td title="1.45%">Medicare</td><td>$ <?php echo number_format($cDed->GetMedicare(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td>Total Allotments</td><td>$ <?php echo number_format($cAllot->GetTotalAllotments(),2); ?></td>
            </tr>
            <tr>
                <td>HDP</td><td>$ <?php echo number_format($cEnt->GetHDP(),2); ?></td>
                <td>SGLI</td><td>$ <?php echo number_format($cDed->GetSGLI(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td>Net Amount</td><td>$ <?php echo number_format($cLES->GetNetAmount($_SESSION['monthOffset']),2); ?></td>
            </tr>
            <tr>
                <td>OHA</td><td>$ <?php echo number_format($cEnt->GetOHA(),2); ?></td>
                <td title="~5.3%">State Tax</td><td>$ <?php echo number_format($cDed->GetStateTax(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td>Cr Fwd</td><td>$ <?php echo number_format($crFwd,2); ?></td>
            </tr>
            <tr>
                <td>COLA</td><td>$ <?php echo number_format($cEnt->GetCOLA($cEnt->GetDaysInNextMonth($_SESSION['monthOffset'])),2); ?></td>
                <td>AFRH</td><td>$ <?php echo number_format($cDed->GetAFRH(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td>EOM Pay</td><td>$ <?php echo number_format($cLES->GetEOMPay($crFwd, $_SESSION['monthOffset']),2); ?></td>
            </tr>
            <tr>
                <td>Clothing</td><td>$ <?php echo number_format($cEnt->GetClothing($month->format('n')),2); ?></td>
                <td>SGLI Family</td><td>$ <?php echo number_format($cDed->GetSGLIFam(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td colspan="2"</td>
            </tr>
            <tr>
                <td>Fam Sep</td><td>$ <?php echo number_format($cEnt->GetFamilySeparation(),2); ?></td>
                <td>Mid-Month Pay</td><td>$ <?php echo number_format($cDed->GetMidMonthPay(),2); ?></td>
                <td>
                    <?php if($cAllot->GetNumSlots() >= $slotNum) {
                        echo $cAllot->GetSlotName($slotNum-1);
                        echo '</td><td>$ ';
                        echo number_format($cAllot->GetSlotAmount($slotNum-1),2);
                        ++$slotNum;
                    } else {
                        echo '<br/></td><td><br/>'; //else insert empty td
                    } ?>
                </td>
                <td colspan="2"</td>
            </tr>
            <tr>
                <th class="footer">Total</th><td class="footer">$ <?php echo number_format($cEnt->GetTotalEntitlements($_SESSION['monthOffset']),2); ?></td>
                <th class="footer">Total</th><td class="footer">$ <?php echo number_format($cDed->GetTotalDeductions(),2); ?></td>
                <th class="footer">Total</th><td class="footer">$ <?php echo number_format($cAllot->GetTotalAllotments(),2); ?></td>
                <th class="footer">My Pay</th><td class="footer">$ <?php echo number_format($cDed->GetMidMonthPay()+$cLES->GetEOMPay($crFwd, $_SESSION['monthOffset']),2); ?></td>
            </tr>
        </table>
        
        <table id="data">
            <tr>
                <td style="padding-right: 20px; vertical-align: top; border-right: none; border-bottom: none;">
                    <form action="/les/core/updateData.php" method="post">
                        <table>
                            <tr>
                                <th colspan="4">Individual Data</th>
                            </tr>
                            <tr>
                                <td>Rank:</td>
                                <td>
                                  <select name="Rank" style="width: <?php echo $inputSize*$inputSizeMultiplier; ?>">
                                    <?php $rankValues = array("E-2","E-3","E-4","E-5","E-6","E-7","E-8","E-9","W-1","W-2","W-3","W-4","W-5","O-1","O-2","O-3","O-4","O-5","O-6","O-7","O-8","O-9","O-10","Retired");
                                    foreach($rankValues as $rank) : ?>
                                      <?php if($rank == $_SESSION['Rank']) : ?>
                                        <option selected="selected" value="<?php echo $rank; ?>"><?php echo $rank; ?></option>
                                      <?php else: ?>
                                        <option value="<?php echo $rank; ?>"><?php echo $rank; ?></option>
                                      <?php endif; ?>
                                    <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>BAH:</td><td><input size="<?php echo $inputSize; ?>" name="BAH" type="text" value="<?php echo number_format($_SESSION['BAH'],2,'.','');number ?>"></td>
                            </tr>
                            <tr>
                                <td>Time in Service:</td>
                                <td>
                                  <select name="TIS" style="width: <?php echo $inputSize*$inputSizeMultiplier; ?>">
                                    <?php $yearValues = array(1,2,3,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40);
                                    foreach($yearValues as $year) : ?>
                                      <?php if($year == $_SESSION['TIS']) : ?>
                                        <option selected value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                      <?php else: ?>
                                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                      <?php endif; ?>
                                    <?php endforeach; ?>
                                  </select>
                                </td>
                                <td>OHA:</td><td><input size="<?php echo $inputSize; ?>" name="OHA" type="text" value="<?php echo number_format($cEnt->GetOHA(),2,'.',''); ?>"></td>
                            </tr>
                            <tr>
                                <td>Fed. Exemptions:</td>
                                <td>
                                  <select name="Exemptions" style="width: <?php echo $inputSize*$inputSizeMultiplier; ?>">
                                    <?php for($i = 0; $i <= 10; $i++) : ?>
                                      <?php if($i == $_SESSION['Exemptions']) : ?>
                                        <option selected="selected" value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                      <?php else: ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                      <?php endif; ?>
                                    <?php endfor; ?>
                                  </select>
                                </td>
                                <td>HDP:</td><td><input size="<?php echo $inputSize; ?>" name="HDP" type="text" value="<?php echo number_format($cEnt->GetHDP(),2,'.',''); ?>"></td>
                            </tr>
                            <tr>
                              <td>State Exemptions:</td>
                              <td>
                                <select name="ExemptionsState" style="width: <?php echo $inputSize*$inputSizeMultiplier; ?>">
                                  <?php for($i = 0; $i <= 10; $i++) : ?>
                                    <?php if($i == $_SESSION['ExemptionsState']) : ?>
                                      <option selected="selected" value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php else: ?>
                                      <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endif; ?>
                                  <?php endfor; ?>
                                </select>
                              </td>
                              <td>Family Sep:</td><td><input size="<?php echo $inputSize; ?>" name="FamilySep" type="text" value="<?php echo number_format($cEnt->GetFamilySeparation(),2,'.',''); ?>"></td>
                            </tr>
                            <tr>
                              <td>
                                <a href="" onclick="calcCOLA();">COLA Daily Rate:</a></td><td><input size="<?php echo $inputSize; ?>" name="COLADailyRate" type="text" value="<?php echo $cEnt->GetCOLADailyRate(); ?>"></td>
                              </td>
                              <td>
                                <a href="" onclick="calcCOLAM();">Mid-Month COLA:</a></td><td><input size="<?php echo $inputSize; ?>" name="COLADailyRateM" type="text" value="<?php echo $cEnt->GetCOLADailyRate(true); ?>"></td>
                              </td>
                            </tr>
                            <tr>
                              <td>MMP Offset:</td>
                              <td>
                                <input size="<?php echo $inputSize; ?>" name="MMPOffset" type="text" value="<?php echo number_format($cDed->GetMMPOffset(),2); ?>"/>
                              </td>
                              <td colspan="2" style="text-align: right;">Save Changes: <input size="<?php echo $inputSize; ?>" name="action" type="submit" value="Save"></td>
                            </tr>
                        </table>
                    </form>
                </td>
                <td style="vertical-align: top; border-right: none; border-bottom: none;">
                    
                        <table>
                          <tr>
                            <th>Allotments</th>
                            <th><form id="fDental" method="post" action=""><label title="Tricare Dental Allotment"><input type="checkbox" name="bDental" <?php echo $_SESSION['bDental'] ? " checked" : ""; ?> value="<?php echo $_SESSION['bDental'] ? 0 : 1; ?>" onclick="submit();"/>Dental?</label></form></th>
                          </tr>
                            <form action="/les/core/updateAllotments.php" method="post">
                            <?php $totalInAllotments = 0.0; $i = 1; if($allotments) : foreach($allotments as $row) : ?>
                                <tr>
                                    <td>#<?php echo $i; ?>: <input size="<?php echo $sizeOfAllotmentTextBox; ?>" name="Name<?php echo $i; ?>" type="text" value="<?php echo $row['Name']; ?>"></td>
                                    <?php $totalInAllotments += number_format($row['Amount'],2,'.',''); ?>
                                    <td><input size="<?php echo $inputSize; ?>" name="Amount<?php echo $i; ?>" type="text" value="<?php echo number_format($row['Amount'],2,'.',''); ?>"></td>
                                </tr>
                            <?php $i++; endforeach; endif; ?>
                            <?php if($i <= 6) : ?>
                                <tr>
                                    <td>#<?php echo $i; ?>: <input size="<?php echo $sizeOfAllotmentTextBox; ?>" name="Name<?php echo $i; ?>" type="text" value=""></td>
                                    <td><input size="<?php echo $inputSize; ?>" name="Amount<?php echo $i; ?>" type="text" value=""></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Total in allotments:</td><td><b><?php echo number_format($totalInAllotments+($_SESSION['bDental'] ? $_SESSION['Dental'] : 0),2); ?></b></td>
                            </tr>
                            <tr>
                              <td colspan="2" style="text-align: right;">
                                Save Changes: <input size="<?php echo $inputSize; ?>" name="action" type="submit" value="Save">
                              </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
        </table>
        
        <a href="https://www.dfas.mil/militarymembers/payentitlements/military-pay-charts.html">Pay Charts</a>
            
        <?php else : ?>
            <form action="../core/login.php?return=les" method="post">
                <table id="login">
                    <tr>
                        <th colspan="2">Login Information</th>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right;">
                            Username: <input name="Username" type="text"><br/>
                            Password: <input name="ThePassword" type="password"><br/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" ><input type="submit" value="Login"/></td>
                    </tr>
                </table>
            </form>
        <?php endif; ?>
    </div></body>
</html>