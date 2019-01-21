<?php 
    include_once $_SESSION['rootDir'] . '../database.php';
    include_once $_SESSION['rootDir'] . 'CLES.php'; 

    function ShowError() {
        //display error message
        if($_SESSION['error_message'] != '') {
            echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
            $_SESSION['error_message'] = '';
        }
    }
    function ShowMessage() {
        if($_SESSION['message'] != '') {
            echo '<p class="success">' . $_SESSION['message'] . '</p>';
            $_SESSION['message'] = '';
        }
    }
    function ShowAlert() {
        if($_SESSION['alert'] != '') {
            echo '<script type="text/javascript">alert("' . $_SESSION['alert'] . '")</script>';
            $_SESSION['alert'] = '';
        }
    }
    
    function NoDataRow($array, $colspan, $text = 'No data exists in the table.') {
        if($array == 0) {
            echo '<tr><td colspan="' . $colspan . '"><b>' . $text . '</b></td></tr>';
        }
    }
    
    function DebugOutput($var) {
        if($_SESSION['debug']) {
            var_dump($var);
        }
    }
    
    //$payPeriod = (date('d') >= 15) ? "16" : "01" ;
    ?>

<script type="text/javascript">
  function calcCOLA() {
    var colaForm = document.getElementById("ocola");
    colaForm.submit();
  }
  function calcCOLAM() {
    var colaForm = document.getElementById("ocolaM");
    colaForm.submit();
  }
</script>

    