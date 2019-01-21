<?php $root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'); //get root folder for relative paths
  session_save_path($root . '/sessions'); session_start();

  include_once $_SESSION['rootDir'] . '../../database.php'; $db = new Database('les');

  $rank = filter_input(INPUT_POST, 'Rank');
  $tis = filter_input(INPUT_POST, 'TIS');
  $bah = filter_input(INPUT_POST, 'BAH');
  $exemptions = filter_input(INPUT_POST, 'Exemptions');
  $exemptionsState = filter_input(INPUT_POST, 'ExemptionsState');
  $hdp = filter_input(INPUT_POST, 'HDP');
  $oha = filter_input(INPUT_POST, 'OHA');
  $colaDR = filter_input(INPUT_POST, 'COLADailyRate');
  $colaDRM = filter_input(INPUT_POST, 'COLADailyRateM');
  $famSep = filter_input(INPUT_POST, 'FamilySep');
  $mmpOffset = filter_input(INPUT_POST, 'MMPOffset');

  if($rank != '') {
    if($rank == 'Retired') {
      $_SESSION['Rank'] = "Retired";
    } else {
      $temp = explode('-', $rank);
      if($temp[0] == 'E' || $temp[0] == 'O' || $temp[0] == 'W') {
        if($temp[1] > 0 && $temp[1] <= 10) {
          $_SESSION['Rank'] = $rank;
        } else { $_SESSION['error_message'] .= 'Invalid grate/rate. Format should be "E-6".<br/>'; }
      } else { $_SESSION['error_message'] .= 'Invalid format. Format should be "E-6".<br/>'; }
    }
  } else { $_SESSION['error_message'] .= 'Invalid rank.<br/>'; }

  if($tis > 0 && $tis <= 40) {
    $_SESSION['TIS'] = $tis;
  } else { $_SESSION['error_message'] .= 'Time in Service not supported.<br/>'; }

  if($bah >= 0) {
    $_SESSION['BAH'] = $bah;
  } else { $_SESSION['error_message'] .= 'BAH cannot be negative.<br/>'; }

  if($exemptions >= 0 && $exemptions <= 9) {
    $_SESSION['Exemptions'] = $exemptions;
  } else { $_SESSION['error_message'] .= 'Exemptions must be between 0 and 9.<br/>'; }

  if($hdp >= 0) {
    $_SESSION['HDP'] = $hdp;
  } else { $_SESSION['error_message'] .= 'HDP cannot be negative.<br/>'; }

  if($oha >= 0) {
    $_SESSION['OHA'] = $oha;
  } else { $_SESSION['error_message'] .= 'OHA cannot be negative.<br/>'; }

  if($colaDR >= 0) {
    $_SESSION['COLADailyRate'] = $colaDR;
  } else { $_SESSION['error_message'] .= 'COLA Daily Rate cannot be negative.<br/>'; }

  if($colaDRM >= 0) {
    $_SESSION['COLADailyRateM'] = $colaDRM;
  } else { $_SESSION['error_message'] .= 'Mid-month COLA cannot be negative.<br/>'; }

  if($famSep >= 0) {
    $_SESSION['FamilySep'] = $famSep;
  } else { $_SESSION['error_message'] .= 'Family separation cannot be negative.<br/>'; }

  if($mmpOffset <= 100 && $mmpOffset >= -100) {
    $_SESSION['MMPOffset'] = $mmpOffset;
  } else { $_SESSION['error_message'] .= 'Mid-month pay offset must be within positive or negative 100 dollars.<br/>'; }

  if($_SESSION['error_message'] == '') {
    $aArgs = array('lesdata', 1,
            'Rank', $rank,
            'TIS', $tis,
            'BAH', $bah,
            'Exemptions', $exemptions,
            'HDP', $hdp,
            'OHA', $oha,
            'COLADailyRate', $colaDR,
            'COLADailyRateM', $colaDRM,
            'FamilySep', $famSep,
            'MMPOffset', $mmpOffset,
            'ExemptionsState', $exemptionsState);

    if($db->UpdateMultipleColumnsDB($aArgs)) {
      //on successful add goto...
      header("location:../");
      exit();
    } else {
      $_SESSION['error_message'] .= 'You did not make any changes.<br/>';
    }
  } else {
    //$_SESSION['error_message'] = substr($_SESSION['error_message'], 0, strlen($_SESSION['error_message'])-5);
  }

  header("location:../?");
  exit();
